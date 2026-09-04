<?php

declare(strict_types=1);

/*
 * Sucht Klassenverweise, die ins Leere zeigen.
 *
 * `php -l` prüft nur die Syntax und meldet einen falsch geschriebenen
 * Klassennamen nicht — der fällt erst zur Laufzeit auf, und zwar genau dann,
 * wenn die betroffene Methode zum ersten Mal aufgerufen wird. Beim
 * Mailinglisten-Bundle ist das am 2026-09-04 passiert: Nach der Umbenennung
 * von MailinglisteModel auf MailinglistenModel blieben drei Signaturen stehen,
 * die PHP gegen den eigenen Namensraum aufgelöst hat.
 *
 * Das Skript tokenisiert jede Datei (dadurch bleiben Kommentare und
 * Zeichenketten außen vor), sammelt die use-Importe und meldet jeden
 * unqualifizierten Klassennamen, der weder importiert ist noch als Datei im
 * selben Namensraum liegt.
 */

$wurzel = $argv[1] ?? dirname(__DIR__);

/**
 * Klassen, die PHP selbst mitbringt und die ohne Import benutzt werden dürfen.
 */
$eingebaut = [
    'Throwable', 'Exception', 'Error', 'TypeError', 'RuntimeException',
    'InvalidArgumentException', 'LogicException', 'DateTime', 'DateTimeImmutable',
    'DateTimeInterface', 'DateInterval', 'ReflectionClass', 'ReflectionMethod',
    'Closure', 'Generator', 'Iterator', 'IteratorAggregate', 'Countable',
    'ArrayAccess', 'Traversable', 'stdClass', 'JsonException',
];

/**
 * Sammelt Namensraum, Importe und benutzte Klassennamen einer Datei.
 *
 * @param string $datei Pfad zur PHP-Datei
 *
 * @return array{namespace: string, importe: array<string,string>, benutzt: array<int,array{0:string,1:int}>}
 */
function zerlegen(string $datei): array
{
    $token = token_get_all(file_get_contents($datei));
    $namespace = '';
    $importe = [];
    $benutzt = [];
    $anzahl = count($token);

    for ($i = 0; $i < $anzahl; ++$i) {
        $t = $token[$i];

        if (!is_array($t)) {
            continue;
        }

        // Namensraum der Datei
        if (T_NAMESPACE === $t[0]) {
            for ($j = $i + 1; $j < $anzahl; ++$j) {
                if (is_array($token[$j]) && in_array($token[$j][0], [T_STRING, T_NAME_QUALIFIED], true)) {
                    $namespace = $token[$j][1];
                    break;
                }
                if (is_string($token[$j]) && ';' === $token[$j]) {
                    break;
                }
            }
            continue;
        }

        // use-Anweisungen auf oberster Ebene
        if (T_USE === $t[0]) {
            $voll = '';
            $alias = '';
            for ($j = $i + 1; $j < $anzahl; ++$j) {
                if (is_string($token[$j]) && (';' === $token[$j] || '(' === $token[$j] || '{' === $token[$j])) {
                    break;
                }
                if (!is_array($token[$j])) {
                    continue;
                }
                if (in_array($token[$j][0], [T_STRING, T_NAME_QUALIFIED], true)) {
                    if ('' === $voll) {
                        $voll = $token[$j][1];
                    } else {
                        $alias = $token[$j][1];
                    }
                }
            }
            if ('' !== $voll) {
                $kurz = '' !== $alias ? $alias : substr(strrchr('\\'.$voll, '\\') ?: '', 1);
                $importe[$kurz] = $voll;
            }
            continue;
        }

        // Benutzte Klassennamen: unqualifizierte Bezeichner mit großem
        // Anfangsbuchstaben, die nicht Methodenname, Eigenschaft oder
        // Funktionsdeklaration sind.
        if (T_STRING === $t[0] && preg_match('/^[A-Z]/', $t[1])) {
            $vorher = null;
            for ($j = $i - 1; $j >= 0; --$j) {
                if (is_array($token[$j]) && in_array($token[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }
                $vorher = $token[$j];
                break;
            }

            // Nach -> ?-> :: function const case steht kein Klassenname.
            // T_CASE deckt die Fälle einer Aufzählung ab (enum Nachbehandlung).
            if (is_array($vorher) && in_array($vorher[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_FUNCTION, T_CONST, T_DOUBLE_COLON, T_CASE], true)) {
                continue;
            }

            // Durchgehende Großschreibung ist eine Konstante, keine Klasse
            // (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, ENT_QUOTES, PHP_EOL).
            if (preg_match('/^[A-Z][A-Z0-9_]*$/', $t[1])) {
                continue;
            }

            // Ein voll qualifizierter Name beginnt mit \ und ist immer gültig.
            if (is_array($vorher) && T_NS_SEPARATOR === $vorher[0]) {
                continue;
            }

            $benutzt[] = [$t[1], $t[2]];
        }
    }

    return ['namespace' => $namespace, 'importe' => $importe, 'benutzt' => $benutzt];
}

// --- Durchlauf -----------------------------------------------------------

$dateien = [];
foreach (['src', 'tests'] as $ordner) {
    $pfad = $wurzel.'/'.$ordner;
    if (!is_dir($pfad)) {
        continue;
    }
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pfad));
    foreach ($iter as $f) {
        if ($f->isFile() && 'php' === $f->getExtension()) {
            $dateien[] = str_replace('\\', '/', $f->getPathname());
        }
    }
}

sort($dateien);

// Welche Klassen gibt es im Bundle? Namensraum -> Kurzname
$vorhanden = [];
foreach ($dateien as $d) {
    $z = zerlegen($d);
    $vorhanden[$z['namespace'].'\\'.basename($d, '.php')] = true;
}

$probleme = 0;
$geprueft = 0;

foreach ($dateien as $d) {
    $z = zerlegen($d);
    $kurz = basename($d);

    foreach ($z['benutzt'] as [$name, $zeile]) {
        ++$geprueft;

        if (isset($z['importe'][$name]) || in_array($name, $eingebaut, true)) {
            continue;
        }

        // Klasse im selben Namensraum?
        if (isset($vorhanden[$z['namespace'].'\\'.$name])) {
            continue;
        }

        // Eigener Klassenname (die Datei selbst)
        if ($name === basename($d, '.php')) {
            continue;
        }

        ++$probleme;
        printf(
            "UNAUFGELÖST  %s:%d  %s\n              -> weder importiert noch als %s\\%s vorhanden\n",
            $kurz,
            $zeile,
            $name,
            $z['namespace'],
            $name,
        );
    }
}

printf("\n%d Dateien, %d Klassenverweise geprüft, %d unaufgelöst\n", count($dateien), $geprueft, $probleme);

exit($probleme > 0 ? 1 : 0);
