<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\EventListener;

use Contao\Database;
use Contao\DataContainer;
use Contao\System;

/**
 * Rückrufe für den Data Container der Teilnehmer.
 *
 * Zwei Dinge müssen hier zuverlässig geschehen: Die Adresse muss
 * kleingeschrieben in der Datenbank landen, weil der Verteiler ohne `LOWER()`
 * vergleicht, damit der Index greift. Und dieselbe Adresse darf innerhalb einer
 * Liste nur einmal vorkommen — sonst bekäme der Betreffende jede Nachricht
 * doppelt, und beim Aufnahmeantrag entstünde bei jedem Anlauf ein weiterer
 * Eintrag.
 */
class TlMailinglisteAbonnentListener
{
    /**
     * Normiert die E-Mail-Adresse und weist Dubletten ab.
     *
     * Die Prüfung fragt die Datenbank ab, statt sich auf einen eindeutigen
     * Index zu verlassen: Ein Datenbankfehler würde im Backend als
     * unverständliche Ausnahme erscheinen, während eine geworfene Ausnahme mit
     * verständlichem Text von Contao als Feldfehler angezeigt wird.
     *
     * @param mixed         $wert Die eingegebene Adresse
     * @param DataContainer $dc   Liefert ID und Elterndatensatz
     *
     * @return string Die kleingeschriebene Adresse
     *
     * @throws \Exception Wenn die Adresse in dieser Liste bereits vorkommt
     */
    public function adressePruefen(mixed $wert, DataContainer $dc): string
    {
        $email = strtolower(trim((string) $wert));

        if ('' === $email) {
            return '';
        }

        // Die Eltern-ID steht beim Anlegen in `currentPid`, beim Bearbeiten im
        // Datensatz selbst. `currentPid` gibt es dank Vorwärtskompatibilität
        // auch in Contao 4.13.
        $pid = (int) ($dc->activeRecord->pid ?? $dc->currentPid ?? 0);

        $treffer = Database::getInstance()
            ->prepare('SELECT id FROM tl_mailingliste_abonnent WHERE pid=? AND email=? AND id!=?')
            ->execute($pid, $email, (int) $dc->id)
        ;

        if ($treffer->numRows > 0) {
            System::loadLanguageFile('tl_mailingliste_abonnent');

            throw new \Exception(sprintf(
                $GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['dublette'] ?? 'Die Adresse "%s" ist in dieser Mailingliste bereits eingetragen.',
                $email,
            ));
        }

        return $email;
    }

    /**
     * Stellt einen Teilnehmer in der Übersicht dar.
     *
     * Der Status ist die wichtigste Angabe und bekommt deshalb Farbe: Ein
     * offener Antrag soll beim Überfliegen der Liste auffallen, eine Sperre
     * ebenso. Die eingeschränkten Rechte stehen nur dann dabei, wenn sie vom
     * Regelfall abweichen — sonst stünde an jeder Zeile dasselbe.
     *
     * @param array<string, mixed> $row Der Datensatz des Teilnehmers
     *
     * @return string HTML für die Zeile in der Übersicht
     */
    public function kindDatensatz(array $row): string
    {
        System::loadLanguageFile('tl_mailingliste_abonnent');

        $status = (string) $row['status'];
        $beschriftung = $GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte'][$status] ?? $status;

        $farbe = match ($status) {
            'beantragt' => '#b45f06',
            'gesperrt' => '#a61c00',
            default => '#4a8f2a',
        };

        $name = trim(($row['vorname'] ?? '').' '.($row['nachname'] ?? ''));
        $rechte = [];

        if (!$row['darfSenden']) {
            $rechte[] = 'darf nicht senden';
        }

        if (!$row['darfEmpfangen']) {
            $rechte[] = 'empfängt nicht';
        }

        return sprintf(
            '<div class="tl_content_left">%s%s <span style="color:%s">[%s]</span>%s</div>',
            htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8'),
            '' !== $name ? ' <span style="color:#999">'.htmlspecialchars($name, ENT_QUOTES, 'UTF-8').'</span>' : '',
            $farbe,
            htmlspecialchars((string) $beschriftung, ENT_QUOTES, 'UTF-8'),
            $rechte ? ' <span style="color:#999">('.implode(', ', $rechte).')</span>' : '',
        );
    }
}
