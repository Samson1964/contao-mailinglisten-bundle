<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Tests\Verteiler;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoMailinglistenBundle\Verteiler\Verteilergebnis;

/**
 * Prüft das Zusammenzählen der Durchgangsergebnisse.
 *
 * Die Zahlen landen im Contao-Protokoll und sind bei einer stillen Liste das
 * Erste, wonach gesehen wird. Eine falsche Addition würde einen Fehler
 * verschleiern.
 */
class VerteilergebnisTest extends TestCase
{
    /**
     * Ein frisches Ergebnis steht auf null.
     *
     * @return void
     */
    public function testBeginntBeiNull(): void
    {
        $ergebnis = new Verteilergebnis();

        $this->assertSame(0, $ergebnis->gelesen);
        $this->assertSame(0, $ergebnis->verteilt);
        $this->assertSame(0, $ergebnis->fehler);
    }

    /**
     * Zwei Ergebnisse werden feldweise addiert, ohne die Ausgangswerte zu ändern.
     *
     * @return void
     */
    public function testAddiertFeldweiseUndBleibtUnveraenderlich(): void
    {
        $erstes = new Verteilergebnis(gelesen: 3, verteilt: 2, abgelehnt: 1);
        $zweites = new Verteilergebnis(gelesen: 2, antraege: 1, fehler: 1);

        $summe = $erstes->plus($zweites);

        $this->assertSame(5, $summe->gelesen);
        $this->assertSame(2, $summe->verteilt);
        $this->assertSame(1, $summe->abgelehnt);
        $this->assertSame(1, $summe->antraege);
        $this->assertSame(1, $summe->fehler);

        // Die Ausgangsobjekte dürfen sich nicht verändert haben.
        $this->assertSame(3, $erstes->gelesen);
        $this->assertSame(2, $zweites->gelesen);
    }

    /**
     * Der Text nennt alle Zahlen des Durchgangs.
     *
     * @return void
     */
    public function testGibtAlleZahlenAus(): void
    {
        $text = (new Verteilergebnis(gelesen: 4, verteilt: 3, abgelehnt: 1))->alsText();

        $this->assertStringContainsString('4 gelesen', $text);
        $this->assertStringContainsString('3 verteilt', $text);
        $this->assertStringContainsString('1 abgelehnt', $text);
    }
}
