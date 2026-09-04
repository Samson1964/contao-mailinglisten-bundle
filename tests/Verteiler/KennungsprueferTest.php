<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Tests\Verteiler;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Kennungspruefer;

/**
 * Prüft die Erkennung der Steuerwörter im Betreff.
 *
 * Die beiden gefährlichen Fälle stehen am Ende: Eine Kennung mitten im Text
 * darf nicht greifen, sonst würde eine normale Nachricht als Antrag
 * missverstanden und käme bei niemandem an. Und eine Kennung, die nur der
 * Anfang eines längeren Wortes ist, ebenfalls nicht.
 */
class KennungsprueferTest extends TestCase
{
    /**
     * Der einfache Fall: Der Betreff besteht aus der Kennung.
     *
     * @return void
     */
    public function testErkenntDieBlosseKennung(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertTrue($pruefer->trifftZu('Anmeldung', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('anmeldung', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('  ANMELDUNG  ', 'Anmeldung'));
    }

    /**
     * Die Kennung darf am Anfang stehen und von weiterem Text gefolgt werden.
     *
     * @return void
     */
    public function testErkenntKennungMitNachfolgendemText(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertTrue($pruefer->trifftZu('Anmeldung zur Vorstandsliste', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('Anmeldung: bitte aufnehmen', 'Anmeldung'));
    }

    /**
     * Antwort- und Weiterleitungskürzel werden vorher abgetragen.
     *
     * Das ist kein Randfall: Wer eine Ablehnung bekommt, antwortet darauf.
     *
     * @return void
     */
    public function testUebergehtAntwortkuerzelUndListenpraefixe(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertTrue($pruefer->trifftZu('Re: Anmeldung', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('AW: Anmeldung', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('Re: AW: [Vorstand] Anmeldung', 'Anmeldung'));
        $this->assertTrue($pruefer->trifftZu('Re[2]: Anmeldung', 'Anmeldung'));
    }

    /**
     * Eine Kennung mitten im Betreff greift nicht.
     *
     * @return void
     */
    public function testGreiftNichtBeiKennungImText(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertFalse($pruefer->trifftZu('Die Anmeldung zum Turnier läuft', 'Anmeldung'));
        $this->assertFalse($pruefer->trifftZu('Frist für Anmeldung verlängert', 'Anmeldung'));
    }

    /**
     * Eine Kennung als Wortanfang greift ebenfalls nicht.
     *
     * @return void
     */
    public function testGreiftNichtBeiLaengeremWort(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertFalse($pruefer->trifftZu('Anmeldungen sind eingegangen', 'Anmeldung'));
        $this->assertFalse($pruefer->trifftZu('Abonnentenliste', 'Abo'));
        $this->assertTrue($pruefer->trifftZu('Abo bitte', 'Abo'));
    }

    /**
     * Eine leere Kennung schaltet die Prüfung ab.
     *
     * @return void
     */
    public function testLeereKennungTrifftNie(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertFalse($pruefer->trifftZu('Anmeldung', ''));
        $this->assertFalse($pruefer->trifftZu('', ''));
        $this->assertFalse($pruefer->trifftZu('', 'Anmeldung'));
    }

    /**
     * Das Bereinigen entfernt Kürzel und Klammerpräfixe, sonst nichts.
     *
     * @return void
     */
    public function testBereinigtNurDenAnfang(): void
    {
        $pruefer = new Kennungspruefer();

        $this->assertSame('Ein Thema', $pruefer->bereinigen('Re: [Liste] Ein Thema'));
        $this->assertSame('Ein Thema [mit Klammer]', $pruefer->bereinigen('AW: Ein Thema [mit Klammer]'));
        $this->assertSame('Ein Thema', $pruefer->bereinigen('Ein Thema'));
    }
}
