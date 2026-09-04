<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Tests\Sicherheit;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher;

/**
 * Prüft die Verschlüsselung der Postfach-Kennwörter.
 *
 * Der wichtigste Fall ist der letzte: Ein gewechseltes Anwendungsgeheimnis
 * darf keine Ausnahme auslösen, sondern muss ein leeres Kennwort liefern —
 * sonst risse ein einziger Konfigurationsfehler den gesamten Cron-Lauf ab und
 * keine der übrigen Listen käme mehr an die Reihe.
 */
class GeheimspeicherTest extends TestCase
{
    /**
     * Ein verschlüsselter Wert lässt sich wieder in den Klartext zurückführen.
     *
     * @return void
     */
    public function testKehrtZumKlartextZurueck(): void
    {
        $speicher = new Geheimspeicher('geheimnis-der-anwendung');

        $verschluesselt = $speicher->verschluesseln('mein Kennwort mit Ümläut & Sonderzeichen');

        $this->assertNotSame('mein Kennwort mit Ümläut & Sonderzeichen', $verschluesselt);
        $this->assertSame('mein Kennwort mit Ümläut & Sonderzeichen', $speicher->entschluesseln($verschluesselt));
    }

    /**
     * Zweimaliges Verschlüsseln desselben Textes liefert verschiedene Werte.
     *
     * Andernfalls ließe sich aus der Tabelle ablesen, welche Listen dasselbe
     * Kennwort benutzen.
     *
     * @return void
     */
    public function testErzeugtBeiJedemAufrufEinAnderesErgebnis(): void
    {
        $speicher = new Geheimspeicher('geheimnis');

        $this->assertNotSame($speicher->verschluesseln('abc'), $speicher->verschluesseln('abc'));
    }

    /**
     * Ein leerer Klartext bleibt leer, statt eine Hülle zu bekommen.
     *
     * @return void
     */
    public function testLaesstLeereWerteLeer(): void
    {
        $speicher = new Geheimspeicher('geheimnis');

        $this->assertSame('', $speicher->verschluesseln(''));
        $this->assertSame('', $speicher->entschluesseln(''));
    }

    /**
     * Ein Wert ohne Kennung wird unverändert durchgereicht.
     *
     * So bleiben Kennwörter lesbar, die von Hand per SQL eingetragen wurden.
     *
     * @return void
     */
    public function testReichtUnverschluesselteWerteDurch(): void
    {
        $speicher = new Geheimspeicher('geheimnis');

        $this->assertSame('klartext123', $speicher->entschluesseln('klartext123'));
        $this->assertFalse($speicher->istVerschluesselt('klartext123'));
        $this->assertTrue($speicher->istVerschluesselt($speicher->verschluesseln('x')));
    }

    /**
     * Ein anderes Anwendungsgeheimnis liefert '' statt einer Ausnahme.
     *
     * @return void
     */
    public function testGibtBeiFalschemSchluesselNichtsZurueck(): void
    {
        $verschluesselt = (new Geheimspeicher('altes-geheimnis'))->verschluesseln('kennwort');

        $this->assertSame('', (new Geheimspeicher('neues-geheimnis'))->entschluesseln($verschluesselt));
    }

    /**
     * Ein beschädigter Wert löst ebenfalls keine Ausnahme aus.
     *
     * @return void
     */
    public function testVertraegtBeschaedigteWerte(): void
    {
        $speicher = new Geheimspeicher('geheimnis');

        $this->assertSame('', $speicher->entschluesseln('sodium:v1:kein-gültiges-base64!!!'));
        $this->assertSame('', $speicher->entschluesseln('sodium:v1:'.base64_encode('zu kurz')));
    }
}
