# Changelog

Alle nennenswerten Änderungen an diesem Bundle.

## Version 1.0.0 (2026-09-04)

Erste Fassung.

* Add: Beliebig viele Mailinglisten (`tl_mailingliste`), jede mit eigener
  Adresse, eigenem IMAP-Postfach und eigenem SMTP-Versandweg.
* Add: Teilnehmerverwaltung (`tl_mailingliste_abonnent`) mit den Zuständen
  aktiv, beantragt und gesperrt sowie getrennten Rechten für Senden und
  Empfangen.
* Add: Verlauf (`tl_mailingliste_protokoll`) über jede eingegangene Nachricht,
  mit Begründung bei Ablehnungen und Fehlern.
* Add: Cronjob im Minutentakt; das tatsächliche Prüfintervall steht je Liste in
  der Datenbank.
* Add: Aufnahmeantrag per Kennwort am Betreffanfang, mit Bestätigung an den
  Antragsteller, Mitteilung an die Betreuung und Freigabe im Backend.
* Add: Abmeldung per Kennwort am Betreffanfang.
* Add: Schleifenschutz — eigene Nachrichten (Kopfzeile
  `X-Contao-Mailingliste`), maschinelle Antworten (`Auto-Submitted`,
  `Precedence`, `X-Autoreply`) und bereits verarbeitete Message-IDs werden
  verworfen.
* Add: Verschlüsselung der Postfach-Kennwörter mit libsodium; der Schlüssel
  wird aus `APP_SECRET` abgeleitet, das Kennwort erscheint nie im Formular.
* Add: Konsolenbefehl `contao:mailingliste:abrufen`, mit `--pruefen` für einen
  Verbindungstest ohne jede Veränderung am Postfach.
* Add: Listenkopfzeilen nach RFC 2919 und RFC 2369 (`List-Id`, `List-Post`,
  `Precedence`), Absender immer die Listenadresse — sonst reißt die SPF-Prüfung
  beim Empfänger.
* Add: Deutsche und englische Sprachdateien.
* Add: Dokumentation unter `docs/` zu Einrichtung, Verteilungsregeln und
  Betrieb.
