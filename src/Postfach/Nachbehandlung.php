<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

/**
 * Was mit einer Nachricht geschehen soll, nachdem sie verarbeitet wurde.
 *
 * Der Verteiler gibt für jede Nachricht einen dieser Werte zurück; der
 * Postfach-Leser setzt ihn um. Dadurch entscheidet die Fachlogik über das
 * Schicksal der Nachricht, ohne die IMAP-Bibliothek zu kennen.
 */
enum Nachbehandlung
{
    /**
     * Nachricht unverändert und ungelesen im Postfach lassen.
     *
     * Gedacht für Fehlerfälle: Der nächste Cron-Lauf soll es noch einmal
     * versuchen. Vorsicht — bei einem dauerhaften Fehler bleibt die Nachricht
     * liegen und wird bei jedem Lauf erneut angefasst.
     */
    case Behalten;

    /**
     * Nachricht als gelesen markieren, aber im Ordner belassen.
     *
     * Der Regelfall. Die Nachricht bleibt für den Betreuer nachvollziehbar,
     * wird aber beim nächsten Lauf nicht mehr geholt.
     */
    case Gelesen;

    /**
     * Nachricht in den in der Liste eingestellten Ordner verschieben.
     *
     * Ist dort kein Ordner hinterlegt, verhält sich der Leser wie bei
     * `Gelesen` — ein fehlender Zielordner darf keine Nachricht verlieren.
     */
    case Verschieben;

    /**
     * Nachricht löschen.
     *
     * Für Postfächer gedacht, die nur als Durchlauf dienen und nicht volllaufen
     * sollen.
     */
    case Loeschen;
}
