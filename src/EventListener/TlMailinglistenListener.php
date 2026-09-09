<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\System;
use Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher;

/**
 * Rückrufe für den Data Container der Mailinglisten.
 *
 * Die Klasse erbt bewusst **nicht** von `Contao\Backend`. Deren Konstruktor ist
 * in Contao 4.13 `protected` und in 5.7 `public`; eine erbende Klasse braucht
 * dann einen eigenen öffentlichen Konstruktor, sonst lässt sie sich unter 4.13
 * nicht von außen erzeugen. Als reiner Dienst mit Dependency Injection stellt
 * sich die Frage gar nicht erst.
 */
class TlMailinglistenListener
{
    /**
     * Was im Kennwortfeld steht, solange ein Kennwort gespeichert ist.
     *
     * Der Platzhalter ist der Kern des Verfahrens: Das echte Kennwort verlässt
     * den Server nie, auch nicht in einem `type="password"`-Feld, dessen Wert
     * sich im Quelltext der Seite auslesen ließe.
     */
    private const PLATZHALTER = '********';

    /**
     * @param Geheimspeicher $geheimspeicher Verschlüsselt die Kennwörter vor
     *                                       dem Schreiben in die Datenbank
     */
    public function __construct(private readonly Geheimspeicher $geheimspeicher)
    {
    }

    /**
     * Weist beim Öffnen der Liste auf fehlende Voraussetzungen hin.
     *
     * Geprüft wird nur, was sich sonst erst im Cron-Lauf bemerkbar machen
     * würde — und dort in einer Protokollzeile, die niemand liest. Die Meldung
     * erscheint als Hinweis im Backend, blockiert aber nichts: Eine Liste soll
     * sich auch dann anlegen lassen, wenn der Zugang erst später eingetragen
     * wird.
     *
     * @param DataContainer|null $dc Der Data Container; wird nicht ausgewertet,
     *                               gehört aber zur Signatur des Rückrufs
     *
     * @return void
     */
    public function pruefeVoraussetzungen(?DataContainer $dc = null): void
    {
        System::loadLanguageFile('tl_mailinglisten');

        if (!\extension_loaded('sodium')) {
            Message::addError($GLOBALS['TL_LANG']['tl_mailinglisten']['fehltSodium'] ?? 'Die PHP-Erweiterung "sodium" fehlt. Die Postfach-Kennwörter können nicht verschlüsselt werden.');
        }

        // Die Bibliothek für den IMAP-Zugriff wird über Composer geliefert.
        // Fehlt sie, wurde das Bundle von Hand ins vendor-Verzeichnis kopiert.
        if (!class_exists(\Webklex\PHPIMAP\ClientManager::class)) {
            Message::addError($GLOBALS['TL_LANG']['tl_mailinglisten']['fehltImap'] ?? 'Das Paket "webklex/php-imap" ist nicht installiert. Es werden keine Nachrichten abgeholt.');
        }
    }

    /**
     * Beschränkt die Sicht und die Möglichkeiten auf die erlaubten Listen.
     *
     * Aufgebaut wie die Rechteprüfung der Nachrichtenarchive im Kern, mit einem
     * Unterschied: Statt `BackendUser::hasAccess()` werden die Felder
     * unmittelbar ausgewertet. Die Methode löst seit Contao 5.2 eine
     * Deprecation aus und entfällt in Contao 6 — ein Bundle, das 4.13 und 5
     * zugleich bedient, käme damit in beiden Fassungen nur mit Warnungen durch.
     *
     * Wer eine Liste sehen darf, sieht auch deren Teilnehmer und deren
     * Verlauf. Eine feinere Aufteilung wäre bei drei Tabellen, die ohne
     * einander sinnlos sind, mehr Verwaltung als Nutzen.
     *
     * @param DataContainer|null $dc Der Data Container; wird nicht ausgewertet,
     *                               gehört aber zur Signatur des Rückrufs
     *
     * @return void
     *
     * @throws AccessDeniedException Wenn der Benutzer eine Handlung versucht,
     *                               die ihm nicht zusteht
     */
    public function pruefeRechte(?DataContainer $dc = null): void
    {
        $benutzer = BackendUser::getInstance();

        if ($benutzer->isAdmin) {
            return;
        }

        // Ohne zugewiesene Liste bleibt die Übersicht leer. Die 0 ist nötig,
        // weil ein leeres Feld in Contao als „keine Einschränkung“ gilt und
        // damit alles zeigen würde.
        $erlaubt = \is_array($benutzer->mailinglisten) && $benutzer->mailinglisten
            ? array_map('intval', $benutzer->mailinglisten)
            : [0];

        $GLOBALS['TL_DCA']['tl_mailinglisten']['list']['sorting']['root'] = $erlaubt;

        if (!$this->darf($benutzer, 'create')) {
            $GLOBALS['TL_DCA']['tl_mailinglisten']['config']['closed'] = true;
            $GLOBALS['TL_DCA']['tl_mailinglisten']['config']['notCreatable'] = true;
            $GLOBALS['TL_DCA']['tl_mailinglisten']['config']['notCopyable'] = true;
        }

        if (!$this->darf($benutzer, 'delete')) {
            $GLOBALS['TL_DCA']['tl_mailinglisten']['config']['notDeletable'] = true;
        }

        $aktion = (string) Input::get('act');

        switch ($aktion) {
            case '':
            case 'select':
                break;

            case 'create':
                if (!$this->darf($benutzer, 'create')) {
                    throw new AccessDeniedException('Keine Berechtigung, eine Mailingliste anzulegen.');
                }
                break;

            case 'edit':
            case 'copy':
            case 'delete':
            case 'show':
            case 'toggle':
                if (!\in_array((int) Input::get('id'), $erlaubt, true)) {
                    throw new AccessDeniedException(sprintf('Keine Berechtigung für die Mailingliste ID %s.', Input::get('id')));
                }

                if ('delete' === $aktion && !$this->darf($benutzer, 'delete')) {
                    throw new AccessDeniedException(sprintf('Keine Berechtigung, die Mailingliste ID %s zu löschen.', Input::get('id')));
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'copyAll':
                // Bei den Sammelaktionen steht die Auswahl in der Sitzung. Sie
                // wird auf die erlaubten Listen eingedampft, statt die Aktion
                // abzuweisen — sonst scheiterte eine Auswahl schon daran, dass
                // eine einzige fremde Liste darin vorkommt.
                $this->auswahlBeschraenken($erlaubt, 'deleteAll' === $aktion && !$this->darf($benutzer, 'delete'));
                break;

            default:
                throw new AccessDeniedException(sprintf('Keine Berechtigung für die Aktion "%s".', $aktion));
        }
    }

    /**
     * Sagt, ob ein Benutzer ein bestimmtes Recht an den Listen hat.
     *
     * Ausgewertet wird das Feld `mailinglistenp`, in dem Contao die
     * angekreuzten Rechte als Feld ablegt. Ein Administrator hat immer alle.
     *
     * @param BackendUser $benutzer Der angemeldete Benutzer
     * @param string      $recht    'create' oder 'delete'
     *
     * @return bool true, wenn das Recht vorliegt
     */
    private function darf(BackendUser $benutzer, string $recht): bool
    {
        if ($benutzer->isAdmin) {
            return true;
        }

        return \is_array($benutzer->mailinglistenp) && \in_array($recht, $benutzer->mailinglistenp, true);
    }

    /**
     * Streicht aus einer Sammelauswahl alles, was dem Benutzer nicht zusteht.
     *
     * Die Auswahl liegt in der Backend-Sitzung. Der Dienst `session` ist in
     * Contao 5 entfallen; der Weg über `request_stack` funktioniert in beiden
     * Fassungen.
     *
     * @param array<int, int> $erlaubt Die zugänglichen Listen-IDs
     * @param bool            $alles   Wenn true, wird die Auswahl vollständig
     *                                 geleert — etwa beim Sammellöschen ohne
     *                                 Löschrecht
     *
     * @return void
     */
    private function auswahlBeschraenken(array $erlaubt, bool $alles): void
    {
        $sitzung = System::getContainer()->get('request_stack')->getSession();
        $daten = $sitzung->all();

        $daten['CURRENT']['IDS'] = $alles
            ? []
            : array_intersect(array_map('intval', (array) ($daten['CURRENT']['IDS'] ?? [])), $erlaubt);

        $sitzung->replace($daten);
    }

    /**
     * Ergänzt die Zeile einer Mailingliste um Teilnehmerzahl und letzten Lauf.
     *
     * Beides sind die Angaben, nach denen bei einer stillen Liste als erstes
     * gefragt wird: Kommen überhaupt Teilnehmer zusammen, und läuft der Cron?
     *
     * @param array<string, mixed> $row   Der Datensatz der Liste
     * @param string               $label Die von Contao aus `list.label.format`
     *                                    zusammengesetzte Beschriftung
     * @param DataContainer|null   $dc    Der Data Container, hier ungenutzt
     * @param array<int, string>   $args  Die eingesetzten Feldwerte, ungenutzt
     *
     * @return string Die Beschriftung mit angehängtem Zusatz
     */
    public function beschriftung(array $row, string $label, ?DataContainer $dc = null, array $args = []): string
    {
        $db = Database::getInstance();

        $anzahl = (int) $db
            ->prepare('SELECT COUNT(*) AS anzahl FROM tl_mailinglisten_abonnent WHERE pid=? AND status=?')
            ->execute($row['id'], 'aktiv')
            ->anzahl
        ;

        $offen = (int) $db
            ->prepare('SELECT COUNT(*) AS anzahl FROM tl_mailinglisten_abonnent WHERE pid=? AND status=?')
            ->execute($row['id'], 'beantragt')
            ->anzahl
        ;

        $zusatz = sprintf('%d Teilnehmer', $anzahl);

        if ($offen > 0) {
            $zusatz .= sprintf(', %d Antrag%s offen', $offen, 1 === $offen ? '' : 'e');
        }

        $zusatz .= ', zuletzt geprüft: '.($row['letztePruefung'] ? date('d.m.Y H:i', (int) $row['letztePruefung']) : 'nie');

        return $label.' <span style="color:#999;padding-left:6px">('.$zusatz.')</span>';
    }

    /**
     * Zeigt statt des gespeicherten Kennworts einen Platzhalter an.
     *
     * Der Rückruf hängt an beiden Kennwortfeldern. Ein leeres Feld bleibt leer,
     * damit sichtbar ist, dass noch kein Kennwort hinterlegt wurde.
     *
     * @param mixed              $wert Der verschlüsselte Wert aus der Datenbank
     * @param DataContainer|null $dc   Der Data Container, hier ungenutzt
     *
     * @return string Der Platzhalter, oder '' wenn kein Kennwort gesetzt ist
     */
    public function kennwortVerbergen(mixed $wert, ?DataContainer $dc = null): string
    {
        return '' !== (string) $wert ? self::PLATZHALTER : '';
    }

    /**
     * Verschlüsselt das eingegebene IMAP-Kennwort.
     *
     * @param mixed         $wert Der Wert aus dem Formular
     * @param DataContainer $dc   Liefert die ID des Datensatzes
     *
     * @return string Der zu speichernde, verschlüsselte Wert
     */
    public function imapKennwortSpeichern(mixed $wert, DataContainer $dc): string
    {
        return $this->kennwortSpeichern((string) $wert, $dc, 'imapKennwort');
    }

    /**
     * Verschlüsselt das eingegebene SMTP-Kennwort.
     *
     * @param mixed         $wert Der Wert aus dem Formular
     * @param DataContainer $dc   Liefert die ID des Datensatzes
     *
     * @return string Der zu speichernde, verschlüsselte Wert
     */
    public function smtpKennwortSpeichern(mixed $wert, DataContainer $dc): string
    {
        return $this->kennwortSpeichern((string) $wert, $dc, 'smtpKennwort');
    }

    /**
     * Entscheidet, was aus einer Kennworteingabe wird.
     *
     * Drei Fälle sind zu unterscheiden. Steht der Platzhalter im Feld, hat
     * niemand etwas geändert und der gespeicherte Wert bleibt — er wird
     * ausdrücklich aus der Datenbank zurückgeholt, weil der Rückgabewert des
     * Rückrufs sonst den Platzhalter selbst speichern würde. Ist das Feld leer,
     * soll das Kennwort weg. In allen anderen Fällen liegt ein neues Kennwort
     * vor und wird verschlüsselt.
     *
     * Möglich ist das nur, weil während des save_callback in der Datenbank noch
     * der **alte** Wert genau dieses Feldes steht — in Contao 4.13 wie in 5.7,
     * trotz unterschiedlicher Speicher-Reihenfolge.
     *
     * @param string        $wert  Der Wert aus dem Formular
     * @param DataContainer $dc    Liefert die ID des Datensatzes
     * @param string        $spalte Name der Datenbankspalte
     *
     * @return string Der zu speichernde Wert
     */
    private function kennwortSpeichern(string $wert, DataContainer $dc, string $spalte): string
    {
        if (self::PLATZHALTER === $wert) {
            $alt = Database::getInstance()
                ->prepare('SELECT '.$spalte.' AS wert FROM tl_mailinglisten WHERE id=?')
                ->execute($dc->id)
            ;

            return $alt->numRows > 0 ? (string) $alt->wert : '';
        }

        if ('' === trim($wert)) {
            return '';
        }

        // Ein bereits verschlüsselter Wert kann nur aus einem Import stammen;
        // ihn ein zweites Mal zu verschlüsseln würde ihn unbrauchbar machen.
        if ($this->geheimspeicher->istVerschluesselt($wert)) {
            return $wert;
        }

        return $this->geheimspeicher->verschluesseln($wert);
    }
}
