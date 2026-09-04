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
 * Ein Eintrag im Verlauf einer Mailingliste.
 *
 * Das Protokoll erfüllt zwei Aufgaben. Erstens macht es nachvollziehbar, was
 * mit einer eingegangenen Nachricht geschehen ist — gerade Ablehnungen führen
 * sonst zu Rückfragen, die niemand beantworten kann. Zweitens verhindert es
 * über die gespeicherte Message-ID, dass dieselbe Nachricht ein zweites Mal
 * verteilt wird, falls der Cron-Lauf nach dem Versand, aber vor dem Setzen des
 * Lesezeichens abbricht.
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $tstamp
 * @property string $messageId
 * @property string $absender
 * @property string $betreff
 * @property string $aktion
 * @property int    $empfaenger
 * @property string $meldung
 * @property int    $datum
 *
 * @method static MailinglisteProtokollModel|null findByPk($id, array $opt = array())
 * @method static MailinglisteProtokollModel|null findOneBy($col, $val, array $opt = array())
 * @method static Collection|MailinglisteProtokollModel[]|null findAll(array $opt = array())
 * @method static Collection|MailinglisteProtokollModel[]|null findBy($col, $val, array $opt = array())
 */
class MailinglisteProtokollModel extends Model
{
    /**
     * Die Nachricht wurde an die Teilnehmer weitergegeben.
     */
    public const AKTION_VERTEILT = 'verteilt';

    /**
     * Der Absender gehört nicht zur Liste; die Nachricht wurde abgewiesen.
     */
    public const AKTION_ABGELEHNT = 'abgelehnt';

    /**
     * Die Nachricht war ein Aufnahmeantrag.
     */
    public const AKTION_ANTRAG = 'antrag';

    /**
     * Die Nachricht war eine Abmeldung.
     */
    public const AKTION_ABMELDUNG = 'abmeldung';

    /**
     * Die Nachricht wurde stillschweigend verworfen.
     *
     * Das betrifft maschinelle Antworten, Unzustellbarkeitsberichte und
     * Nachrichten, die diese Liste selbst versendet hat.
     */
    public const AKTION_IGNORIERT = 'ignoriert';

    /**
     * Bei der Verarbeitung trat ein Fehler auf.
     */
    public const AKTION_FEHLER = 'fehler';

    /**
     * Name der zugehörigen Datenbanktabelle.
     *
     * @var string
     */
    protected static $strTable = 'tl_mailingliste_protokoll';

    /**
     * Prüft, ob eine Nachricht mit dieser Message-ID schon behandelt wurde.
     *
     * Eine leere Message-ID gilt nie als bekannt. Nachrichten ohne diesen Kopf
     * sind selten und stammen fast immer von fehlerhaften Absendern; sie
     * einfach durchzulassen ist harmloser, als sie alle für dieselbe Nachricht
     * zu halten und ab der zweiten zu verwerfen.
     *
     * @param int    $pid       ID der Mailingliste
     * @param string $messageId Die Message-ID ohne spitze Klammern
     *
     * @return bool true, wenn zu dieser Liste bereits ein Eintrag mit dieser
     *              Message-ID vorliegt
     */
    public static function istBekannt(int $pid, string $messageId): bool
    {
        if ('' === trim($messageId)) {
            return false;
        }

        return null !== static::findOneBy(['pid=?', 'messageId=?'], [$pid, $messageId]);
    }

    /**
     * Schreibt einen Protokolleintrag und speichert ihn sofort.
     *
     * Der Eintrag wird auch dann geschrieben, wenn die Verarbeitung
     * fehlschlug — gerade dann ist er wertvoll. Bei einem Fehler steht die
     * Meldung der Ausnahme in `meldung`.
     *
     * @param int    $pid        ID der Mailingliste
     * @param string $messageId  Message-ID der Nachricht, darf leer sein
     * @param string $absender   Adresse des Absenders
     * @param string $betreff    Betreff der Nachricht
     * @param string $aktion     Einer der AKTION_*-Werte dieser Klasse
     * @param int    $empfaenger Anzahl der tatsächlich erreichten Empfänger
     * @param string $meldung    Erläuterung im Klartext, etwa der Grund der
     *                           Ablehnung; wird auf 2000 Zeichen gekürzt
     *
     * @return self Der gespeicherte Eintrag, mit gesetzter ID
     */
    public static function protokollieren(
        int $pid,
        string $messageId,
        string $absender,
        string $betreff,
        string $aktion,
        int $empfaenger = 0,
        string $meldung = '',
    ): self {
        $eintrag = new self();
        $eintrag->pid = $pid;
        $eintrag->tstamp = time();
        $eintrag->datum = time();
        $eintrag->messageId = substr($messageId, 0, 255);
        $eintrag->absender = substr($absender, 0, 255);
        $eintrag->betreff = substr($betreff, 0, 255);
        $eintrag->aktion = $aktion;
        $eintrag->empfaenger = $empfaenger;
        $eintrag->meldung = substr($meldung, 0, 2000);
        $eintrag->save();

        return $eintrag;
    }
}
