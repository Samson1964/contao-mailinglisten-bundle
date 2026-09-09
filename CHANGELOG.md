# Changelog

Alle nennenswerten Änderungen an diesem Bundle.

## Version 1.1.0 (2026-09-09)

* Add: Anonymes Schreiben. Ein Teilnehmer kann seine Beiträge ohne Name und
  Adresse erscheinen lassen; statt dessen steht dort „[Anonym]“. Die
  Antwortadresse zeigt dann zwingend auf die Liste, sonst gäbe ein Klick auf
  „Antworten“ den Verfasser preis. Umschalten per Kennwort im Betreff
  (neues Feld „Kennwort für anonymes Schreiben“) oder als Ankreuzfeld im
  Anmeldeformular; die Betreuung sieht den Zustand in der Teilnehmerliste.
* Add: Mehr Beispieltexte im Hilfe-Assistenten — für den Ablehnungstext und
  die Antragsbestätigung, dazu ein Abschnitt über anonyme Teilnehmer.
* Fix: `##absendername##` lieferte den Rohwert aus dem `From`-Kopf der
  eingegangenen Nachricht statt des gepflegten Namens aus dem
  Teilnehmerdatensatz. Im Betreff stand damit „Max Mustermann via …“, in der
  Fußzeile daneben etwas anderes — oder nichts.

## Version 1.0.2 (2026-09-04)

* Fix: Der Hilfe-Assistent blieb leer. `explanation` stand in `eval`, gelesen
  wird es aber von der Feldebene des DCA (`BackendHelp` in beiden
  Contao-Fassungen). `helpwizard` gehört dagegen weiterhin in `eval` — die
  Aufteilung ist der Stolperstein.

## Version 1.0.1 (2026-09-04)

* Add: Hilfe-Assistent an der Fußzeile, am Ablehnungstext und am Text der
  Antragsbestätigung. Er erklärt jeden Platzhalter, wodurch er ersetzt wird
  und mit welchem Beispielwert, dazu den selbsttätigen Abmeldehinweis und die
  Frage, wo welcher Text erscheint.

## Version 1.0.0 (2026-09-04)

Erste Fassung.

* Add: Beliebig viele Mailinglisten (`tl_mailinglisten`), jede mit eigener
  Adresse, eigenem IMAP-Postfach und eigenem SMTP-Versandweg.
* Add: Teilnehmerverwaltung (`tl_mailinglisten_abonnent`) mit den Zuständen
  aktiv, beantragt und gesperrt sowie getrennten Rechten für Senden und
  Empfangen.
* Add: Verlauf (`tl_mailinglisten_protokoll`) über jede eingegangene Nachricht,
  mit Begründung bei Ablehnungen und Fehlern.
* Add: Cronjob im Minutentakt; das tatsächliche Prüfintervall steht je Liste in
  der Datenbank.
* Add: Aufnahmeantrag per Kennwort am Betreffanfang, mit Bestätigung an den
  Antragsteller, Mitteilung an die Betreuung und Freigabe im Backend.
* Add: Abmeldung per Kennwort am Betreffanfang.
* Add: Frontend-Modul „Anmeldung zur Mailingliste“. Die eingetragene Adresse
  wird per Bestätigungslink geprüft (Status „Bestätigung ausstehend“), erst
  danach entsteht ein Antrag und die Betreuung wird benachrichtigt. Die Meldung
  auf dem Bildschirm ist in jedem Fall dieselbe, damit sich über das Formular
  nicht abfragen lässt, wer Teilnehmer der Liste ist. Mit unsichtbarem
  Fangfeld gegen Formularroboter und einstellbarem Datenschutzhinweis.
* Add: Schleifenschutz — eigene Nachrichten (Kopfzeile
  `X-Contao-Mailingliste`), maschinelle Antworten (`Auto-Submitted`,
  `Precedence`, `X-Autoreply`) und bereits verarbeitete Message-IDs werden
  verworfen.
* Add: Verschlüsselung der Postfach-Kennwörter mit libsodium; der Schlüssel
  wird aus `APP_SECRET` abgeleitet, das Kennwort erscheint nie im Formular.
* Add: Meldung an die Betreuung bei jeder abgewiesenen Nachricht, mit
  Absender, Betreff, Grund und dem Anfang des Textes; je Liste abschaltbar
  (`ablehnungMelden`).
* Add: Konsolenbefehl `contao:mailingliste:abrufen`, mit `--pruefen` für einen
  Verbindungstest ohne jede Veränderung am Postfach. Die Prüfung nennt auch,
  wie viele Teilnehmer eine Verteilung tatsächlich erreichen würde.
* Add: Listenkopfzeilen nach RFC 2919 und RFC 2369 (`List-Id`, `List-Post`,
  `Precedence`), Absender immer die Listenadresse — sonst reißt die SPF-Prüfung
  beim Empfänger.
* Add: `List-Unsubscribe` an jeder verteilten Nachricht, mit der Abmeldekennung
  der Liste. Google und Yahoo verlangen den Kopf seit Februar 2024 von
  Massenversendern, Microsoft bewertet ihn ebenso; sein Fehlen gilt als
  Spam-Merkmal.
* Add: Sichtbarer Abmeldehinweis in der Fußzeile jeder verteilten Nachricht.
  `List-Unsubscribe` allein genügt nicht — Thunderbird zeigt den Kopf nur unter
  bestimmten Bedingungen an, die Mailprogramme der Mobiltelefone meist gar
  nicht. Erwähnt die eingestellte Fußzeile den Abmeldeweg bereits, bleibt es
  bei ihrem Wortlaut.
* Add: Willkommensnachricht an den Teilnehmer, sobald die Betreuung ihn im
  Backend auf „aktiv“ setzt. Bis dahin endete der Aufnahmeweg im Nichts.
* Change: Der angezeigte Name des Verfassers stammt jetzt aus dem
  Teilnehmerdatensatz (Vor- und Nachname) statt aus dem `From`-Kopf der
  eingegangenen Nachricht. Dort steht oft nichts oder nur der Teil vor dem
  Klammeraffen, und im Betreff erschien dann „frank.binding via …“ statt
  „Frank Binding via …“.
* Add: Deutsche und englische Sprachdateien.
* Add: Dokumentation unter `docs/` zu Einrichtung, Verteilungsregeln und
  Betrieb.
