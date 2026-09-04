<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Cron;

use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Versand\VersandDienst;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Verteilergebnis;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Verteiler;

/**
 * Der regelmäßige Lauf, der alle Postfächer der Mailinglisten abfragt.
 *
 * Der Dienst ist im Container mit `interval: minutely` eingetragen, wird also
 * bei jedem Cron-Durchgang von Contao aufgerufen. Wie oft eine **einzelne**
 * Liste tatsächlich an die Reihe kommt, bestimmt deren Feld „Prüfintervall".
 * Der Umweg über die Datenbank ist nötig, weil der Dienst-Tag nur ein einziges,
 * für alle Listen gleiches Intervall zulässt.
 *
 * Wichtig für den Betrieb: Contaos Cron läuft in der Voreinstellung über
 * Seitenaufrufe von Besuchern. Auf einer wenig besuchten Seite bedeutet das
 * lange Pausen. Für eine Mailingliste gehört deshalb ein echter Cronjob
 * eingerichtet (`vendor/bin/contao-console contao:cron`).
 */
class MailinglistenCron
{
    /**
     * @param ContaoFramework $framework Muss vor jedem Zugriff auf Models
     *                                   angestoßen werden; im Cron-Kontext ist
     *                                   das nicht selbstverständlich
     * @param Verteiler       $verteiler Arbeitet das Postfach einer Liste ab
     * @param VersandDienst   $versand   Wird am Ende zum Schließen der
     *                                   SMTP-Verbindungen gebraucht
     * @param LoggerInterface $logger    Schreibt die Zusammenfassung; im
     *                                   Container ist das
     *                                   `monolog.logger.contao.cron`
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Verteiler $verteiler,
        private readonly VersandDienst $versand,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Geht alle veröffentlichten Mailinglisten durch, soweit sie fällig sind.
     *
     * Ein Fehler bei einer Liste beendet den Durchgang nicht: Der Verteiler
     * fängt Postfachfehler selbst ab und vermerkt sie im Protokoll. Die
     * übrigen Listen werden anschließend ganz normal bearbeitet.
     *
     * Die SMTP-Verbindungen werden am Ende ausdrücklich geschlossen. Läuft der
     * Cron über einen dauerhaft laufenden Prozess, blieben sie sonst stehen,
     * bis der Server sie von sich aus kappt — und der nächste Versand liefe
     * dann in eine tote Verbindung.
     *
     * @return void
     */
    public function __invoke(): void
    {
        $this->framework->initialize();

        $listen = MailinglistenModel::findAktive();

        if (null === $listen) {
            return;
        }

        $gesamt = new Verteilergebnis();
        $bearbeitet = 0;

        foreach ($listen as $liste) {
            if (!$liste->istFaellig()) {
                continue;
            }

            $gesamt = $gesamt->plus($this->verteiler->listeVerarbeiten($liste));
            ++$bearbeitet;
        }

        $this->versand->verbindungenSchliessen();

        // Ein Eintrag nur dann, wenn wirklich etwas geschah. Andernfalls
        // schriebe der Minutentakt das Protokoll zu, und die Zeilen, auf die
        // es ankommt, gingen darin unter.
        if ($gesamt->gelesen > 0 || $gesamt->fehler > 0) {
            $this->logger->info(sprintf('Mailinglisten (%d geprüft): %s', $bearbeitet, $gesamt->alsText()));
        }
    }
}
