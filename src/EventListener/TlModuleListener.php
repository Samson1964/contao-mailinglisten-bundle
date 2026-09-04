<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\EventListener;

use Contao\DataContainer;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;

/**
 * Rückruf für die Modul-Einstellungen des Anmeldeformulars.
 *
 * Ein `options_callback` statt fester Werte in der DCA-Datei ist hier Pflicht:
 * Die Auswahl hängt vom Datenbestand ab, und DCA-Dateien werden in Contao 5
 * zwischengespeichert — eine Closure oder eine zur Ladezeit aufgebaute Liste
 * überstünde das Zwischenspeichern nicht.
 */
class TlModuleListener
{
    /**
     * Liefert die auswählbaren Mailinglisten.
     *
     * Angeboten werden **alle** Listen, auch abgeschaltete. Wer ein Modul
     * vorbereitet, während die Liste noch nicht veröffentlicht ist, soll sie
     * schon zuordnen können; das Modul selbst zeigt dann im Frontend nichts an,
     * bis die Liste aktiv ist.
     *
     * @param DataContainer|null $dc Der Data Container; wird nicht ausgewertet,
     *                               gehört aber zur Signatur des Rückrufs
     *
     * @return array<int, string> Listen-ID als Schlüssel, Titel mit Adresse als
     *                            Beschriftung; leer, wenn noch keine Liste
     *                            angelegt wurde
     */
    public function listenOptionen(?DataContainer $dc = null): array
    {
        $listen = MailinglistenModel::findAll(['order' => 'titel']);

        if (null === $listen) {
            return [];
        }

        $optionen = [];

        foreach ($listen as $liste) {
            $optionen[(int) $liste->id] = sprintf(
                '%s (%s)%s',
                $liste->titel,
                $liste->adresse,
                $liste->published ? '' : ' — nicht aktiv',
            );
        }

        return $optionen;
    }
}
