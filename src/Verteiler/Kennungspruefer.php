<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Verteiler;

/**
 * Erkennt die Steuerwörter für Aufnahme und Abmeldung im Betreff.
 *
 * Die Kennung muss **am Anfang** des Betreffs stehen. Ein bloßes Vorkommen
 * irgendwo im Text würde harmlose Nachrichten umdeuten: Bei der Kennung
 * „Anmeldung" wäre der Betreff „Anmeldung zum Vereinsturnier läuft" plötzlich
 * ein Aufnahmeantrag, und der eigentliche Inhalt käme bei niemandem an.
 *
 * Vorangestellte Antwort- und Weiterleitungskürzel werden vorher entfernt,
 * damit auch die Antwort auf eine Ablehnung („Re: Anmeldung") noch als Antrag
 * durchgeht — genau das tun Leute erfahrungsgemäß.
 */
class Kennungspruefer
{
    /**
     * Kürzel, die Mailprogramme dem Betreff voranstellen.
     *
     * Die Liste deckt die deutschen und englischen Varianten ab, die in
     * Outlook, Thunderbird, Apple Mail und den gängigen Webmailern anfallen.
     *
     * @var string[]
     */
    private const KUERZEL = ['re', 'aw', 'antw', 'fwd', 'fw', 'wg', 'sv', 'vs'];

    /**
     * Sagt, ob der Betreff mit der angegebenen Kennung beginnt.
     *
     * Groß- und Kleinschreibung spielt keine Rolle, ebensowenig Leerzeichen
     * oder Doppelpunkte hinter der Kennung. Eine leere Kennung trifft nie zu —
     * so schaltet ein leeres Feld im Backend die Funktion einfach ab.
     *
     * @param string $betreff Der Betreff, wie er in der Nachricht steht
     * @param string $kennung Das gesuchte Steuerwort, etwa 'Anmeldung'
     *
     * @return bool true, wenn der bereinigte Betreff mit der Kennung anfängt
     */
    public function trifftZu(string $betreff, string $kennung): bool
    {
        $kennung = trim($kennung);

        if ('' === $kennung) {
            return false;
        }

        $rest = $this->bereinigen($betreff);

        if (0 !== stripos($rest, $kennung)) {
            return false;
        }

        // Was hinter der Kennung folgt, darf kein Buchstabe sein — sonst
        // träfe die Kennung „Abo" auch auf „Abonnentenliste" zu.
        $dahinter = substr($rest, \strlen($kennung), 1);

        return '' === $dahinter || false === $dahinter || 1 !== preg_match('/\p{L}|\p{N}/u', $dahinter);
    }

    /**
     * Entfernt Antwort- und Weiterleitungskürzel sowie Listenpräfixe.
     *
     * Die Schleife läuft so lange, wie sich am Anfang noch etwas abtragen
     * lässt; Betreffs wie „Re: AW: [Vorstand] Anmeldung" kommen in der Praxis
     * vor. Die Zahl der Durchgänge ist begrenzt, damit ein absichtlich
     * verschachtelter Betreff die Verarbeitung nicht aufhält.
     *
     * @param string $betreff Der ursprüngliche Betreff
     *
     * @return string Der Betreff ohne führende Kürzel und Klammerpräfixe
     */
    public function bereinigen(string $betreff): string
    {
        $rest = trim($betreff);
        $muster = '/^(?:(?:'.implode('|', self::KUERZEL).')\s*(?:\[\d+\])?\s*:|\[[^\]]{1,60}\])\s*/i';

        for ($i = 0; $i < 10; ++$i) {
            $neu = preg_replace($muster, '', $rest, 1);

            if (null === $neu || $neu === $rest) {
                break;
            }

            $rest = trim($neu);
        }

        return $rest;
    }
}
