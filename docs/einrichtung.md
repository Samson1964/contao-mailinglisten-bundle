# Einrichtung einer Mailingliste

Eine Liste wird im Backend unter **Inhalte → Mailinglisten** angelegt. Die
Maske ist in sieben Abschnitte geteilt; dieser Text geht sie der Reihe nach
durch.

## Grunddaten

| Feld | Bedeutung |
| --- | --- |
| **Name der Liste** | Erscheint im Betreffkennzeichen und im angezeigten Absendernamen („Max Mustermann via Vorstand"). |
| **Adresse der Liste** | Die Adresse, an die geschrieben wird und unter der die Liste versendet. Sie muss zu dem Postfach gehören, das unten eingetragen wird — sonst gehen Antworten ins Leere. |
| **Beschreibung** | Nur für die eigene Übersicht. Wird nirgends versendet. |

## Postfach (Empfang per IMAP)

Hier steht der Zugang zu dem Postfach, aus dem die Nachrichten geholt werden.

| Feld | Hinweis |
| --- | --- |
| **IMAP-Server** | Rechnername ohne Protokoll, also `imap.example.org`, nicht `imap://…`. |
| **IMAP-Port** | 993 mit SSL, 143 mit STARTTLS. |
| **IMAP-Verschlüsselung** | „SSL/TLS" verschlüsselt ab dem ersten Byte (Port 993). „STARTTLS" baut zuerst eine offene Verbindung auf und schaltet dann um (Port 143). Im Zweifel SSL/TLS auf 993. |
| **IMAP-Benutzername** | Bei den meisten Anbietern die vollständige E-Mail-Adresse. |
| **IMAP-Kennwort** | Wird verschlüsselt gespeichert (siehe [Betrieb](betrieb.md)). |
| **IMAP-Ordner** | In aller Regel `INBOX`. Wer Unterordner benutzt, muss die Schreibweise seines Servers treffen, etwa `INBOX.Liste`. |
| **Zertifikat prüfen** | Bleibt an. Ausschalten nur bei einem Testaufbau mit selbstsigniertem Zertifikat. |

### Nach der Verarbeitung

Was mit einer abgearbeiteten Nachricht geschehen soll:

* **Als gelesen markieren** — der Regelfall. Die Nachricht bleibt liegen und ist
  im Postfach nachvollziehbar.
* **In einen anderen Ordner verschieben** — hält die INBOX sauber. Der Zielordner
  muss auf dem Server **bereits vorhanden** sein; ist er es nicht, wird die
  Nachricht nur als gelesen markiert und bleibt liegen. Verloren geht dabei
  nichts.
* **Löschen** — für Postfächer, die nur als Durchlauf dienen.

## Versand (SMTP)

Die Liste versendet über einen eigenen SMTP-Zugang. Das ist kein Luxus: Nur so
stimmt die Absenderadresse mit dem versendenden Server überein, und nur dann
gehen SPF- und DKIM-Prüfung beim Empfänger auf. Geht die Nachricht über einen
fremden Server hinaus, ist der Spam-Ordner der wahrscheinlichste Zielort.

| Feld | Hinweis |
| --- | --- |
| **SMTP-Server** | Bleibt das Feld leer, versendet die Liste über den allgemeinen Mailer der Contao-Installation. Das funktioniert, hat aber den eben beschriebenen Nachteil. |
| **SMTP-Port** | 587 mit STARTTLS, 465 mit SSL. |
| **SMTP-Verschlüsselung** | Wie beim Empfang. |
| **SMTP-Benutzername** | Leer lassen heißt: ohne Anmeldung versenden. Das erlauben nur wenige Server. |
| **SMTP-Kennwort** | Wird verschlüsselt gespeichert. |

### Übliche Zugangsdaten

| Anbieter | IMAP | SMTP |
| --- | --- | --- |
| All-Inkl | `<server>.kasserver.com`, 993, SSL | `<server>.kasserver.com`, 465, SSL |
| IONOS / 1&1 | `imap.ionos.de`, 993, SSL | `smtp.ionos.de`, 465, SSL |
| Strato | `imap.strato.de`, 993, SSL | `smtp.strato.de`, 465, SSL |
| Mailbox.org | `imap.mailbox.org`, 993, SSL | `smtp.mailbox.org`, 465, SSL |

Bei Anbietern mit Zwei-Faktor-Anmeldung — etwa Google — muss ein eigenes
Anwendungskennwort erzeugt werden; das gewöhnliche Kontokennwort wird
abgewiesen.

## Verteilung

| Feld | Bedeutung |
| --- | --- |
| **Betreffkennzeichen** | Wird jedem verteilten Betreff vorangestellt, etwa `[Vorstand]`. Ein bereits vorhandenes Kennzeichen wird **nicht** ein zweites Mal angehängt — sonst wüchse der Betreff mit jeder Antwort. |
| **Antworten gehen an** | „an die Liste" macht aus dem Verteiler einen Gesprächskreis, „an den Absender" ein Rundschreiben mit privaten Rückfragen. |
| **Anhänge weitergeben** | Ohne diese Einstellung wird nur der Text verteilt. |
| **Fußzeile** | Wird unter jede verteilte Nachricht gesetzt, abgetrennt durch die übliche Signaturmarke. |

### Der Abmeldehinweis in der Fußzeile

Unter jede verteilte Nachricht kommt **selbsttätig** eine Zeile, die den
Abmeldeweg nennt:

```
Abmelden: eine E-Mail an vorstand@example.org mit dem Betreff "Abmeldung".
```

Das ist kein Zierrat. Die Kopfzeile `List-Unsubscribe`, die jede Nachricht
ebenfalls trägt, zeigt Thunderbird nur unter bestimmten Bedingungen an und die
Mailprogramme der Mobiltelefone meist überhaupt nicht. Wer sich abmelden will,
fände den Weg sonst nirgends — und drückt im Zweifel den Spam-Knopf, was der
Zustellbarkeit der ganzen Liste weit mehr schadet als eine Zeile unter jeder
Nachricht.

Doppelt gesagt wird nichts: Verwendet die eigene Fußzeile einen der
Platzhalter `##abmeldekennung##` oder `##adresse##`, gilt sie als
selbsterklärend und die Zeile entfällt. Wer den Wortlaut selbst bestimmen
will, schreibt ihn also einfach in die Fußzeile.

Ohne eingetragene Abmeldekennung entfällt der Hinweis ebenfalls — dann gibt es
schließlich keinen Weg, den man nennen könnte.

### Platzhalter in den Texten

In Fußzeile, Ablehnungstext und Bestätigungstext stehen zur Verfügung:

| Platzhalter | Inhalt |
| --- | --- |
| `##liste##` | Name der Liste |
| `##adresse##` | Adresse der Liste |
| `##kennung##` | Kennwort für die Aufnahme |
| `##abmeldekennung##` | Kennwort für die Abmeldung |
| `##absender##` | Adresse des ursprünglichen Absenders |
| `##absendername##` | Angezeigter Name des Absenders |
| `##betreff##` | Betreff der eingegangenen Nachricht |

Beispiel für eine Fußzeile:

```
Diese Nachricht ging an alle Teilnehmer von ##liste##.
Zum Austragen eine E-Mail an ##adresse## mit dem Betreff "##abmeldekennung##".
```

## Aufnahme, Abmeldung und Ablehnung

| Feld | Bedeutung |
| --- | --- |
| **Kennwort für die Aufnahme** | Steht dieses Wort am **Anfang** des Betreffs, gilt die Nachricht eines Fremden als Aufnahmeantrag. Leer lassen schaltet die Funktion ab. |
| **Kennwort für die Abmeldung** | Ebenso für das Austragen. |
| **Benachrichtigung an** | Diese Adressen erfahren von einem neuen Antrag. Mehrere durch Komma trennen. Ohne Eintrag bleibt ein Antrag unbemerkt im Backend liegen. |
| **Text der Antragsbestätigung** | Geht an den Antragsteller. Leer lassen benutzt einen Standardtext. |
| **Absender über die Ablehnung unterrichten** | Bei einer Adresse, die viel Spam bekommt, besser abschalten — siehe [Betrieb](betrieb.md). |
| **Text der Ablehnung** | Leer lassen benutzt einen Standardtext. |

Zur genauen Erkennung der Kennwörter siehe [Wie die Verteilung entscheidet](verteilung.md).

## Regelmäßiger Lauf

| Feld | Bedeutung |
| --- | --- |
| **Prüfintervall in Minuten** | Wie oft dieses Postfach abgefragt wird. 0 heißt: bei jedem Cron-Durchgang. |
| **Nachrichten je Durchgang** | Obergrenze für einen Lauf. Was übrig bleibt, kommt beim nächsten Mal an die Reihe. 25 ist ein vernünftiger Wert; bei Listen mit vielen Anhängen eher weniger. |

## Veröffentlichung

Nur aktive Listen werden vom Cronjob abgefragt. Das Postfach einer
abgeschalteten Liste bleibt vollständig unangetastet — die dort liegenden
Nachrichten werden nach dem Wiedereinschalten der Reihe nach abgearbeitet.

## Teilnehmer

Über die Operation **Teilnehmer** in der Übersicht.

| Feld | Bedeutung |
| --- | --- |
| **E-Mail-Adresse** | Wird immer kleingeschrieben gespeichert und darf je Liste nur einmal vorkommen. |
| **Vorname, Nachname** | Erscheinen im Empfängernamen der verteilten Nachrichten — und, wenn dieser Teilnehmer selbst an die Liste schreibt, als sein angezeigter Name („Max Mustermann via Vorstand"). Der Datensatz hat dabei Vorrang vor dem Namen aus seinem Mailprogramm, der oft fehlt oder nur die Adresse wiederholt. Es lohnt sich also, ihn zu pflegen. |
| **Status** | `aktiv` nimmt am Verkehr teil. `beantragt` wartet auf Freigabe und bekommt nichts. `gesperrt` ist ausgeschlossen und bleibt es auch bei einem erneuten Antrag. |
| **Darf an die Liste schreiben** | Ohne dieses Recht liest der Teilnehmer nur mit. |
| **Erhält die Nachrichten der Liste** | Ohne dieses Recht darf er einreichen, bekommt aber nichts. |

Ein Aufnahmeantrag wird freigegeben, indem der Status von `beantragt` auf
`aktiv` gesetzt wird. Wer den Antrag ablehnen will, löscht den Eintrag — oder
setzt ihn auf `gesperrt`, wenn derselbe Absender es sonst gleich wieder
versucht.

## Anmeldung über die Webseite

Neben dem Weg über den Betreff gibt es ein Frontend-Modul. Es wird im Backend
unter **Layout → Module** angelegt, Typ **Anmeldung zur Mailingliste**.

| Feld | Bedeutung |
| --- | --- |
| **Mailingliste** | Die Liste, für die das Formular gilt. Für mehrere Listen legt man mehrere Module an. Abgeschaltete Listen lassen sich auswählen; das Formular erscheint dann erst nach ihrer Veröffentlichung. |
| **Hinweis zum Datenschutz** | Erscheint als Ankreuzfeld unter dem Formular und muss dann bestätigt werden. HTML ist erlaubt, etwa für einen Verweis auf die Datenschutzerklärung. Bleibt das Feld leer, entfällt das Ankreuzfeld. |

### Der Ablauf

```
Formular ausgefüllt   →  Status „Bestätigung ausstehend“
Bestätigungslink      →  Status „Aufnahme beantragt“   + Meldung an die Betreuung
Freigabe im Backend   →  Status „aktiv“
```

Der Bestätigungslink ist **kein Formalismus**. In ein Formular auf einer
öffentlichen Seite kann jeder eine fremde Adresse eintragen; erst der Klick
beweist, dass der Eintragende Zugriff auf das Postfach hat. Bis dahin erfährt
die Betreuung nichts von dem Eintrag — sonst ließe sich ihr Postfach mit
erfundenen Anmeldungen zuschütten. Der Link gilt zwei Tage; danach verfällt
der Eintrag.

Schreibt jemand mit einem offenen, unbestätigten Eintrag später selbst an die
Listenadresse, gilt die Adresse damit als belegt: Der Eintrag rückt ohne
weiteres Zutun zum Antrag auf.

### Was der Besucher zu sehen bekommt

Die Meldung nach dem Absenden ist **immer dieselbe** — gleich ob die Adresse
neu ist, schon auf der Liste steht oder gesperrt wurde. Andernfalls ließe sich
über das Formular abfragen, wer Teilnehmer der Liste ist; bei einem Verteiler
zu einem Thema wie Gesundheit oder Inklusion wäre das eine Auskunft, die
niemanden etwas angeht.

Was wirklich der Fall ist, erfährt allein der Inhaber des Postfachs:

| Lage der Adresse | Was per Mail geschieht |
| --- | --- |
| neu oder unbestätigt | Bestätigungslink |
| bereits aktiv | Hinweis, dass sie schon Teilnehmer ist, samt Abmeldeweg |
| Antrag läuft | Hinweis, dass der Antrag auf Freigabe wartet |
| gesperrt | nichts |

### Spam-Schutz

Das Formular enthält ein für Menschen unsichtbares Feld. Wird es ausgefüllt,
war ein Formularroboter am Werk; die Eingabe wird verworfen, ohne dass die
Gegenseite den Grund erfährt. Zusätzlich prüft Contao das Anfrage-Merkmal
(`REQUEST_TOKEN`) jeder Formularsendung.

### Abmeldung

Dafür gibt es kein Modul, und zwar mit Absicht: Es gibt bereits zwei Wege, die
ohne weitere Pflege auskommen — das Kennwort im Betreff und die Kopfzeile
`List-Unsubscribe`, die jedes gängige Mailprogramm als Abmeldeknopf anzeigt.

## Was ein Teilnehmer zu sehen bekommt

Der Aufnahmeweg meldet sich an jeder Stelle, damit niemand im Ungewissen
bleibt:

| Zeitpunkt | Nachricht an den Teilnehmer |
| --- | --- |
| Antrag per E-Mail eingegangen | Eingangsbestätigung („Ihr Antrag ist eingegangen") |
| Antrag über die Webseite | Bestätigungslink, danach Eingangsbestätigung |
| **Freigabe im Backend** | **Willkommensnachricht** — er ist jetzt Teilnehmer, mit Hinweis, wohin er schreiben kann und wie er sich abmeldet |
| Abmeldung | Bestätigung der Abmeldung |

Die Willkommensnachricht geht heraus, sobald der Status auf **aktiv**
wechselt — gleich ob über die Einzelbearbeitung oder die Mehrfachbearbeitung.
Ein erneutes Speichern eines bereits aktiven Teilnehmers löst nichts aus; eine
Namenskorrektur führt also nicht zu einer zweiten Begrüßung.
