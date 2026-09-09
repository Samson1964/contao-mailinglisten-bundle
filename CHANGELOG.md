# Changelog

Alle nennenswerten Änderungen an diesem Bundle.

## Version 1.3.0 (2026-09-09)

* Add: Platzhalter `##abmeldelink##` für die Fußzeile. Er liefert die
  persönliche Abmeldeadresse des jeweiligen Empfängers — dieselbe, die
  auch in der Kopfzeile `List-Unsubscribe` steht, nur eben sichtbar. Das
  hilft überall dort, wo das Mailprogramm den Abmeldeknopf aus den
  Kopfzeilen nicht anbietet; Thunderbird etwa zeigt ihn nicht. Im
  HTML-Teil wird die Adresse zusätzlich zu einem echten Verweis.
* Add: Ist keine Basisadresse hinterlegt oder steht kein einzelner
  Empfänger fest, **entfällt die ganze Zeile**, in der der Platzhalter
  steht. Ein Satz ohne Ziel wäre schlimmer als kein Hinweis. Das gilt nur
  für diesen einen Platzhalter; alle übrigen verhalten sich unverändert.
* Change: Kopfzeile und Fußzeile bilden die Abmeldeadresse jetzt an
  **einer** Stelle. Stünde die Bildung an zweien, liefen sie beim
  nächsten Umbau der Route auseinander und einer der Wege ginge still
  ins Leere.
* Change: Der Hilfe-Assistent an der Fußzeile führt den neuen Platzhalter
  samt Beispiel; `docs/einrichtung.md` erklärt, warum die Adresse
  persönlich ist und nur in die Fußzeile gehört.
* Add: `tools/abmeldelink-pruefen.php` sichert das Verhalten ab — mit
  und ohne Basisadresse, mit und ohne Empfänger, Schrägstrich am Ende.

## Version 1.2.4 (2026-09-09)

* Change: Die Beschriftung des Prüfintervalls sagt jetzt, was sie tut.
  „Wie oft das Postfach abgefragt wird" ließ erwarten, dass ein Wert von
  5 auch alle fünf Minuten bedeutet. Tatsächlich kann das Feld den
  Abstand nur **verlängern**, nie verkürzen: Der Dienst läuft bei jedem
  Cron-Durchgang von Contao mit, und erst dort entscheidet das Feld, ob
  die Liste an der Reihe ist. Fragt Contao seinen Cron nur alle zehn
  Minuten ab, wird auch ein Postfach mit „5" nur alle zehn Minuten
  geprüft. `docs/einrichtung.md` beschreibt, wie sich der wirkliche Takt
  aus `tl_cron_job` ablesen lässt.

## Version 1.2.3 (2026-09-09)

* Change: `docs/betrieb.md` erklärt den Versandfehler „Connection
  refused". Viele Anbieter sperren die Ports 25 und 465, damit eine
  gekaperte Webanwendung keinen Spam versenden kann; der
  Submission-Port 587 bleibt dabei offen, weil er eine Anmeldung
  erzwingt. Die Abhilfe ist dann eine Einstellung an der Liste — Port
  587, Verschlüsselung TLS —, kein Eingriff am Server. Der Abschnitt
  nennt den Prüfbefehl, die Unterscheidung von einer Anbieterstörung
  und den Grund, warum ein lokaler Postfix keine Lösung ist: Der
  Webserver steht meist nicht im SPF der Absenderdomäne und hat keinen
  DKIM-Schlüssel dafür.

## Version 1.2.2 (2026-09-09)

* Fix: Statusmeldungen des SMTP-Transports standen als **Fehler** im
  System-Log („Email transport starting"). Ursache war die Neuerung aus
  1.2.1: Der Transport von Symfony bekam denselben Protokollierer wie das
  Bundle, also den in Contaos `SystemLogger` gehüllten Kanal — damit
  bekam jede beiläufige Zeile des Transports einen `ContaoContext` und
  die Aktion „Fehler". Der Transport erhält jetzt den nackten
  Monolog-Kanal; seine Meldungen stehen wieder nur in `var/logs`.
  Gescheiterte Zustellungen protokolliert das Bundle davon unberührt
  selbst — mit Liste, Empfänger und Serverantwort.
* Change: `tools/dienste-pruefen.php` erkennt diesen Fehler künftig. Es
  meldet jeden Protokollierer, der in einen `SystemLogger` gehüllt ist
  und im Quelltext an einen fremden Konstruktor weitergereicht wird.

## Version 1.2.1 (2026-09-09)

* Add: Einträge im System-Log von Contao. Jede verteilte Nachricht
  erscheint dort unter der Aktion „E-Mail", Fehler unter „Fehler", die
  Zusammenfassung eines Cron-Durchgangs unter „Cron". Bisher gingen alle
  Meldungen nur nach `var/logs/`: Der `ContaoTableHandler` verwirft jeden
  Datensatz ohne `ContaoContext`, und der fehlte. Die Dienste hüllen die
  Monolog-Kanäle jetzt in Contaos `SystemLogger`, der in 4.13 und 5.7
  zeichengleich ist.
* Fix: Die Legende „Mailinglisten" bei den Rechten einer **Benutzergruppe**
  zeigte den nackten Schlüssel `mailinglisten_legend`. Die Beschriftung
  einer Legende wird immer unter dem Namen der eigenen Tabelle
  nachgeschlagen; der vorhandene Eintrag unter `tl_user` galt für
  `tl_user_group` nicht mit.
* Add: `tools/dienste-pruefen.php` prüft services.yaml gegen den Quelltext
  — benannte Argumente gegen die Konstruktoren, Verweise auf eigene
  Dienste, Rückruf-Methoden der `contao.callback`-Tags.
* Change: `tools/dca-pruefen.php` prüft jetzt auch die Beschriftung jeder
  Legende und folgt Beschriftungen, die per Referenz aus einer anderen
  Tabelle stammen.
* Add: `tools/dmarc-auswerten.php` und `tools/dmarc-fehler.php` werten
  DMARC-Aggregatberichte aus, wie sie als `.eml` im Postfach liegen — ohne
  die Anhänge von Hand zu entpacken (gzip wie zip). Das erste Werkzeug
  zeigt jede Quell-IP mit SPF- und DKIM-Ergebnis, das zweite nur die
  Nachrichten, die DMARC nicht bestehen. Landen Listennachrichten trotz
  bestandener Prüfungen im Spam, ist das der Weg zur Absenderreputation;
  `docs/betrieb.md` beschreibt ihn.

## Version 1.2.0 (2026-09-09)

* Add: Ein-Klick-Abmeldung nach RFC 8058. Trägt die Liste eine
  Basisadresse, zeigen Mailprogramme einen Abmeldeknopf an; ein Klick
  meldet ohne Rückfrage ab. Eigene Route mit `_token_check: false` — ein
  Frontend-Modul taugt dafür nicht, weil Contao jeden POST ohne
  Anfrage-Merkmal abweist und ein Mailprogramm dieses nicht kennt. Ein
  Aufruf per GET zeigt statt dessen eine Seite mit Schaltfläche, damit
  Sicherheitsprüfungen der Mailanbieter niemanden ungewollt austragen.
* Add: Schalter „Anonymes Schreiben erlauben“ an der Liste. Das Kennwort
  dafür steht jetzt in einer Subpalette und erscheint erst, wenn der
  Schalter gesetzt ist. Wird die Erlaubnis entzogen, bleiben bereits
  anonyme Teilnehmer anonym — es entfallen nur Umschaltung und
  Ankreuzfeld im Anmeldeformular.

## Version 1.1.0 (2026-09-09)

* Add: Anonymes Schreiben. Ein Teilnehmer kann seine Beiträge ohne Name und
  Adresse erscheinen lassen; statt dessen steht dort „[Anonym]“. Die
  Antwortadresse zeigt dann zwingend auf die Liste, sonst gäbe ein Klick auf
  „Antworten“ den Verfasser preis. Umschalten per Kennwort im Betreff
  (neues Feld „Kennwort für anonymes Schreiben“) oder als Ankreuzfeld im
  Anmeldeformular; die Betreuung sieht den Zustand in der Teilnehmerliste.
* Add: Rechte je Benutzer und Benutzergruppe, aufgebaut wie bei den
  Nachrichtenarchiven des Kerns: Auswahl der erlaubten Listen sowie die
  Rechte zum Anlegen und Löschen. Wer eine Liste sehen darf, sieht auch
  deren Teilnehmer und deren Verlauf. Ausgewertet werden die Felder
  unmittelbar statt über `BackendUser::hasAccess()` — die Methode löst seit
  Contao 5.2 eine Deprecation aus und entfällt in Contao 6.
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
