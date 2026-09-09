<?php

declare(strict_types=1);

/*
 * Prüft die DCA-Dateien auf Stolperstellen, die erst im Backend auffallen.
 *
 * Contao meldet solche Fehler nicht: Ein Feld in der Palette, das es nicht
 * gibt, erzeugt eine leere Zeile; eine fehlende Beschriftung zeigt den
 * Feldnamen; und ein `explanation`, das versehentlich in `eval` steht, führt
 * zu einem Hilfe-Assistenten, der sich mit leerem Inhalt öffnet. Genau das ist
 * am 2026-09-04 passiert — `BackendHelp` liest `explanation` von der
 * **Feldebene**, `helpwizard` dagegen aus `eval`.
 *
 * Aufruf: php tools/dca-pruefen.php
 */

$wurzel = $argv[1] ?? \dirname(__DIR__);
$dcaPfad = $wurzel.'/src/Resources/contao/dca';
$sprachPfad = $wurzel.'/src/Resources/contao/languages';

if (!is_dir($dcaPfad)) {
    echo "Kein DCA-Verzeichnis unter $dcaPfad\n";

    exit(1);
}

$probleme = 0;
$geprueft = 0;

/**
 * Liest eine DCA-Datei ein und gibt das erzeugte Array zurück.
 *
 * Die Datei wird wirklich ausgeführt, nicht mit einem regulären Ausdruck
 * zerlegt — nur so stimmen auch Felder, die über eine Zuweisung entstehen
 * (wie in tl_module.php) und nicht als Array-Literal.
 *
 * @param string $datei Pfad zur DCA-Datei
 *
 * @return array<string, mixed> Die Definition, oder ein leeres Feld
 */
function dcaLesen(string $datei): array
{
    $tabelle = basename($datei, '.php');

    // Contao-Klassen, die eine DCA-Datei benutzen darf, ohne dass wir den
    // ganzen Rahmen laden müssten.
    if (!class_exists('Contao\DataContainer', false)) {
        eval('namespace Contao; class DataContainer { const MODE_SORTED = 1; const MODE_PARENT = 4; const SORT_ASC = 11; const SORT_DESC = 12; } class DC_Table {}');
    }

    // Der PaletteManipulator erweitert Kernpaletten, die hier gar nicht
    // vorliegen. Die Attrappe schluckt die Aufrufkette, damit die Datei
    // durchläuft; geprüft werden ohnehin nur die Felder, die das Bundle selbst
    // beisteuert.
    if (!class_exists('Contao\CoreBundle\DataContainer\PaletteManipulator', false)) {
        eval('namespace Contao\CoreBundle\DataContainer; class PaletteManipulator {
            const POSITION_BEFORE = "before";
            const POSITION_AFTER = "after";
            const POSITION_PREPEND = "prepend";
            const POSITION_APPEND = "append";
            public static function create(): self { return new self(); }
            public function __call($name, $args): self { return $this; }
        }');
    }

    $GLOBALS['TL_DCA'] = [];
    $GLOBALS['TL_LANG'] = $GLOBALS['TL_LANG'] ?? [];

    require $datei;

    return $GLOBALS['TL_DCA'][$tabelle] ?? [];
}

foreach (glob($dcaPfad.'/*.php') as $datei) {
    $tabelle = basename($datei, '.php');
    $dca = dcaLesen($datei);
    $felder = array_keys($dca['fields'] ?? []);

    if (!$felder) {
        printf("%-30s KEINE FELDER GEFUNDEN\n", $tabelle);
        ++$probleme;

        continue;
    }

    // --- 1. Palettenfelder müssen definiert sein -------------------------
    //
    // Nur bei Tabellen, die dieses Bundle selbst anlegt. Eine Datei ohne
    // `config.dataContainer` erweitert eine Kerntabelle (etwa tl_module); dort
    // stammt der größte Teil der Palette von Contao, und die Felder stehen in
    // einer DCA-Datei, die hier gar nicht vorliegt.

    $eigeneTabelle = isset($dca['config']['dataContainer']);
    $inPalette = [];

    foreach (($dca['palettes'] ?? []) as $name => $palette) {
        if ('__selector__' === $name || !\is_string($palette)) {
            continue;
        }

        preg_match_all('/[;,]?([a-zA-Z_]+)/', preg_replace('/\{[^}]+\}/', '', $palette) ?? '', $m);
        $inPalette = array_merge($inPalette, $m[1]);
    }

    foreach (($dca['subpalettes'] ?? []) as $palette) {
        $inPalette = array_merge($inPalette, array_map('trim', explode(',', (string) $palette)));
    }

    $fehlend = $eigeneTabelle ? array_diff(array_unique(array_filter($inPalette)), $felder) : [];
    ++$geprueft;

    if ($fehlend) {
        printf("%-30s PALETTENFELD OHNE DEFINITION: %s\n", $tabelle, implode(', ', $fehlend));
        ++$probleme;
    }

    // --- 2. explanation gehört auf die Feldebene, helpwizard in eval -----

    foreach ($dca['fields'] as $feld => $daten) {
        ++$geprueft;

        if (isset($daten['eval']['explanation'])) {
            printf("%-30s %s: explanation steht in eval — BackendHelp liest es von der Feldebene\n", $tabelle, $feld);
            ++$probleme;
        }

        if (isset($daten['explanation'])) {
            if (empty($daten['eval']['helpwizard'])) {
                printf("%-30s %s: explanation ohne helpwizard — der Verweis wird nie gezeigt\n", $tabelle, $feld);
                ++$probleme;
            }

            foreach (['de', 'en'] as $sprache) {
                $explain = $sprachPfad.'/'.$sprache.'/explain.php';
                $GLOBALS['TL_LANG'] = [];

                if (is_file($explain)) {
                    require $explain;
                }

                if (!isset($GLOBALS['TL_LANG']['XPL'][$daten['explanation']])) {
                    printf("%-30s %s: XPL-Schlüssel \"%s\" fehlt in %s/explain.php\n", $tabelle, $feld, $daten['explanation'], $sprache);
                    ++$probleme;
                }
            }
        }
    }

    // --- 3. Beschriftungen in beiden Sprachen ---------------------------

    $ohneLabel = ['id', 'pid', 'tstamp', 'sorting'];

    foreach (['de', 'en'] as $sprache) {
        $sprachdatei = $sprachPfad.'/'.$sprache.'/'.$tabelle.'.php';

        if (!is_file($sprachdatei)) {
            continue;
        }

        $inhalt = file_get_contents($sprachdatei);
        $fehlt = [];

        foreach ($dca['fields'] as $feld => $daten) {
            // Nur Felder, die im Formular erscheinen, brauchen eine
            // Beschriftung. Reine Datenbankspalten nicht.
            if (!isset($daten['inputType']) || \in_array($feld, $ohneLabel, true)) {
                continue;
            }

            if (!str_contains($inhalt, "['".$feld."']")) {
                $fehlt[] = $feld;
            }
        }

        ++$geprueft;

        if ($fehlt) {
            printf("%-30s OHNE BESCHRIFTUNG (%s): %s\n", $tabelle, $sprache, implode(', ', $fehlt));
            ++$probleme;
        }
    }
}

printf("\n%d Prüfungen, %d Beanstandungen\n", $geprueft, $probleme);

exit($probleme > 0 ? 1 : 0);
