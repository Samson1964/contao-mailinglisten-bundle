<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Tests\Postfach;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoMailinglistenBundle\Postfach\EingehendeNachricht;

/**
 * Prüft die Erkennung maschineller Nachrichten und die Textgewinnung.
 *
 * Die Erkennung ist der wichtigste Schutz der ganzen Erweiterung: Eine
 * Abwesenheitsnotiz, die an eine Liste mit fünfzig Teilnehmern verteilt wird,
 * erzeugt fünfzig weitere Automaten-Antworten, und die Liste ist binnen
 * Minuten unbrauchbar.
 */
class EingehendeNachrichtTest extends TestCase
{
    /**
     * Eine gewöhnliche Nachricht gilt nicht als maschinell.
     *
     * @return void
     */
    public function testGewoehnlicheNachrichtIstNichtAutomatisch(): void
    {
        $this->assertFalse($this->nachricht()->istAutomatisch());
    }

    /**
     * Ein leerer Absender kennzeichnet einen Unzustellbarkeitsbericht.
     *
     * @return void
     */
    public function testLeererAbsenderGiltAlsAutomatisch(): void
    {
        $this->assertTrue($this->nachricht(absender: '')->istAutomatisch());
    }

    /**
     * Die Kopfzeile Auto-Submitted schlägt an, außer bei dem Wert "no".
     *
     * Genau dieser Wert steht nach RFC 3834 in einer ganz normalen, von einem
     * Menschen geschriebenen Nachricht — er darf also nicht zum Verwerfen
     * führen.
     *
     * @return void
     */
    public function testWertetAutoSubmittedAus(): void
    {
        $this->assertTrue($this->nachricht(kopfzeilen: ['auto-submitted' => 'auto-replied'])->istAutomatisch());
        $this->assertTrue($this->nachricht(kopfzeilen: ['auto-submitted' => 'auto-generated'])->istAutomatisch());
        $this->assertFalse($this->nachricht(kopfzeilen: ['auto-submitted' => 'no'])->istAutomatisch());
    }

    /**
     * Auch Precedence und die X-Auto*-Kopfzeilen werden beachtet.
     *
     * @return void
     */
    public function testWertetWeitereKennzeichenAus(): void
    {
        $this->assertTrue($this->nachricht(kopfzeilen: ['precedence' => 'bulk'])->istAutomatisch());
        $this->assertTrue($this->nachricht(kopfzeilen: ['x-autoreply' => 'yes'])->istAutomatisch());
        $this->assertFalse($this->nachricht(kopfzeilen: ['precedence' => 'normal'])->istAutomatisch());
    }

    /**
     * Kopfzeilen lassen sich unabhängig von der Schreibweise lesen.
     *
     * @return void
     */
    public function testLiestKopfzeilenUnabhaengigVonDerSchreibweise(): void
    {
        $nachricht = $this->nachricht(kopfzeilen: ['x-contao-mailingliste' => '7']);

        $this->assertSame('7', $nachricht->kopfzeile('X-Contao-Mailingliste'));
        $this->assertSame('', $nachricht->kopfzeile('gibt-es-nicht'));
    }

    /**
     * Fehlt der Textteil, wird er aus dem HTML gewonnen.
     *
     * Die Zeilenumbrüche müssen dabei erhalten bleiben, sonst klebt der ganze
     * Text in einer einzigen Zeile.
     *
     * @return void
     */
    public function testGewinntTextAusHtml(): void
    {
        $nachricht = $this->nachricht(text: '', html: '<p>Erste Zeile</p><p>Zweite Zeile &amp; mehr</p>');

        $this->assertSame("Erste Zeile\n\nZweite Zeile & mehr", $nachricht->textOderAusHtml());
    }

    /**
     * Verschachtelte Auszeichnungen führen nicht zu einem Loch im Text.
     *
     * Ein Absatz in einem Kasten in einer Tabelle erzeugt sonst leicht sechs
     * Leerzeilen hintereinander, was wie ein Fehler aussieht.
     *
     * @return void
     */
    public function testFasstMehrfacheLeerzeilenZusammen(): void
    {
        $nachricht = $this->nachricht(text: '', html: '<div><p>Oben</p></div><div><p>Unten</p></div>');

        $this->assertSame("Oben\n\nUnten", $nachricht->textOderAusHtml());
    }

    /**
     * Ist ein Textteil vorhanden, wird er unverändert bevorzugt.
     *
     * @return void
     */
    public function testBevorzugtDenVorhandenenText(): void
    {
        $nachricht = $this->nachricht(text: 'Der Urtext', html: '<p>Etwas anderes</p>');

        $this->assertSame('Der Urtext', $nachricht->textOderAusHtml());
    }

    /**
     * Baut eine Nachricht mit sinnvollen Vorgabewerten.
     *
     * @param string                $absender   Absenderadresse
     * @param array<string, string> $kopfzeilen Zusätzliche Kopfzeilen
     * @param string                $text       Textteil
     * @param string                $html       HTML-Teil
     *
     * @return EingehendeNachricht Die zusammengebaute Nachricht
     */
    private function nachricht(string $absender = 'max@example.org', array $kopfzeilen = [], string $text = 'Hallo', string $html = ''): EingehendeNachricht
    {
        return new EingehendeNachricht(
            messageId: 'abc123@example.org',
            absender: $absender,
            absenderName: 'Max Mustermann',
            betreff: 'Ein Betreff',
            text: $text,
            html: $html,
            kopfzeilen: $kopfzeilen,
        );
    }
}
