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
