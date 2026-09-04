<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Verteiler;

/**
 * Was ein Durchgang über eine Mailingliste bewirkt hat.
 *
 * Der Cronjob fasst die Ergebnisse aller Listen zu einer Zeile im
 * Contao-Protokoll zusammen. Ohne solche Zahlen bliebe unklar, ob ein
 * ruhiger Verteiler an leeren Postfächern oder an einem Fehler liegt.
 */
final class Verteilergebnis
{
    /**
     * @param int $gelesen   Wie viele Nachrichten aus dem Postfach geholt wurden
     * @param int $verteilt  Wie viele davon an die Teilnehmer gingen
     * @param int $abgelehnt Wie viele von fremden Absendern kamen
     * @param int $antraege  Wie viele Aufnahmeanträge dabei waren
     * @param int $abmeldungen Wie viele Abmeldungen dabei waren
     * @param int $ignoriert Wie viele stillschweigend verworfen wurden
     *                       (Automaten, eigene Nachrichten, Dubletten)
     * @param int $fehler    Bei wie vielen die Verarbeitung scheiterte
     */
    public function __construct(
        public readonly int $gelesen = 0,
        public readonly int $verteilt = 0,
        public readonly int $abgelehnt = 0,
        public readonly int $antraege = 0,
        public readonly int $abmeldungen = 0,
        public readonly int $ignoriert = 0,
        public readonly int $fehler = 0,
    ) {
    }

    /**
     * Fasst zwei Ergebnisse zu einem zusammen.
     *
     * Der Cronjob addiert damit die Ergebnisse der einzelnen Listen, ohne
     * Zählvariablen durch die Methoden zu reichen.
     *
     * @param self $weiteres Das hinzuzurechnende Ergebnis
     *
     * @return self Ein neues Ergebnis mit den Summen; die Ausgangsobjekte
     *              bleiben unverändert
     */
    public function plus(self $weiteres): self
    {
        return new self(
            $this->gelesen + $weiteres->gelesen,
            $this->verteilt + $weiteres->verteilt,
            $this->abgelehnt + $weiteres->abgelehnt,
            $this->antraege + $weiteres->antraege,
            $this->abmeldungen + $weiteres->abmeldungen,
            $this->ignoriert + $weiteres->ignoriert,
            $this->fehler + $weiteres->fehler,
        );
    }

    /**
     * Gibt das Ergebnis als lesbare Zeile für das Protokoll aus.
     *
     * @return string Etwa "3 gelesen, 2 verteilt, 1 abgelehnt"
     */
    public function alsText(): string
    {
        return sprintf(
            '%d gelesen, %d verteilt, %d abgelehnt, %d Anträge, %d Abmeldungen, %d ignoriert, %d Fehler',
            $this->gelesen,
            $this->verteilt,
            $this->abgelehnt,
            $this->antraege,
            $this->abmeldungen,
            $this->ignoriert,
            $this->fehler,
        );
    }
}
