<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

/**
 * Eine aus dem Postfach gelesene E-Mail, auf das Nötige eingedampft.
 *
 * Das Wertobjekt hält die Verteilerlogik von der IMAP-Bibliothek fern: Der
 * Verteiler kennt nur diese Klasse, nicht die Nachrichtenobjekte von
 * webklex/php-imap. Ein Wechsel der Bibliothek berührt deshalb nur den Leser.
 */
final class EingehendeNachricht
{
    /**
     * Baut die Nachricht aus den bereits ausgewerteten Bestandteilen.
     *
     * @param string                     $messageId  Der Message-ID-Kopf ohne spitze
     *                                               Klammern. Dient als Schlüssel gegen
     *                                               doppelte Verteilung und kann leer
     *                                               sein, wenn der Absender keinen
     *                                               gesetzt hat.
     * @param string                     $absender   E-Mail-Adresse des Absenders,
     *                                               bereits in Kleinbuchstaben
     * @param string                     $absenderName Angezeigter Name des Absenders,
     *                                                 kann leer sein
     * @param string                     $betreff    Betreff, bereits dekodiert
     * @param string                     $text       Inhalt als reiner Text; leer, wenn
     *                                               die Nachricht nur HTML enthielt
     * @param string                     $html       Inhalt als HTML; leer, wenn die
     *                                               Nachricht nur Text enthielt
     * @param array<string, string>      $kopfzeilen Weitere Kopfzeilen, Schlüssel in
     *                                               Kleinbuchstaben. Gebraucht werden
     *                                               vor allem 'auto-submitted',
     *                                               'precedence' und die eigene
     *                                               Listenkennung.
     * @param array<int, array{name: string, inhalt: string, mimetyp: string}> $anhaenge
     *                                               Dateianhänge mit Namen, Rohinhalt
     *                                               und MIME-Typ
     * @param \DateTimeImmutable|null    $datum      Versanddatum laut Kopfzeile, oder
     *                                               null wenn es fehlt oder unlesbar ist
     * @param string                     $kennung    Bezeichner der Nachricht im
     *                                               Postfach (IMAP-UID). Der Leser
     *                                               braucht ihn, um die Nachricht
     *                                               hinterher zu markieren.
     */
    public function __construct(
        public readonly string $messageId,
        public readonly string $absender,
        public readonly string $absenderName,
        public readonly string $betreff,
        public readonly string $text,
        public readonly string $html,
        public readonly array $kopfzeilen = [],
        public readonly array $anhaenge = [],
        public readonly ?\DateTimeImmutable $datum = null,
        public readonly string $kennung = '',
    ) {
    }

    /**
     * Liest eine einzelne Kopfzeile.
     *
     * @param string $name Name der Kopfzeile, Groß- und Kleinschreibung egal
     *
     * @return string Der Wert, oder '' wenn die Kopfzeile fehlt
     */
    public function kopfzeile(string $name): string
    {
        return $this->kopfzeilen[strtolower($name)] ?? '';
    }

    /**
     * Erkennt maschinell erzeugte Nachrichten, die nicht verteilt werden dürfen.
     *
     * Abwesenheitsnotizen, Unzustellbarkeitsberichte und ähnliche Automaten
     * würden sonst an alle Teilnehmer gehen und ihrerseits weitere Automaten
     * auslösen — ein Kreislauf, der eine Liste innerhalb von Minuten
     * unbrauchbar macht. Geprüft werden die drei in der Praxis üblichen
     * Merkmale sowie der leere Absender, mit dem Mailserver ihre Berichte
     * kennzeichnen.
     *
     * @return bool true, wenn die Nachricht stillschweigend zu verwerfen ist
     */
    public function istAutomatisch(): bool
    {
        if ('' === $this->absender) {
            return true;
        }

        $autoSubmitted = strtolower($this->kopfzeile('auto-submitted'));

        if ('' !== $autoSubmitted && 'no' !== $autoSubmitted) {
            return true;
        }

        if ('' !== $this->kopfzeile('x-autoreply') || '' !== $this->kopfzeile('x-autorespond')) {
            return true;
        }

        return \in_array(strtolower($this->kopfzeile('precedence')), ['bulk', 'auto_reply', 'junk'], true);
    }

    /**
     * Gibt den Inhalt zurück, der beim Weiterverteilen als Text dienen soll.
     *
     * Fehlt der Textteil, wird das HTML grob entkleidet. Das Ergebnis ist
     * nicht schön, aber Empfänger mit reinem Textprogramm bekommen dann
     * wenigstens den Wortlaut statt einer leeren Nachricht.
     *
     * @return string Der Textinhalt, gegebenenfalls aus dem HTML gewonnen
     */
    public function textOderAusHtml(): string
    {
        if ('' !== trim($this->text)) {
            return $this->text;
        }

        if ('' === trim($this->html)) {
            return '';
        }

        // Zeilenumbrüche vor dem Entfernen der Auszeichnungen retten, sonst
        // klebt der gesamte Text in einer einzigen Zeile. Ein Absatzende
        // ergibt eine Leerzeile, ein einfacher Umbruch nur einen Zeilenwechsel.
        $roh = preg_replace('#</(p|div|tr|h[1-6])\s*>#i', "\n\n", $this->html) ?? $this->html;
        $roh = preg_replace('#<(br|/li)[^>]*>#i', "\n", $roh) ?? $roh;

        $text = html_entity_decode(strip_tags($roh), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Verschachtelte Auszeichnungen erzeugen leicht drei und mehr
        // Leerzeilen hintereinander; das sieht nach Fehler aus.
        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
