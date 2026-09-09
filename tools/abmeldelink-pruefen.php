<?php

declare(strict_types=1);

/*
 * Prüft die beiden neuen Wege in NachrichtenBauer::platzhalter():
 * den Ersatz von ##abmeldelink## und das Wegfallen der Zeile, wenn keine
 * Adresse zustande kommt.
 *
 * Die Klasse selbst lässt sich ohne Contao nicht laden. Geprüft wird deshalb
 * die Logik in einer wortgleichen Nachbildung — sinnvoll nur zusammen mit der
 * Gegenprobe, dass der Quelltext des Bundles dieselben Zeilen enthält.
 */

$wurzel = \dirname(__DIR__, 1);
$quelle = $wurzel.'/src/Versand/NachrichtenBauer.php';
$text = file_get_contents($quelle);

$fehler = 0;

/**
 * Meldet das Ergebnis einer einzelnen Prüfung.
 *
 * @param string $was      Beschreibung der Erwartung
 * @param bool   $erfuellt Ob sie zutrifft
 */
function pruefe(string $was, bool $erfuellt): void
{
    global $fehler;

    printf("  [%s] %s\n", $erfuellt ? 'ok' : 'FEHLER', $was);

    if (!$erfuellt) {
        ++$fehler;
    }
}

echo "Quelltext:\n";
pruefe('abmeldeadresse() ist vorhanden', str_contains($text, 'private function abmeldeadresse('));
pruefe('Kopfzeile benutzt abmeldeadresse()', str_contains($text, '$basis = $this->abmeldeadresse($liste, $empfaenger);'));
pruefe('platzhalter() kennt ##abmeldelink##', str_contains($text, "'##abmeldelink##' => \$abmeldelink,"));
pruefe('fusszeile() reicht den Empfänger durch', str_contains($text, '$this->platzhalter($eigene, $liste, $eingang, $absender, $empfaenger)'));
pruefe('verteilung() reicht den Empfänger durch', str_contains($text, '$this->fusszeile($liste, $eingang, $absender, $empfaenger)'));
pruefe('HTML-Teil macht daraus einen Verweis', str_contains($text, '<a href="%s">%s</a>'));
pruefe('selbsterklärend zählt ##abmeldelink## mit', str_contains($text, "str_contains(\$eigene, '##abmeldelink##')"));

// --- Nachbildung der Logik -------------------------------------------------

/**
 * Bildet `NachrichtenBauer::abmeldeadresse()` nach.
 */
function adresse(string $basisUrl, ?string $merkmal): string
{
    $basis = rtrim(trim($basisUrl), '/');

    if ('' === $basis || null === $merkmal) {
        return '';
    }

    return sprintf('%s/mailinglisten/abmelden/%s', $basis, $merkmal);
}

/**
 * Bildet den neuen Teil von `NachrichtenBauer::platzhalter()` nach.
 */
function ersetzen(string $vorlage, string $basisUrl, ?string $merkmal): string
{
    $link = adresse($basisUrl, $merkmal);

    if ('' === $link) {
        $zeilen = preg_split('/\R/', $vorlage) ?: [];
        $vorlage = implode("\n", array_filter(
            $zeilen,
            static fn (string $zeile): bool => !str_contains($zeile, '##abmeldelink##'),
        ));
    }

    return strtr($vorlage, ['##abmeldelink##' => $link, '##liste##' => 'Vorstand']);
}

$fuss = "Diese Nachricht ging an alle Teilnehmer von ##liste##.\nOder mit einem Klick: ##abmeldelink##";
$merkmal = '9db5ba79291e1a1f1c77eebc7fb1866c';

echo "\nMit Basisadresse:\n";
$mit = ersetzen($fuss, 'https://www.example.org', $merkmal);
pruefe('Adresse eingesetzt', str_contains($mit, "https://www.example.org/mailinglisten/abmelden/$merkmal"));
pruefe('beide Zeilen erhalten', 2 === substr_count($mit, "\n") + 1);
pruefe('kein Platzhalter übrig', !str_contains($mit, '##'));

echo "\nMit Schrägstrich am Ende der Basisadresse:\n";
$slash = ersetzen($fuss, 'https://www.example.org/', $merkmal);
pruefe('kein doppelter Schrägstrich', !str_contains($slash, '.org//'));

echo "\nOhne Basisadresse:\n";
$ohne = ersetzen($fuss, '', $merkmal);
pruefe('Zeile mit dem Platzhalter entfällt', !str_contains($ohne, 'Oder mit einem Klick'));
pruefe('die übrige Fußzeile bleibt', str_contains($ohne, 'Diese Nachricht ging an alle Teilnehmer von Vorstand.'));
pruefe('kein Platzhalter übrig', !str_contains($ohne, '##'));

echo "\nOhne Empfänger (etwa bei einer Ablehnung):\n";
$keiner = ersetzen($fuss, 'https://www.example.org', null);
pruefe('Zeile entfällt ebenfalls', !str_contains($keiner, 'Oder mit einem Klick'));

echo "\nFußzeile ohne den Platzhalter bleibt unangetastet:\n";
$alt = ersetzen("Diese Nachricht ging an alle Teilnehmer von ##liste##.", '', null);
pruefe('unverändert bis auf ##liste##', 'Diese Nachricht ging an alle Teilnehmer von Vorstand.' === $alt);

printf("\n%s\n", 0 === $fehler ? 'Alle Prüfungen bestanden.' : "$fehler Prüfung(en) fehlgeschlagen.");

exit($fehler > 0 ? 1 : 0);
