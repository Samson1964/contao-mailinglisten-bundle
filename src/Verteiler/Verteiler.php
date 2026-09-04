<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Verteiler;

use Psr\Log\LoggerInterface;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteProtokollModel;
use Schachbulle\ContaoMailinglistenBundle\Postfach\EingehendeNachricht;
use Schachbulle\ContaoMailinglistenBundle\Postfach\Nachbehandlung;
use Schachbulle\ContaoMailinglistenBundle\Postfach\PostfachFehler;
use Schachbulle\ContaoMailinglistenBundle\Postfach\PostfachLeserInterface;
use Schachbulle\ContaoMailinglistenBundle\Postfach\Postfachzugang;
use Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher;
use Schachbulle\ContaoMailinglistenBundle\Versand\NachrichtenBauer;
use Schachbulle\ContaoMailinglistenBundle\Versand\VersandDienst;

/**
 * Das Herzstück: holt Nachrichten ab und entscheidet, was mit ihnen geschieht.
 *
 * Die Reihenfolge der Prüfungen ist wichtig und nicht beliebig:
 *
 *  1. Nachrichten, die diese Liste selbst versendet hat, werden verworfen.
 *     Ohne diese Prüfung entsteht eine Schleife, sobald die Listenadresse
 *     selbst Teilnehmer ist — und das kommt bei „Antwort an alle" ständig vor.
 *  2. Maschinelle Antworten werden verworfen. Eine einzige Abwesenheitsnotiz
 *     an eine Liste mit fünfzig Teilnehmern erzeugt sonst fünfzig weitere.
 *  3. Bereits verarbeitete Message-IDs werden verworfen. Bricht ein Cron-Lauf
 *     zwischen Versand und Lesezeichen ab, bekäme sonst jeder die Nachricht
 *     ein zweites Mal.
 *  4. Erst dann die Abmeldung, weil sie auch für Teilnehmer gelten muss.
 *  5. Dann die Verteilung für berechtigte Teilnehmer.
 *  6. Dann der Aufnahmeantrag für alle übrigen.
 *  7. Zuletzt die Ablehnung.
 *
 * Die Prüfung auf Abmeldung steht vor der Verteilung, die auf Aufnahme
 * dahinter: Ein Teilnehmer, der versehentlich mit dem Aufnahmewort schreibt,
 * soll seine Nachricht verteilt bekommen, nicht einen zweiten Antrag stellen.
 */
class Verteiler
{
    /**
     * @param PostfachLeserInterface $leser          Holt die Nachrichten aus dem IMAP-Postfach
     * @param VersandDienst          $versand        Verschickt die ausgehenden Nachrichten
     * @param NachrichtenBauer       $bauer          Baut Verteilung, Ablehnung und Bestätigungen
     * @param Kennungspruefer        $kennung        Erkennt die Steuerwörter im Betreff
     * @param Geheimspeicher         $geheimspeicher Entschlüsselt das IMAP-Kennwort
     * @param LoggerInterface        $logger         Nimmt Fehler auf, die keine Ausnahme wert sind
     */
    public function __construct(
        private readonly PostfachLeserInterface $leser,
        private readonly VersandDienst $versand,
        private readonly NachrichtenBauer $bauer,
        private readonly Kennungspruefer $kennung,
        private readonly Geheimspeicher $geheimspeicher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Arbeitet das Postfach einer Mailingliste ab.
     *
     * Der Zeitpunkt der Prüfung wird auch dann festgehalten, wenn der Abruf
     * scheitert. Sonst versuchte der Cronjob bei einem dauerhaft nicht
     * erreichbaren Server im Minutentakt eine neue Verbindung und füllte das
     * Fehlerprotokoll.
     *
     * @param MailinglisteModel $liste Die abzuarbeitende Liste
     *
     * @return Verteilergebnis Die Zählung dieses Durchgangs. Bei einem
     *                         Postfachfehler steht dort ein Fehler und sonst
     *                         nichts; die Ausnahme wird nicht weitergereicht,
     *                         damit die übrigen Listen an die Reihe kommen.
     */
    public function listeVerarbeiten(MailinglisteModel $liste): Verteilergebnis
    {
        $ergebnis = new Verteilergebnis();

        try {
            $this->leser->verarbeiten(
                $this->zugangVon($liste),
                max(1, (int) $liste->hoechstzahl),
                function (EingehendeNachricht $eingang) use ($liste, &$ergebnis): Nachbehandlung {
                    [$nachbehandlung, $teilergebnis] = $this->nachrichtVerarbeiten($liste, $eingang);
                    $ergebnis = $ergebnis->plus($teilergebnis);

                    return $nachbehandlung;
                },
            );
        } catch (PostfachFehler $e) {
            $this->logger->error(sprintf('Mailingliste "%s": %s', $liste->titel, $e->getMessage()));

            MailinglisteProtokollModel::protokollieren(
                (int) $liste->id,
                '',
                '',
                '',
                MailinglisteProtokollModel::AKTION_FEHLER,
                0,
                $e->getMessage(),
            );

            $ergebnis = $ergebnis->plus(new Verteilergebnis(fehler: 1));
        } finally {
            $liste->letztePruefung = time();
            $liste->save();
        }

        return $ergebnis;
    }

    /**
     * Entscheidet über eine einzelne Nachricht und führt die Entscheidung aus.
     *
     * @param MailinglisteModel   $liste   Die verarbeitende Liste
     * @param EingehendeNachricht $eingang Die eingegangene Nachricht
     *
     * @return array{0: Nachbehandlung, 1: Verteilergebnis} Was mit der
     *                                                      Nachricht im
     *                                                      Postfach geschehen
     *                                                      soll, und die
     *                                                      Zählung dazu
     */
    private function nachrichtVerarbeiten(MailinglisteModel $liste, EingehendeNachricht $eingang): array
    {
        $pid = (int) $liste->id;
        $gelesen = new Verteilergebnis(gelesen: 1);

        try {
            // 1. Nachrichten, die diese Liste selbst verschickt hat.
            if ($eingang->kopfzeile(NachrichtenBauer::KOPF_KENNUNG) === (string) $liste->id) {
                return [$this->nachbehandlungVon($liste), $gelesen->plus(new Verteilergebnis(ignoriert: 1))];
            }

            // 2. Abwesenheitsnotizen, Unzustellbarkeitsberichte und ähnliches.
            if ($eingang->istAutomatisch()) {
                MailinglisteProtokollModel::protokollieren(
                    $pid,
                    $eingang->messageId,
                    $eingang->absender,
                    $eingang->betreff,
                    MailinglisteProtokollModel::AKTION_IGNORIERT,
                    0,
                    'Maschinell erzeugte Nachricht.',
                );

                return [$this->nachbehandlungVon($liste), $gelesen->plus(new Verteilergebnis(ignoriert: 1))];
            }

            // 3. Schon einmal behandelt.
            if (MailinglisteProtokollModel::istBekannt($pid, $eingang->messageId)) {
                return [$this->nachbehandlungVon($liste), $gelesen->plus(new Verteilergebnis(ignoriert: 1))];
            }

            $teilnehmer = MailinglisteAbonnentModel::findByListeUndEmail($pid, $eingang->absender);

            // 4. Abmeldung — muss auch für aktive Teilnehmer greifen.
            if (null !== $teilnehmer && $this->kennung->trifftZu($eingang->betreff, (string) $liste->abmeldeKennung)) {
                return [$this->abmelden($liste, $eingang, $teilnehmer), $gelesen->plus(new Verteilergebnis(abmeldungen: 1))];
            }

            // 5. Der Regelfall: ein berechtigter Teilnehmer schreibt an die Liste.
            if (null !== $teilnehmer && $teilnehmer->darfEinreichen()) {
                $this->verteilen($liste, $eingang);

                return [$this->nachbehandlungVon($liste), $gelesen->plus(new Verteilergebnis(verteilt: 1))];
            }

            // 6. Aufnahmeantrag eines Fremden — oder eines Gesperrten, der
            //    dann aber nicht wieder aufgenommen wird.
            if ($this->kennung->trifftZu($eingang->betreff, (string) $liste->aufnahmeKennung)) {
                return [$this->antragAufnehmen($liste, $eingang, $teilnehmer), $gelesen->plus(new Verteilergebnis(antraege: 1))];
            }

            // 7. Alles andere wird abgewiesen.
            return [$this->ablehnen($liste, $eingang, $teilnehmer), $gelesen->plus(new Verteilergebnis(abgelehnt: 1))];
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Mailingliste "%s": Nachricht von "%s" konnte nicht verarbeitet werden: %s', $liste->titel, $eingang->absender, $e->getMessage()));

            MailinglisteProtokollModel::protokollieren(
                $pid,
                $eingang->messageId,
                $eingang->absender,
                $eingang->betreff,
                MailinglisteProtokollModel::AKTION_FEHLER,
                0,
                $e->getMessage(),
            );

            // Die Nachricht bleibt ungelesen liegen. Der Protokolleintrag mit
            // der Message-ID sorgt dafür, dass der nächste Lauf sie als
            // bekannt erkennt und nicht in dieselbe Falle rennt.
            return [Nachbehandlung::Behalten, $gelesen->plus(new Verteilergebnis(fehler: 1))];
        }
    }

    /**
     * Schickt die Nachricht an alle Empfänger der Liste.
     *
     * Jeder Empfänger bekommt eine eigene Ausfertigung. Ein gemeinsames BCC
     * wäre schneller, aber dann trüge jede Nachricht dieselbe Message-ID, und
     * Mailprogramme, die nach ihr aussortieren, zeigten die Nachricht nur
     * einmal an. Außerdem lässt sich so später eine persönliche Fußzeile
     * ergänzen.
     *
     * Der Verfasser selbst bekommt seine Nachricht mit — so sieht er, dass sie
     * angekommen ist, und hat den Verlauf vollständig im eigenen Postfach.
     *
     * @param MailinglisteModel   $liste   Die verteilende Liste
     * @param EingehendeNachricht $eingang Die zu verteilende Nachricht
     *
     * @return int Anzahl der erfolgreich versendeten Ausfertigungen
     */
    private function verteilen(MailinglisteModel $liste, EingehendeNachricht $eingang): int
    {
        $empfaenger = MailinglisteAbonnentModel::findEmpfaenger((int) $liste->id);
        $anzahl = 0;
        $fehlgeschlagen = 0;

        if (null !== $empfaenger) {
            foreach ($empfaenger as $einzelner) {
                if ($this->versand->versenden($liste, $this->bauer->verteilung($liste, $eingang, $einzelner))) {
                    ++$anzahl;
                } else {
                    ++$fehlgeschlagen;
                }
            }
        }

        MailinglisteProtokollModel::protokollieren(
            (int) $liste->id,
            $eingang->messageId,
            $eingang->absender,
            $eingang->betreff,
            MailinglisteProtokollModel::AKTION_VERTEILT,
            $anzahl,
            $fehlgeschlagen > 0 ? sprintf('%d Zustellungen schlugen fehl.', $fehlgeschlagen) : '',
        );

        return $anzahl;
    }

    /**
     * Legt einen Aufnahmeantrag an und benachrichtigt die Beteiligten.
     *
     * Ein bereits gesperrter Bewerber wird nicht wieder auf „beantragt"
     * gesetzt — sonst ließe sich eine Sperre durch beharrliches Beantragen
     * aushebeln. Er bekommt dieselbe Bestätigung wie alle anderen, damit die
     * Sperre nicht durch das Ausbleiben einer Antwort erkennbar wird.
     *
     * @param MailinglisteModel              $liste     Die beantragte Liste
     * @param EingehendeNachricht            $eingang   Der Antrag
     * @param MailinglisteAbonnentModel|null $vorhanden Ein bereits bestehender
     *                                                  Eintrag zu dieser
     *                                                  Adresse, oder null
     *
     * @return Nachbehandlung Was mit der Nachricht im Postfach geschehen soll
     */
    private function antragAufnehmen(MailinglisteModel $liste, EingehendeNachricht $eingang, ?MailinglisteAbonnentModel $vorhanden): Nachbehandlung
    {
        $meldung = 'Antrag vorgemerkt.';

        if (null === $vorhanden) {
            $teilnehmer = new MailinglisteAbonnentModel();
            $teilnehmer->pid = (int) $liste->id;
            $teilnehmer->tstamp = time();
            $teilnehmer->email = $eingang->absender;
            $teilnehmer->vorname = '';
            $teilnehmer->nachname = $eingang->absenderName;
            $teilnehmer->status = MailinglisteAbonnentModel::STATUS_BEANTRAGT;
            $teilnehmer->darfSenden = '1';
            $teilnehmer->darfEmpfangen = '1';
            $teilnehmer->beigetreten = time();
            $teilnehmer->notiz = sprintf('Antrag per E-Mail am %s, Betreff: %s', date('d.m.Y H:i'), $eingang->betreff);
            $teilnehmer->save();
        } elseif (MailinglisteAbonnentModel::STATUS_GESPERRT === $vorhanden->status) {
            $meldung = 'Antrag einer gesperrten Adresse, Eintrag unverändert gelassen.';
        } else {
            $meldung = 'Antrag einer bereits eingetragenen Adresse, Eintrag unverändert gelassen.';
        }

        $this->versand->versenden($liste, $this->bauer->antragsBestaetigung($liste, $eingang));

        $betreuer = trim((string) $liste->benachrichtigung);

        if ('' !== $betreuer && null === $vorhanden) {
            foreach (array_filter(array_map('trim', explode(',', $betreuer))) as $adresse) {
                $this->versand->versenden($liste, $this->bauer->betreuerBenachrichtigung($liste, $eingang, $adresse));
            }
        }

        MailinglisteProtokollModel::protokollieren(
            (int) $liste->id,
            $eingang->messageId,
            $eingang->absender,
            $eingang->betreff,
            MailinglisteProtokollModel::AKTION_ANTRAG,
            0,
            $meldung,
        );

        return $this->nachbehandlungVon($liste);
    }

    /**
     * Trägt einen Teilnehmer auf eigenen Wunsch aus.
     *
     * Der Eintrag wird gelöscht und nicht auf „gesperrt" gesetzt: Wer sich
     * abmeldet, soll sich später ohne Hürde wieder anmelden können. Eine
     * Sperre bleibt der Betreuung im Backend vorbehalten.
     *
     * @param MailinglisteModel         $liste      Die verlassene Liste
     * @param EingehendeNachricht       $eingang    Die Abmeldung
     * @param MailinglisteAbonnentModel $teilnehmer Der auszutragende Eintrag
     *
     * @return Nachbehandlung Was mit der Nachricht im Postfach geschehen soll
     */
    private function abmelden(MailinglisteModel $liste, EingehendeNachricht $eingang, MailinglisteAbonnentModel $teilnehmer): Nachbehandlung
    {
        // Eine gesperrte Adresse bleibt gesperrt; sie durch eine Abmeldung
        // löschen zu lassen, wäre ein bequemer Weg, die Sperre loszuwerden.
        if (MailinglisteAbonnentModel::STATUS_GESPERRT === $teilnehmer->status) {
            $meldung = 'Abmeldung einer gesperrten Adresse, Eintrag behalten.';
        } else {
            $teilnehmer->delete();
            $meldung = 'Teilnehmer ausgetragen.';
        }

        $this->versand->versenden($liste, $this->bauer->abmeldeBestaetigung($liste, $eingang));

        MailinglisteProtokollModel::protokollieren(
            (int) $liste->id,
            $eingang->messageId,
            $eingang->absender,
            $eingang->betreff,
            MailinglisteProtokollModel::AKTION_ABMELDUNG,
            0,
            $meldung,
        );

        return $this->nachbehandlungVon($liste);
    }

    /**
     * Weist eine Nachricht ab, deren Absender nicht zur Liste gehört.
     *
     * Ob überhaupt geantwortet wird, entscheidet die Einstellung der Liste.
     * Bei einer Adresse, die viel Spam bekommt, ist Schweigen die bessere
     * Wahl: Jede Ablehnung an eine gefälschte Absenderadresse belästigt einen
     * Unbeteiligten und schadet dem Ruf des eigenen Mailservers.
     *
     * @param MailinglisteModel              $liste      Die ablehnende Liste
     * @param EingehendeNachricht            $eingang    Die abgewiesene Nachricht
     * @param MailinglisteAbonnentModel|null $teilnehmer Der Eintrag zur Adresse,
     *                                                    falls einer besteht —
     *                                                    für die Begründung im
     *                                                    Protokoll
     *
     * @return Nachbehandlung Was mit der Nachricht im Postfach geschehen soll
     */
    private function ablehnen(MailinglisteModel $liste, EingehendeNachricht $eingang, ?MailinglisteAbonnentModel $teilnehmer): Nachbehandlung
    {
        $grund = match (true) {
            null === $teilnehmer => 'Absender gehört nicht zur Liste.',
            MailinglisteAbonnentModel::STATUS_BEANTRAGT === $teilnehmer->status => 'Aufnahmeantrag ist noch nicht freigegeben.',
            MailinglisteAbonnentModel::STATUS_GESPERRT === $teilnehmer->status => 'Absender ist gesperrt.',
            default => 'Absender hat kein Senderecht.',
        };

        if ($liste->ablehnungSenden) {
            $this->versand->versenden($liste, $this->bauer->ablehnung($liste, $eingang));
        }

        MailinglisteProtokollModel::protokollieren(
            (int) $liste->id,
            $eingang->messageId,
            $eingang->absender,
            $eingang->betreff,
            MailinglisteProtokollModel::AKTION_ABGELEHNT,
            0,
            $grund,
        );

        return $this->nachbehandlungVon($liste);
    }

    /**
     * Übersetzt die Einstellung der Liste in eine Nachbehandlung.
     *
     * @param MailinglisteModel $liste Die Liste mit der Einstellung
     *
     * @return Nachbehandlung Der zugehörige Enum-Wert; bei einem unbekannten
     *                        Wert das ungefährlichste Verhalten, nämlich das
     *                        bloße Setzen des Lesezeichens
     */
    private function nachbehandlungVon(MailinglisteModel $liste): Nachbehandlung
    {
        return match ($liste->imapNachbehandlung) {
            'verschieben' => Nachbehandlung::Verschieben,
            'loeschen' => Nachbehandlung::Loeschen,
            default => Nachbehandlung::Gelesen,
        };
    }

    /**
     * Baut aus dem Datensatz der Liste die Zugangsdaten des Postfachs.
     *
     * Das Kennwort wird hier — und nur hier — entschlüsselt. Es lebt danach
     * ausschließlich im Wertobjekt und geht nicht durch weitere Schichten.
     *
     * @param MailinglisteModel $liste Die Liste mit den IMAP-Angaben
     *
     * @return Postfachzugang Die Zugangsdaten für den Leser
     */
    private function zugangVon(MailinglisteModel $liste): Postfachzugang
    {
        return new Postfachzugang(
            host: trim((string) $liste->imapHost),
            port: (int) $liste->imapPort ?: 993,
            verschluesselung: (string) $liste->imapVerschluesselung,
            benutzer: trim((string) $liste->imapBenutzer),
            kennwort: $this->geheimspeicher->entschluesseln((string) $liste->imapKennwort),
            ordner: trim((string) $liste->imapOrdner) ?: 'INBOX',
            zertifikatPruefen: (bool) $liste->imapZertifikat,
            ordnerErledigt: trim((string) $liste->imapOrdnerErledigt),
        );
    }
}
