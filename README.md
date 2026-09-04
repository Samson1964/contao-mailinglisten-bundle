# Contao Mailinglisten Bundle

Betreibt beliebig viele E-Mail-Verteiler in Contao. Jede Mailingliste hat ein
eigenes Postfach; ein Cronjob holt dort neue Nachrichten per IMAP ab und gibt
sie an die Teilnehmer weiter. Nachrichten von Absendern, die nicht zur Liste
gehören, werden abgewiesen — mit einem Kennwort im Betreff lässt sich die
Aufnahme beantragen.

Läuft unter **Contao 4.13 LTS** und **Contao 5**.

## Was das Bundle tut

* **Beliebig viele Listen.** Jede mit eigener Adresse, eigenem Postfach und
  eigenem Versandweg.
* **Verteilung nur für Berechtigte.** Wer nicht als aktiver Teilnehmer
  eingetragen ist, kommt nicht durch. Senden und Empfangen sind getrennte
  Rechte: Ein Teilnehmer kann nur mitlesen, ein anderer nur einreichen.
* **Aufnahme per E-Mail.** Beginnt der Betreff mit dem eingestellten Kennwort
  (Voreinstellung „Anmeldung"), wird der Absender mit dem Status „beantragt"
  vorgemerkt, bekommt eine Bestätigung, und die Betreuung wird benachrichtigt.
  Freigegeben wird im Backend.
* **Abmeldung per E-Mail.** Mit dem Gegenstück (Voreinstellung „Abmeldung")
  trägt sich ein Teilnehmer selbst aus.
* **Verlauf.** Jede eingegangene Nachricht hinterlässt einen Eintrag: verteilt,
  abgelehnt, Antrag, Abmeldung, verworfen oder Fehler — jeweils mit Begründung.
* **Schleifenschutz.** Abwesenheitsnotizen, Unzustellbarkeitsberichte und
  Nachrichten, die die Liste selbst versendet hat, werden erkannt und
  stillschweigend verworfen.
* **Kennwörter verschlüsselt.** Die Postfach-Kennwörter liegen mit libsodium
  verschlüsselt in der Datenbank und erscheinen nie im Backend-Formular.

## Installation

Über den Contao Manager oder per Composer:

```bash
composer require schachbulle/contao-mailinglisten-bundle
```

Anschließend die Datenbank aktualisieren (Contao Manager → Systemwartung →
Datenbank aktualisieren, oder `vendor/bin/contao-console contao:migrate`).

**Voraussetzungen:** PHP 8.1 oder neuer mit den Erweiterungen `sodium` und
`json`. Die PHP-Erweiterung `imap` wird **nicht** gebraucht — der IMAP-Zugriff
läuft über die Bibliothek `webklex/php-imap`, die Composer mitinstalliert.

## Schnellstart

1. Im Backend unter **Inhalte → Mailinglisten** eine Liste anlegen: Name,
   Adresse und die Zugangsdaten des Postfachs.
2. Prüfen, ob das Postfach erreichbar ist:

```bash
vendor/bin/contao-console contao:mailingliste:abrufen --pruefen
```

3. Über die Operation **Teilnehmer** die ersten Adressen eintragen.
4. Die Liste aktivieren.
5. Sicherstellen, dass Contaos Cron tatsächlich läuft — siehe unten.

## Der Cronjob

Das Bundle hängt sich mit dem Intervall `minutely` in Contaos Cron ein. Wie oft
eine einzelne Liste wirklich abgefragt wird, bestimmt deren Feld
**Prüfintervall** (Voreinstellung: 5 Minuten).

Wichtig: In der Voreinstellung stößt Contao seinen Cron über Seitenaufrufe von
Besuchern an. Auf einer wenig besuchten Seite bedeutet das, dass Nachrichten
stundenlang liegen bleiben. Für eine Mailingliste gehört deshalb ein echter
Cronjob eingerichtet:

```bash
* * * * * /usr/bin/php /pfad/zum/projekt/vendor/bin/contao-console contao:cron
```

Von Hand lässt sich der Abruf jederzeit anstoßen:

```bash
vendor/bin/contao-console contao:mailingliste:abrufen        # alle aktiven Listen
vendor/bin/contao-console contao:mailingliste:abrufen 3      # nur Liste mit ID 3
vendor/bin/contao-console contao:mailingliste:abrufen 3 --pruefen
```

`--pruefen` verbindet sich nur und zählt die ungelesenen Nachrichten. Es wird
nichts verteilt, nichts markiert und nichts gelöscht — der Aufruf lässt sich
gefahrlos wiederholen.

## Ausführliche Dokumentation

* [Einrichtung einer Liste](docs/einrichtung.md) — alle Felder im Einzelnen,
  mit den üblichen Zugangsdaten gängiger Anbieter
* [Wie die Verteilung entscheidet](docs/verteilung.md) — die Reihenfolge der
  Prüfungen, Aufnahme, Abmeldung und Ablehnung
* [Betrieb und Fehlersuche](docs/betrieb.md) — Verlauf lesen, Spam-Vermeidung,
  Kennwörter und das Anwendungsgeheimnis

## Lizenz

LGPL-3.0-or-later. Siehe [LICENSE](LICENSE).
