<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

/**
 * Die Zugangsdaten eines IMAP-Postfachs als unveränderliches Wertobjekt.
 *
 * Die Klasse trennt die Zugangsdaten vom Datensatz der Mailingliste. Dadurch
 * lässt sich der Postfach-Leser prüfen, ohne dass eine Datenbank oder ein
 * Contao-Model im Spiel ist, und ein Kennwort taucht in keiner
 * Fehlerausgabe eines Models auf.
 */
final class Postfachzugang
{
    /**
     * Legt einen Zugang mit allen für IMAP nötigen Angaben an.
     *
     * @param string $host            Rechnername des IMAP-Servers, ohne Protokoll
     * @param int    $port            Üblich sind 993 (SSL) und 143 (TLS oder unverschlüsselt)
     * @param string $verschluesselung 'ssl', 'tls' oder '' für unverschlüsselt.
     *                                 Andere Werte behandelt der Leser wie ''.
     * @param string $benutzer        Anmeldename, bei den meisten Anbietern die
     *                                vollständige E-Mail-Adresse
     * @param string $kennwort        Kennwort im Klartext. Es wird nur für die
     *                                Dauer des Abrufs gehalten; in der
     *                                Datenbank steht es verschlüsselt.
     * @param string $ordner          Zu lesender IMAP-Ordner, in aller Regel 'INBOX'
     * @param bool   $zertifikatPruefen Wenn false, werden selbstsignierte
     *                                  Zertifikate hingenommen. Nur für
     *                                  Testaufbauten gedacht.
     * @param string $ordnerErledigt  Zielordner, wenn verarbeitete Nachrichten
     *                                verschoben werden sollen; leer, wenn
     *                                nicht verschoben wird
     */
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $verschluesselung,
        public readonly string $benutzer,
        public readonly string $kennwort,
        public readonly string $ordner = 'INBOX',
        public readonly bool $zertifikatPruefen = true,
        public readonly string $ordnerErledigt = '',
    ) {
    }

    /**
     * Prüft, ob die Angaben für einen Verbindungsversuch überhaupt reichen.
     *
     * Der Cronjob überspringt Listen mit unvollständigem Zugang, statt in
     * einen Verbindungsfehler zu laufen und ihn zu protokollieren — eine
     * gerade erst angelegte, noch nicht fertig ausgefüllte Liste soll kein
     * Fehlerrauschen erzeugen.
     *
     * @return bool true, wenn Host, Benutzer und Kennwort gesetzt sind
     */
    public function istVollstaendig(): bool
    {
        return '' !== $this->host && '' !== $this->benutzer && '' !== $this->kennwort;
    }
}
