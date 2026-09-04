<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Model;

use Contao\Model;
use Contao\Model\Collection;

/**
 * Eine Mailingliste samt Postfach- und Versandeinstellungen.
 *
 * Contao erzeugt die Eigenschaften zur Laufzeit aus den Spalten der Tabelle;
 * die folgenden Anmerkungen dienen nur der Codevervollständigung. Die
 * Kennwortspalten enthalten den mit
 * {@see \Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher}
 * verschlüsselten Wert und dürfen nicht unmittelbar verwendet werden.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $titel
 * @property string $adresse
 * @property string $beschreibung
 * @property string $imapHost
 * @property int    $imapPort
 * @property string $imapVerschluesselung
 * @property string $imapBenutzer
 * @property string $imapKennwort
 * @property string $imapOrdner
 * @property string $imapOrdnerErledigt
 * @property string $imapZertifikat
 * @property string $imapNachbehandlung
 * @property string $smtpHost
 * @property int    $smtpPort
 * @property string $smtpVerschluesselung
 * @property string $smtpBenutzer
 * @property string $smtpKennwort
 * @property string $betreffPraefix
 * @property string $antwortAn
 * @property string $anhaengeUebernehmen
 * @property string $fussnote
 * @property string $aufnahmeKennung
 * @property string $abmeldeKennung
 * @property string $benachrichtigung
 * @property string $ablehnungSenden
 * @property string $ablehnungText
 * @property string $bestaetigungText
 * @property int    $pruefintervall
 * @property int    $hoechstzahl
 * @property int    $letztePruefung
 * @property string $published
 *
 * @method static MailinglistenModel|null findByPk($id, array $opt = array())
 * @method static MailinglistenModel|null findOneBy($col, $val, array $opt = array())
 * @method static Collection|MailinglistenModel[]|null findAll(array $opt = array())
 * @method static Collection|MailinglistenModel[]|null findBy($col, $val, array $opt = array())
 */
class MailinglistenModel extends Model
{
    /**
     * Name der zugehörigen Datenbanktabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_mailinglisten';

    /**
     * Findet alle veröffentlichten Listen, sortiert nach Titel.
     *
     * Der Cronjob benutzt diese Methode als Einstiegspunkt. Nicht
     * veröffentlichte Listen bleiben unangetastet — ihr Postfach wird also
     * auch nicht geleert, und die dort liegenden Nachrichten werden nach dem
     * Wiedereinschalten der Reihe nach abgearbeitet.
     *
     * @param array<string, mixed> $opt Zusätzliche Abfrageoptionen von Contao
     *
     * @return Collection|MailinglistenModel[]|null Die Listen, oder null wenn
     *                                             keine veröffentlicht ist
     */
    public static function findAktive(array $opt = [])
    {
        $opt['order'] ??= 'titel';

        return static::findBy(['published=?'], ['1'], $opt);
    }

    /**
     * Sagt, ob die Liste laut ihrem Prüfintervall wieder an der Reihe ist.
     *
     * Der Cronjob läuft im Minutentakt; welche Liste dabei tatsächlich
     * abgefragt wird, entscheidet dieses Verfahren. So lassen sich Listen
     * unterschiedlich häufig prüfen, obwohl der Dienst-Tag nur ein einziges,
     * festes Intervall kennt.
     *
     * Ein Prüfintervall von 0 oder weniger gilt als „bei jedem Lauf".
     *
     * @param int|null $jetzt Zeitstempel für den Vergleich; null nimmt die
     *                        aktuelle Zeit. Der Parameter existiert für die
     *                        Tests.
     *
     * @return bool true, wenn das Postfach jetzt abgefragt werden soll
     */
    public function istFaellig(?int $jetzt = null): bool
    {
        $intervall = (int) $this->pruefintervall;

        if ($intervall <= 0) {
            return true;
        }

        return ($jetzt ?? time()) >= (int) $this->letztePruefung + $intervall * 60;
    }
}
