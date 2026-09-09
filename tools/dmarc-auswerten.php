<?php

declare(strict_types=1);

/*
 * Wertet DMARC-Aggregatberichte aus, die als .eml im Postfach liegen.
 *
 * Der Anhang steckt base64-kodiert in der Nachricht und ist je nach Anbieter
 * ein gzip- oder ein zip-Archiv. Statt die MIME-Struktur zu zerlegen, werden
 * alle base64-Blöcke dekodiert und an der Kennung am Dateianfang erkannt
 * (1f 8b für gzip, PK für zip) — das ist gegen die Formatvielfalt der
 * Absender unempfindlich.
 *
 * Aufruf: php dmarc-auswerten.php <Verzeichnis>
 */

$verzeichnis = $argv[1] ?? '.';
$dateien = glob(rtrim($verzeichnis, '/\\').'/*.eml');

if (!$dateien) {
    echo "Keine .eml-Dateien in $verzeichnis\n";

    exit(1);
}

require __DIR__."/dmarc-gemeinsam.php";

$quellen = [];   // IP => ['anzahl'=>int, 'spf'=>[], 'dkim'=>[], 'disp'=>[], 'melder'=>[]]
$zeitraum = ['von' => PHP_INT_MAX, 'bis' => 0];
$berichte = 0;
$ohneAnhang = [];

foreach ($dateien as $datei) {
    $xmls = berichteAus($datei);

    if (!$xmls) {
        $ohneAnhang[] = basename($datei);

        continue;
    }

    foreach ($xmls as $xml) {
        $baum = @simplexml_load_string($xml);

        if (false === $baum) {
            continue;
        }

        ++$berichte;
        $melder = (string) ($baum->report_metadata->org_name ?? '?');
        $zeitraum['von'] = min($zeitraum['von'], (int) ($baum->report_metadata->date_range->begin ?? PHP_INT_MAX));
        $zeitraum['bis'] = max($zeitraum['bis'], (int) ($baum->report_metadata->date_range->end ?? 0));

        foreach ($baum->record as $satz) {
            $ip = (string) $satz->row->source_ip;
            $anzahl = (int) $satz->row->count;
            $pol = $satz->row->policy_evaluated;

            $quellen[$ip] ??= ['anzahl' => 0, 'spf' => [], 'dkim' => [], 'disp' => [], 'melder' => [], 'von' => []];
            $quellen[$ip]['anzahl'] += $anzahl;
            $quellen[$ip]['spf'][(string) $pol->spf] = ($quellen[$ip]['spf'][(string) $pol->spf] ?? 0) + $anzahl;
            $quellen[$ip]['dkim'][(string) $pol->dkim] = ($quellen[$ip]['dkim'][(string) $pol->dkim] ?? 0) + $anzahl;
            $quellen[$ip]['disp'][(string) $pol->disposition] = ($quellen[$ip]['disp'][(string) $pol->disposition] ?? 0) + $anzahl;
            $quellen[$ip]['melder'][$melder] = true;

            foreach ($satz->identifiers->header_from ?? [] as $von) {
                $quellen[$ip]['von'][(string) $von] = true;
            }
        }
    }
}

uasort($quellen, static fn ($a, $b) => $b['anzahl'] <=> $a['anzahl']);

printf(
    "%d Dateien, %d Berichte, Zeitraum %s bis %s\n\n",
    \count($dateien),
    $berichte,
    date('d.m.Y', $zeitraum['von']),
    date('d.m.Y', $zeitraum['bis']),
);

if ($ohneAnhang) {
    printf("Ohne erkennbaren Anhang: %d (%s)\n\n", \count($ohneAnhang), implode(', ', \array_slice($ohneAnhang, 0, 3)));
}

$gesamt = 0;
$bestanden = 0;

printf("%-16s %6s  %-28s  %-22s  %s\n", 'Quell-IP', 'Mails', 'DMARC-Ergebnis', 'Umkehrname', 'Absenderdomäne');
echo str_repeat('-', 118), "\n";

foreach ($quellen as $ip => $d) {
    $gesamt += $d['anzahl'];

    // DMARC besteht, wenn SPF ODER DKIM ausgerichtet ist.
    $ok = ($d['spf']['pass'] ?? 0) + ($d['dkim']['pass'] ?? 0) > 0
        ? min($d['anzahl'], max($d['spf']['pass'] ?? 0, $d['dkim']['pass'] ?? 0))
        : 0;
    $bestanden += $ok;

    $ergebnis = sprintf(
        'SPF %s / DKIM %s',
        implode('+', array_map(static fn ($k, $v) => "$k:$v", array_keys($d['spf']), $d['spf'])),
        implode('+', array_map(static fn ($k, $v) => "$k:$v", array_keys($d['dkim']), $d['dkim'])),
    );

    $name = gethostbyaddr($ip);

    printf(
        "%-16s %6d  %-28s  %-22s  %s\n",
        $ip,
        $d['anzahl'],
        $ergebnis,
        $name === $ip ? '—' : substr($name, 0, 22),
        implode(', ', array_keys($d['von'])),
    );
}

echo str_repeat('-', 118), "\n";
printf(
    "\n%d Nachrichten insgesamt, davon %d ausgerichtet (%.1f %%), %d NICHT ausgerichtet\n",
    $gesamt,
    $bestanden,
    $gesamt > 0 ? 100 * $bestanden / $gesamt : 0,
    $gesamt - $bestanden,
);
