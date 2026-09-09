<?php

declare(strict_types=1);

/*
 * Zeigt aus DMARC-Aggregatberichten genau die Datensätze, die DMARC NICHT
 * bestehen — also solche, bei denen weder SPF noch DKIM ausgerichtet ist.
 *
 * Nur diese Nachrichten wären von einer Anhebung auf `p=quarantine`
 * betroffen. Alles andere ist Beiwerk: Ein SPF-Fehler bei bestandenem DKIM
 * (typisch für Weiterleitungen und Bounce-Server) ist für DMARC belanglos.
 *
 * Aufruf: php dmarc-fehler.php <Verzeichnis>
 */

require __DIR__.'/dmarc-gemeinsam.php';

$verzeichnis = $argv[1] ?? '.';
$dateien = glob(rtrim($verzeichnis, '/\\').'/*.eml');
$fehler = [];
$gesamt = 0;

foreach ($dateien as $datei) {
    foreach (berichteAus($datei) as $xml) {
        $baum = @simplexml_load_string($xml);

        if (false === $baum) {
            continue;
        }

        $melder = (string) ($baum->report_metadata->org_name ?? '?');

        foreach ($baum->record as $satz) {
            $anzahl = (int) $satz->row->count;
            $gesamt += $anzahl;
            $pol = $satz->row->policy_evaluated;

            // DMARC besteht, sobald EINES von beiden ausgerichtet ist.
            if ('pass' === (string) $pol->spf || 'pass' === (string) $pol->dkim) {
                continue;
            }

            $spfDetails = [];

            foreach ($satz->auth_results->spf ?? [] as $s) {
                $spfDetails[] = sprintf('%s=%s', (string) $s->domain, (string) $s->result);
            }

            $dkimDetails = [];

            foreach ($satz->auth_results->dkim ?? [] as $d) {
                $dkimDetails[] = sprintf('%s/%s=%s', (string) $d->domain, (string) $d->selector, (string) $d->result);
            }

            $ip = (string) $satz->row->source_ip;
            $schluessel = $ip.'|'.implode(',', $spfDetails).'|'.implode(',', $dkimDetails);

            $fehler[$schluessel] ??= [
                'ip' => $ip,
                'anzahl' => 0,
                'spf' => implode(', ', $spfDetails) ?: '—',
                'dkim' => implode(', ', $dkimDetails) ?: '— (nicht signiert)',
                'von' => (string) ($satz->identifiers->header_from ?? '?'),
                'disp' => (string) $pol->disposition,
                'melder' => [],
            ];
            $fehler[$schluessel]['anzahl'] += $anzahl;
            $fehler[$schluessel]['melder'][$melder] = true;
        }
    }
}

uasort($fehler, static fn ($a, $b) => $b['anzahl'] <=> $a['anzahl']);

$summe = array_sum(array_column($fehler, 'anzahl'));

printf(
    "NICHT ausgerichtete Nachrichten: %d von %d (%.2f %%)\n\n",
    $summe,
    $gesamt,
    $gesamt > 0 ? 100 * $summe / $gesamt : 0,
);

foreach ($fehler as $f) {
    $name = gethostbyaddr($f['ip']);

    printf("%d Nachricht(en)  %s  (%s)\n", $f['anzahl'], $f['ip'], $name === $f['ip'] ? 'kein Umkehrname' : $name);
    printf("    Absenderdomäne im Kopf: %s\n", $f['von']);
    printf("    SPF:  %s\n", $f['spf']);
    printf("    DKIM: %s\n", $f['dkim']);
    printf("    gemeldet von: %s\n\n", implode(', ', array_keys($f['melder'])));
}
