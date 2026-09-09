<?php

declare(strict_types=1);

/**
 * Holt alle XML-Berichte aus einer .eml-Datei.
 *
 * Der Anhang steckt base64-kodiert in der Nachricht und ist je nach Anbieter
 * ein gzip- oder ein zip-Archiv. Statt die MIME-Struktur zu zerlegen, werden
 * alle base64-Blöcke dekodiert und an der Kennung am Dateianfang erkannt
 * (1f 8b für gzip, PK für zip) — das ist gegen die Formatvielfalt der
 * Absender unempfindlich.
 *
 * @param string $datei Pfad zur Nachricht
 *
 * @return array<int, string> Die XML-Inhalte; leer, wenn kein Anhang erkannt
 *                            wurde
 */
function berichteAus(string $datei): array
{
    $roh = file_get_contents($datei);
    $gefunden = [];

    if (!preg_match_all('/(?:[A-Za-z0-9+\/=]{60,}\r?\n){4,}[A-Za-z0-9+\/=]*={0,2}/', $roh, $treffer)) {
        return [];
    }

    foreach ($treffer[0] as $block) {
        $rohdaten = base64_decode(preg_replace('/\s+/', '', $block), true);

        if (false === $rohdaten || 4 > \strlen($rohdaten)) {
            continue;
        }

        if ("\x1f\x8b" === substr($rohdaten, 0, 2)) {
            $xml = @gzdecode($rohdaten);

            if (false !== $xml) {
                $gefunden[] = $xml;
            }

            continue;
        }

        if ('PK' === substr($rohdaten, 0, 2)) {
            $tmp = tempnam(sys_get_temp_dir(), 'dmarc');
            file_put_contents($tmp, $rohdaten);
            $zip = new ZipArchive();

            if (true === $zip->open($tmp)) {
                for ($i = 0; $i < $zip->numFiles; ++$i) {
                    $inhalt = $zip->getFromIndex($i);

                    if (false !== $inhalt && str_contains($inhalt, '<feedback')) {
                        $gefunden[] = $inhalt;
                    }
                }

                $zip->close();
            }

            unlink($tmp);
        }
    }

    return $gefunden;
}
