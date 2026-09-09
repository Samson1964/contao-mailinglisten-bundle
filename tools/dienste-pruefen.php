<?php

declare(strict_types=1);

/*
 * Prüft services.yaml gegen den Quelltext.
 *
 * Vier Fehler dieser Art fallen erst auf dem Server auf, und dann als
 * Ausnahme beim Übersetzen des Containers oder — schlimmer — erst beim
 * Aufruf einer einzelnen Methode:
 *
 *  1. Ein benanntes Argument (`$logger:`), das im Konstruktor gar nicht
 *     vorkommt. Symfony bricht mit „Invalid service … unused binding" ab.
 *  2. Ein Verweis auf einen Dienst dieses Bundles, den es nicht gibt —
 *     ein Tippfehler in `@schachbulle_mailinglisten.logger.email` etwa.
 *  3. Eine Dienst-Kennung, die als Klasse gemeint ist, aber keine Datei hat
 *     (typisch nach einer Umbenennung, siehe tools/klassen-pruefen.php).
 *  4. Ein `contao.callback`-Tag, dessen `method` in der Klasse fehlt. Contao
 *     meldet das nicht; der Rückruf bleibt einfach wirkungslos.
 *
 * Gelesen wird zeilenweise statt mit einem YAML-Parser: Das Bundle bringt
 * keine Abhängigkeiten mit, und die Datei hat eine feste, flache Form.
 *
 * Aufruf: php tools/dienste-pruefen.php
 */

$wurzel = $argv[1] ?? \dirname(__DIR__);
$datei = $wurzel.'/src/Resources/config/services.yaml';
$namensraum = 'Schachbulle\\ContaoMailinglistenBundle\\';

if (!is_file($datei)) {
    echo "Keine services.yaml unter $datei\n";

    exit(1);
}

/**
 * Liest die Namen der Konstruktorparameter einer Klasse aus dem Quelltext.
 *
 * Die Klasse wird nicht geladen — dafür fehlten die Contao-Basisklassen. Es
 * genügt, den Kopf des Konstruktors zu tokenisieren und jede Variable zu
 * nehmen, die auf gleicher Klammerebene steht.
 *
 * @param string $klassendatei Pfad zur PHP-Datei
 *
 * @return array<int, string> Die Parameternamen ohne Dollarzeichen, in der
 *                            Reihenfolge ihres Auftretens; leer, wenn die
 *                            Klasse keinen Konstruktor hat
 */
function konstruktorParameter(string $klassendatei): array
{
    $marken = token_get_all(file_get_contents($klassendatei));
    $anzahl = \count($marken);

    for ($i = 0; $i < $anzahl; ++$i) {
        if (\T_FUNCTION !== ($marken[$i][0] ?? null)) {
            continue;
        }

        // Der Name folgt nach dem Leerraum. Alles andere als __construct
        // überspringen.
        $j = $i + 1;

        while (isset($marken[$j]) && \is_array($marken[$j]) && \in_array($marken[$j][0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)) {
            ++$j;
        }

        if (!\is_array($marken[$j] ?? null) || '__construct' !== $marken[$j][1]) {
            continue;
        }

        // Ab der öffnenden Klammer bis zur zugehörigen schließenden alle
        // Variablen der Ebene 1 einsammeln. Voreinstellungen wie
        // `array $x = []` bringen keine weiteren Variablen mit, Aufrufe im
        // Standardwert wären auf tieferer Ebene.
        $ebene = 0;
        $namen = [];

        for ($k = $j; $k < $anzahl; ++$k) {
            $marke = $marken[$k];

            if ('(' === $marke) {
                ++$ebene;

                continue;
            }

            if (')' === $marke) {
                if (0 === --$ebene) {
                    return $namen;
                }

                continue;
            }

            if (1 === $ebene && \is_array($marke) && \T_VARIABLE === $marke[0]) {
                $namen[] = ltrim($marke[1], '$');
            }
        }
    }

    return [];
}

/**
 * Sagt, ob eine Eigenschaft im Quelltext als Argument eines `new` auftaucht.
 *
 * Gesucht wird `$this-><name>` innerhalb der Argumentliste eines
 * `new Irgendwas(…)`. Die Klammerebene wird mitgezählt, damit auch
 * mehrzeilige Aufrufe mit verschachtelten Klammern richtig erfasst werden —
 * ein regulärer Ausdruck käme damit nicht zurecht.
 *
 * @param string $klassendatei Pfad zur PHP-Datei
 * @param string $name         Name der Eigenschaft ohne `$this->`
 *
 * @return bool true, wenn die Eigenschaft an einen Konstruktor weitergereicht
 *              wird
 */
function wirdWeitergereicht(string $klassendatei, string $name): bool
{
    $marken = token_get_all(file_get_contents($klassendatei));
    $anzahl = \count($marken);

    for ($i = 0; $i < $anzahl; ++$i) {
        if (\T_NEW !== ($marken[$i][0] ?? null)) {
            continue;
        }

        // Bis zur öffnenden Klammer des Aufrufs vorspulen. Steht dort keine
        // (etwa bei `new $klasse;`), ist nichts zu prüfen.
        $j = $i;

        while ($j < $anzahl && '(' !== $marken[$j]) {
            if (';' === $marken[$j]) {
                continue 2;
            }

            ++$j;
        }

        $ebene = 0;

        for ($k = $j; $k < $anzahl; ++$k) {
            if ('(' === $marken[$k]) {
                ++$ebene;

                continue;
            }

            if (')' === $marken[$k]) {
                if (0 === --$ebene) {
                    break;
                }

                continue;
            }

            // `$this` `->` `name` als Folge dreier Marken.
            if (
                \is_array($marken[$k]) && \T_VARIABLE === $marken[$k][0] && '$this' === $marken[$k][1]
                && \is_array($marken[$k + 1] ?? null) && \T_OBJECT_OPERATOR === $marken[$k + 1][0]
                && \is_array($marken[$k + 2] ?? null) && $name === $marken[$k + 2][1]
            ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Ermittelt die Datei zu einem Klassennamen dieses Bundles.
 *
 * @param string $klasse     Voll qualifizierter Name
 * @param string $wurzel     Wurzelverzeichnis des Bundles
 * @param string $namensraum Der eigene Namensraum samt abschließendem Backslash
 *
 * @return string|null Der Pfad, oder null wenn die Klasse nicht zu diesem
 *                     Bundle gehört
 */
function klassendatei(string $klasse, string $wurzel, string $namensraum): ?string
{
    if (!str_starts_with($klasse, $namensraum)) {
        return null;
    }

    return $wurzel.'/src/'.str_replace('\\', '/', substr($klasse, \strlen($namensraum))).'.php';
}

$zeilen = file($datei, FILE_IGNORE_NEW_LINES);
$dienste = [];      // Kennung => ['klasse' => ?string, 'args' => [Name], 'tags' => [[method, ...]]]
$kennung = null;
$abschnitt = '';
$probleme = 0;
$geprueft = 0;

foreach ($zeilen as $nr => $zeile) {
    if ('' === trim($zeile) || str_starts_with(trim($zeile), '#')) {
        continue;
    }

    // Dienst-Kennung: genau vier Leerzeichen Einzug, endet auf einem
    // Doppelpunkt (mit oder ohne `~` dahinter).
    if (preg_match('/^ {4}([^\s#][^:]*):\s*(~|)$/', $zeile, $m) && '_defaults' !== $m[1]) {
        $kennung = $m[1];
        $dienste[$kennung] = ['klasse' => null, 'args' => [], 'tags' => [], 'zeile' => $nr + 1];
        $abschnitt = '';

        continue;
    }

    if (null === $kennung) {
        continue;
    }

    if (preg_match('/^ {8}(arguments|tags|class):\s*(.*)$/', $zeile, $m)) {
        $abschnitt = $m[1];

        if ('class' === $m[1]) {
            $dienste[$kennung]['klasse'] = trim($m[2]);
            $abschnitt = '';
        }

        continue;
    }

    if ('arguments' === $abschnitt && preg_match('/^ {12}\$(\w+):\s*(.*)$/', $zeile, $m)) {
        $dienste[$kennung]['args'][$m[1]] = trim($m[2], "'\" ");
    }

    if ('arguments' === $abschnitt && preg_match("/^ {12}- '@([^']+)'/", $zeile, $m)) {
        $dienste[$kennung]['args'][] = $m[1];
    }

    if ('tags' === $abschnitt && preg_match('/method:\s*(\w+)/', $zeile, $m)) {
        $dienste[$kennung]['tags'][] = $m[1];
    }
}

// Dienste, die einen Monolog-Kanal in Contaos SystemLogger hüllen. Ihre
// Einträge landen im System-Log — das ist gewollt, solange nur eigener Code
// sie benutzt. Wird ein solcher Protokollierer an fremden Code weitergereicht,
// bekommt jede beiläufige Statusmeldung jener Klasse einen ContaoContext und
// steht mit der Aktion des Dienstes im System-Log. Siehe Prüfung 5.
$gehuellt = [];

foreach ($dienste as $id => $dienst) {
    if (str_contains((string) ($dienst['klasse'] ?? ''), 'Monolog\\SystemLogger')) {
        $gehuellt[$id] = true;
    }
}

foreach ($dienste as $id => $dienst) {
    $klasse = $dienst['klasse'] ?? (str_contains($id, '\\') ? $id : null);
    $pfad = null === $klasse ? null : klassendatei($klasse, $wurzel, $namensraum);

    // --- 1. Klassendatei vorhanden --------------------------------------

    if (null !== $pfad) {
        ++$geprueft;

        if (!is_file($pfad)) {
            printf("%-60s KLASSE FEHLT: %s\n", $id, $pfad);
            ++$probleme;

            continue;
        }
    }

    $parameter = null === $pfad || !is_file($pfad) ? null : konstruktorParameter($pfad);

    foreach ($dienst['args'] as $name => $wert) {
        // --- 2. Benannte Argumente müssen im Konstruktor stehen ---------

        if (\is_string($name) && null !== $parameter) {
            ++$geprueft;

            if (!\in_array($name, $parameter, true)) {
                printf(
                    "%-60s ARGUMENT \$%s hat keinen Konstruktorparameter (vorhanden: %s)\n",
                    $id,
                    $name,
                    implode(', ', $parameter) ?: '—',
                );
                ++$probleme;
            }
        }

        // --- 3. Verweise auf eigene Dienste müssen aufgehen -------------

        $verweis = ltrim((string) $wert, '@');

        if (str_starts_with($verweis, 'schachbulle_') || str_starts_with($verweis, $namensraum)) {
            ++$geprueft;

            if (!isset($dienste[$verweis])) {
                printf("%-60s VERWEIS INS LEERE: @%s\n", $id, $verweis);
                ++$probleme;
            }
        }

        // --- 5. Ein SystemLogger gehört nicht in fremde Hände -----------
        //
        // Contaos SystemLogger hängt jedem Eintrag einen ContaoContext an,
        // damit er im System-Log erscheint. Reicht man ihn an fremden Code
        // weiter — etwa an Symfonys SMTP-Transport —, wandert auch dessen
        // beiläufiges „Email transport starting“ ins System-Log, und zwar
        // unter der Aktion des Dienstes. In 1.2.1 stand deshalb eine
        // Statusmeldung des Mailers als „Fehler“ im Log.

        if (\is_string($name) && isset($gehuellt[$verweis]) && null !== $pfad && is_file($pfad)) {
            ++$geprueft;

            if (wirdWeitergereicht($pfad, $name)) {
                printf(
                    "%-60s \$%s ist ein SystemLogger und wird an fremden Code weitergereicht\n",
                    $id,
                    $name,
                );
                ++$probleme;
            }
        }
    }

    // --- 4. Rückruf-Methoden müssen existieren --------------------------

    if ($dienst['tags'] && null !== $pfad && is_file($pfad)) {
        $quelltext = file_get_contents($pfad);

        foreach (array_unique($dienst['tags']) as $methode) {
            ++$geprueft;

            if (!preg_match('/function\s+'.preg_quote($methode, '/').'\s*\(/', $quelltext)) {
                printf("%-60s RÜCKRUF FEHLT: %s()\n", $id, $methode);
                ++$probleme;
            }
        }
    }
}

printf("\n%d Dienste, %d Prüfungen, %d Beanstandungen\n", \count($dienste), $geprueft, $probleme);

exit($probleme > 0 ? 1 : 0);
