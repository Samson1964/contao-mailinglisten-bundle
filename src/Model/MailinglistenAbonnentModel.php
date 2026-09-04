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
 * Ein Teilnehmer einer Mailingliste.
 *
 * Teilnehmer sind bewusst nicht an Contao-Mitglieder gebunden: An einer
 * Verteilerliste nehmen häufig Leute teil, die auf der Webseite kein Konto
 * haben. Wer beides verbinden will, kann die Adressen über einen eigenen
 * Import abgleichen.
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $tstamp
 * @property string $email
 * @property string $vorname
 * @property string $nachname
 * @property string $status
 * @property string $darfSenden
 * @property string $darfEmpfangen
 * @property int    $beigetreten
 * @property string $notiz
 * @property string $token
 * @property int    $tokenErzeugt
 *
 * @method static MailinglistenAbonnentModel|null findByPk($id, array $opt = array())
 * @method static MailinglistenAbonnentModel|null findOneBy($col, $val, array $opt = array())
 * @method static Collection|MailinglistenAbonnentModel[]|null findAll(array $opt = array())
 * @method static Collection|MailinglistenAbonnentModel[]|null findBy($col, $val, array $opt = array())
 */
class MailinglistenAbonnentModel extends Model
{
    /**
     * Der Teilnehmer ist aufgenommen und nimmt am Verkehr teil.
     */
    public const STATUS_AKTIV = 'aktiv';

    /**
     * Der Teilnehmer hat die Aufnahme beantragt und wartet auf Freigabe.
     *
     * In diesem Zustand bekommt er keine Nachrichten und darf auch keine an
     * die Liste schicken.
     */
    public const STATUS_BEANTRAGT = 'beantragt';

    /**
     * Über das Frontend-Formular eingetragen, aber noch nicht bestätigt.
     *
     * Dieser Zustand steht **vor** `beantragt` und gibt es nur beim Weg über
     * die Webseite: Dort kann jeder eine fremde Adresse eintragen, die
     * Adresse ist also durch nichts belegt. Erst der Klick auf den
     * Bestätigungslink beweist, dass der Eintragende auch Zugriff auf das
     * Postfach hat — dann wird daraus ein `beantragt`, über das die Betreuung
     * entscheidet.
     *
     * Ein Eintrag in diesem Zustand taucht in keiner Verteilung auf und
     * erzeugt auch keine Meldung an die Betreuung.
     */
    public const STATUS_UNBESTAETIGT = 'unbestaetigt';

    /**
     * Der Teilnehmer ist gesperrt.
     *
     * Anders als beim Löschen bleibt der Eintrag erhalten, so dass ein
     * erneuter Aufnahmeantrag derselben Adresse nicht stillschweigend
     * durchgeht.
     */
    public const STATUS_GESPERRT = 'gesperrt';

    /**
     * Name der zugehörigen Datenbanktabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_mailinglisten_abonnent';

    /**
     * Sucht einen Teilnehmer einer Liste anhand seiner E-Mail-Adresse.
     *
     * Der Vergleich läuft über die kleingeschriebene Adresse. Beim Speichern
     * wird die Adresse ebenfalls kleingeschrieben abgelegt, so dass ein
     * einfacher Gleichheitsvergleich genügt und kein `LOWER()` die Nutzung des
     * Indexes verhindert.
     *
     * @param int    $pid   ID der Mailingliste
     * @param string $email Die gesuchte Adresse, Schreibweise beliebig
     *
     * @return MailinglistenAbonnentModel|null Der Teilnehmer in beliebigem
     *                                        Status, oder null
     */
    public static function findByListeUndEmail(int $pid, string $email): ?self
    {
        $email = strtolower(trim($email));

        if ('' === $email) {
            return null;
        }

        return static::findOneBy(['pid=?', 'email=?'], [$pid, $email]);
    }

    /**
     * Liefert alle Teilnehmer, die Nachrichten der Liste bekommen sollen.
     *
     * Ausgeschlossen sind alle nicht freigegebenen Teilnehmer sowie jene, bei
     * denen der Empfang abgeschaltet ist — etwa Leute, die nur einreichen
     * dürfen, oder solche, die vorübergehend pausieren wollen.
     *
     * @param int $pid ID der Mailingliste
     *
     * @return Collection|MailinglistenAbonnentModel[]|null Die Empfänger, oder
     *                                                     null wenn keiner in
     *                                                     Frage kommt
     */
    public static function findEmpfaenger(int $pid)
    {
        return static::findBy(
            ['pid=?', 'status=?', 'darfEmpfangen=?'],
            [$pid, self::STATUS_AKTIV, '1'],
            ['order' => 'email'],
        );
    }

    /**
     * Sucht einen Eintrag anhand seines Bestätigungsmerkmals.
     *
     * Das Merkmal ist über alle Listen hinweg eindeutig, weil es aus dem
     * Zufallsgenerator stammt; die Liste muss deshalb nicht mitgegeben werden.
     * Ein abgelaufenes oder leeres Merkmal findet nichts — ein Link, der
     * wochenlang in einem Postfach liegt, soll nicht mehr wirken.
     *
     * @param string $merkmal Der Wert aus dem Bestätigungslink
     * @param int    $gueltig Wie lange ein Merkmal gilt, in Sekunden
     *
     * @return self|null Der wartende Eintrag, oder null wenn das Merkmal
     *                   unbekannt, abgelaufen oder bereits eingelöst ist
     */
    public static function findByMerkmal(string $merkmal, int $gueltig = 172800): ?self
    {
        $merkmal = trim($merkmal);

        // Ein zu kurzer Wert kann nicht aus dem Zufallsgenerator stammen; die
        // Prüfung hält Rateversuche von der Datenbank fern.
        if (32 > \strlen($merkmal)) {
            return null;
        }

        return static::findOneBy(
            ['token=?', 'status=?', 'tokenErzeugt>?'],
            [$merkmal, self::STATUS_UNBESTAETIGT, time() - $gueltig],
        );
    }

    /**
     * Sagt, ob dieser Teilnehmer an die Liste schreiben darf.
     *
     * Beides muss zutreffen: Der Eintrag ist freigegeben, und das Senderecht
     * ist nicht ausdrücklich entzogen. Ein Teilnehmer im Status „beantragt"
     * darf also noch nichts einreichen, auch wenn das Senderecht gesetzt ist.
     *
     * @return bool true, wenn Nachrichten dieses Absenders verteilt werden
     */
    public function darfEinreichen(): bool
    {
        return self::STATUS_AKTIV === $this->status && $this->darfSenden;
    }
}
