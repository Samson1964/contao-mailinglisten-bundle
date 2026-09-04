<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Command;

use Contao\CoreBundle\Framework\ContaoFramework;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Postfach\Nachbehandlung;
use Schachbulle\ContaoMailinglistenBundle\Postfach\PostfachFehler;
use Schachbulle\ContaoMailinglistenBundle\Postfach\PostfachLeserInterface;
use Schachbulle\ContaoMailinglistenBundle\Postfach\Postfachzugang;
use Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher;
use Schachbulle\ContaoMailinglistenBundle\Versand\VersandDienst;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Verteilergebnis;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Verteiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Ruft die Postfächer der Mailinglisten von Hand ab.
 *
 * Der Befehl ist vor allem für die Einrichtung gedacht. Wer eine neue Liste
 * anlegt, will nicht bis zum nächsten Cron-Durchgang warten und schon gar
 * nicht raten, ob eine falsche Portangabe oder ein falsches Kennwart schuld
 * ist — mit `--pruefen` steht die Antwort in einer Zeile.
 *
 * Aufruf:
 *   vendor/bin/contao-console contao:mailingliste:abrufen
 *   vendor/bin/contao-console contao:mailingliste:abrufen 3
 *   vendor/bin/contao-console contao:mailingliste:abrufen 3 --pruefen
 */
class PostfachAbrufenCommand extends Command
{
    /**
     * Der Name, unter dem Contao den Befehl anbietet.
     *
     * Das Attribut #[AsCommand] wird bewusst nicht benutzt: Es setzt
     * symfony/console 5.3 voraus und die statische Eigenschaft funktioniert in
     * jeder unterstützten Fassung.
     *
     * @var string
     */
    protected static $defaultName = 'contao:mailingliste:abrufen';

    /**
     * @param ContaoFramework        $framework      Muss vor jedem Model-Zugriff laufen
     * @param Verteiler              $verteiler      Arbeitet ein Postfach ab
     * @param VersandDienst          $versand        Schließt am Ende die SMTP-Verbindungen
     * @param PostfachLeserInterface $leser          Wird für den reinen Verbindungstest gebraucht
     * @param Geheimspeicher         $geheimspeicher Entschlüsselt das IMAP-Kennwort für den Test
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Verteiler $verteiler,
        private readonly VersandDienst $versand,
        private readonly PostfachLeserInterface $leser,
        private readonly Geheimspeicher $geheimspeicher,
    ) {
        parent::__construct();
    }

    /**
     * Beschreibt Argumente und Optionen des Befehls.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Ruft die Postfächer der Mailinglisten ab und verteilt die Nachrichten.')
            ->addArgument('liste', InputArgument::OPTIONAL, 'ID einer einzelnen Mailingliste; ohne Angabe werden alle veröffentlichten bearbeitet')
            ->addOption('pruefen', null, InputOption::VALUE_NONE, 'Nur die Verbindung prüfen und die ungelesenen Nachrichten zählen, nichts verteilen und nichts verändern')
        ;
    }

    /**
     * Führt den Befehl aus.
     *
     * Anders als der Cronjob beachtet der Befehl das Prüfintervall **nicht**:
     * Wer ihn von Hand aufruft, will jetzt ein Ergebnis sehen.
     *
     * @param InputInterface  $input  Argumente und Optionen des Aufrufs
     * @param OutputInterface $output Ziel der Ausgabe
     *
     * @return int Command::SUCCESS, oder Command::FAILURE wenn die angegebene
     *             Liste nicht gefunden wurde oder eine Prüfung scheiterte
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $stil = new SymfonyStyle($input, $output);
        $listen = $this->listenErmitteln($input->getArgument('liste'));

        if (!$listen) {
            $stil->error('Es wurde keine passende Mailingliste gefunden.');

            return Command::FAILURE;
        }

        if ($input->getOption('pruefen')) {
            return $this->verbindungenPruefen($listen, $stil);
        }

        $gesamt = new Verteilergebnis();

        foreach ($listen as $liste) {
            $ergebnis = $this->verteiler->listeVerarbeiten($liste);
            $gesamt = $gesamt->plus($ergebnis);

            $stil->writeln(sprintf('<info>%s</info>: %s', $liste->titel, $ergebnis->alsText()));
        }

        $this->versand->verbindungenSchliessen();

        $stil->success('Gesamt: '.$gesamt->alsText());

        return Command::SUCCESS;
    }

    /**
     * Sucht die zu bearbeitenden Listen heraus.
     *
     * Bei einer ausdrücklich genannten ID wird auch eine nicht veröffentlichte
     * Liste geliefert — beim Einrichten ist eine Liste in aller Regel noch
     * abgeschaltet, und gerade dann will man sie prüfen.
     *
     * @param string|null $argument Die ID aus der Befehlszeile, oder null
     *
     * @return MailinglistenModel[] Die gefundenen Listen, notfalls leer
     */
    private function listenErmitteln(?string $argument): array
    {
        if (null !== $argument && '' !== $argument) {
            $liste = MailinglistenModel::findByPk((int) $argument);

            return null !== $liste ? [$liste] : [];
        }

        $listen = MailinglistenModel::findAktive();

        return null !== $listen ? $listen->getModels() : [];
    }

    /**
     * Prüft nur die Erreichbarkeit der Postfächer.
     *
     * Der Rückruf gibt für jede Nachricht `Behalten` zurück. Dadurch bleibt das
     * Postfach unverändert: nichts wird als gelesen markiert, verschoben,
     * gelöscht oder verteilt. Der Befehl lässt sich also gefahrlos wiederholen.
     *
     * @param MailinglistenModel[] $listen Die zu prüfenden Listen
     * @param SymfonyStyle        $stil   Für die Ausgabe
     *
     * @return int Command::SUCCESS, wenn alle Postfächer erreichbar waren
     */
    private function verbindungenPruefen(array $listen, SymfonyStyle $stil): int
    {
        $fehler = 0;

        foreach ($listen as $liste) {
            $zugang = new Postfachzugang(
                host: trim((string) $liste->imapHost),
                port: (int) $liste->imapPort ?: 993,
                verschluesselung: (string) $liste->imapVerschluesselung,
                benutzer: trim((string) $liste->imapBenutzer),
                kennwort: $this->geheimspeicher->entschluesseln((string) $liste->imapKennwort),
                ordner: trim((string) $liste->imapOrdner) ?: 'INBOX',
                zertifikatPruefen: (bool) $liste->imapZertifikat,
            );

            try {
                $anzahl = $this->leser->verarbeiten($zugang, max(1, (int) $liste->hoechstzahl), static fn () => Nachbehandlung::Behalten);

                $stil->writeln(sprintf(
                    '<info>%s</info> (%s auf %s): Verbindung steht, %d ungelesene Nachricht%s.',
                    $liste->titel,
                    $zugang->benutzer,
                    $zugang->host,
                    $anzahl,
                    1 === $anzahl ? '' : 'en',
                ));

                $this->teilnehmerZeigen($liste, $stil);
            } catch (PostfachFehler $e) {
                ++$fehler;
                $stil->writeln(sprintf('<error>%s</error>: %s', $liste->titel, $e->getMessage()));
            }
        }

        if ($fehler > 0) {
            $stil->error(sprintf('%d von %d Postfächern konnten nicht gelesen werden.', $fehler, \count($listen)));

            return Command::FAILURE;
        }

        $stil->success('Alle Postfächer sind erreichbar.');

        return Command::SUCCESS;
    }

    /**
     * Zeigt, wen die Liste beim Verteilen tatsächlich erreichen würde.
     *
     * Die Angabe beantwortet die häufigste Frage bei einer Liste, die
     * scheinbar nichts verteilt: Wie viele Teilnehmer hält das Bundle für
     * empfangsberechtigt? Wer noch auf Freigabe wartet oder den Empfang
     * abgeschaltet hat, taucht in der Verteilung nicht auf — im Backend ist
     * das erst zu sehen, wenn man jeden Eintrag einzeln öffnet.
     *
     * Bei genau einem Empfänger folgt ein ausdrücklicher Hinweis: Dann
     * bekommt nur der Verfasser seine eigene Nachricht zurück, was leicht wie
     * ein Fehler der Verteilung aussieht, in Wahrheit aber an den
     * Teilnehmerdaten liegt.
     *
     * @param MailinglistenModel $liste Die zu untersuchende Liste
     * @param SymfonyStyle      $stil  Für die Ausgabe
     *
     * @return void
     */
    private function teilnehmerZeigen(MailinglistenModel $liste, SymfonyStyle $stil): void
    {
        $empfaenger = MailinglistenAbonnentModel::findEmpfaenger((int) $liste->id);
        $alle = MailinglistenAbonnentModel::findBy(['pid=?'], [(int) $liste->id]);

        $anzahlEmpfaenger = null !== $empfaenger ? $empfaenger->count() : 0;
        $zaehlung = ['aktiv' => 0, 'beantragt' => 0, 'gesperrt' => 0, 'ohneEmpfang' => 0, 'ohneSenderecht' => 0];

        if (null !== $alle) {
            foreach ($alle as $einzelner) {
                $zaehlung[$einzelner->status] = ($zaehlung[$einzelner->status] ?? 0) + 1;

                if (MailinglistenAbonnentModel::STATUS_AKTIV === $einzelner->status) {
                    if (!$einzelner->darfEmpfangen) {
                        ++$zaehlung['ohneEmpfang'];
                    }

                    if (!$einzelner->darfSenden) {
                        ++$zaehlung['ohneSenderecht'];
                    }
                }
            }
        }

        $stil->writeln(sprintf(
            '  Teilnehmer: %d aktiv, %d beantragt, %d gesperrt. Empfänger einer Verteilung: <comment>%d</comment>%s%s',
            $zaehlung['aktiv'],
            $zaehlung['beantragt'],
            $zaehlung['gesperrt'],
            $anzahlEmpfaenger,
            $zaehlung['ohneEmpfang'] > 0 ? sprintf(' (%d aktive ohne Empfang)', $zaehlung['ohneEmpfang']) : '',
            $zaehlung['ohneSenderecht'] > 0 ? sprintf(' (%d aktive ohne Senderecht)', $zaehlung['ohneSenderecht']) : '',
        ));

        if (1 === $anzahlEmpfaenger) {
            $stil->writeln('  <comment>Nur ein Empfänger: Eine verteilte Nachricht geht dann allein an diese Adresse.</comment>');
        }

        if (0 === $anzahlEmpfaenger) {
            $stil->writeln('  <comment>Kein Empfänger: Es würde nichts verteilt.</comment>');
        }
    }
}
