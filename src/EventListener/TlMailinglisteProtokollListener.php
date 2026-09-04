<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\EventListener;

use Contao\System;

/**
 * Rückruf für die Darstellung des Verlaufs.
 *
 * Der Verlauf wird gelesen, wenn etwas schiefgegangen ist. Deshalb steht die
 * Aktion vorn und farbig, und die Begründung — warum abgelehnt, wie viele
 * erreicht — gehört sichtbar in dieselbe Zeile, statt sich hinter der
 * Detailansicht zu verstecken.
 */
class TlMailinglisteProtokollListener
{
    /**
     * Stellt einen Protokolleintrag in der Übersicht dar.
     *
     * @param array<string, mixed> $row Der Datensatz des Eintrags
     *
     * @return string HTML für die Zeile in der Übersicht
     */
    public function kindDatensatz(array $row): string
    {
        System::loadLanguageFile('tl_mailingliste_protokoll');

        $aktion = (string) $row['aktion'];
        $beschriftung = $GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen'][$aktion] ?? $aktion;

        $farbe = match ($aktion) {
            'verteilt' => '#4a8f2a',
            'abgelehnt' => '#a61c00',
            'fehler' => '#a61c00',
            'antrag' => '#b45f06',
            'abmeldung' => '#b45f06',
            default => '#999999',
        };

        $zusatz = [];

        if ((int) $row['empfaenger'] > 0) {
            $zusatz[] = sprintf('%d Empfänger', (int) $row['empfaenger']);
        }

        if ('' !== trim((string) ($row['meldung'] ?? ''))) {
            $zusatz[] = (string) $row['meldung'];
        }

        return sprintf(
            '<div class="tl_content_left"><span style="color:#999">%s</span> <strong style="color:%s">%s</strong> &ndash; %s: %s%s</div>',
            date('d.m.Y H:i', (int) $row['datum']),
            $farbe,
            htmlspecialchars((string) $beschriftung, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $row['absender'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $row['betreff'], ENT_QUOTES, 'UTF-8'),
            $zusatz ? ' <span style="color:#999">('.htmlspecialchars(implode('; ', $zusatz), ENT_QUOTES, 'UTF-8').')</span>' : '',
        );
    }
}
