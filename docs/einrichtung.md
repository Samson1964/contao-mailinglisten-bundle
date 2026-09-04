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
| **Fußzeile** | Wird unter jede verteilte Nachricht gesetzt, abgetrennt durch die übliche Signaturmarke. Gut geeignet für den Hinweis, wie man sich abmeldet. |

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
| **Vorname, Nachname** | Erscheinen im Empfängernamen der verteilten Nachrichten. |
| **Status** | `aktiv` nimmt am Verkehr teil. `beantragt` wartet auf Freigabe und bekommt nichts. `gesperrt` ist ausgeschlossen und bleibt es auch bei einem erneuten Antrag. |
| **Darf an die Liste schreiben** | Ohne dieses Recht liest der Teilnehmer nur mit. |
| **Erhält die Nachrichten der Liste** | Ohne dieses Recht darf er einreichen, bekommt aber nichts. |

Ein Aufnahmeantrag wird freigegeben, indem der Status von `beantragt` auf
`aktiv` gesetzt wird. Wer den Antrag ablehnen will, löscht den Eintrag — oder
setzt ihn auf `gesperrt`, wenn derselbe Absender es sonst gleich wieder
versucht.
