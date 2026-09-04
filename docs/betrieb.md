# Betrieb und Fehlersuche

## Der Verlauf

Über die Operation **Verlauf** in der Übersicht. Jede eingegangene Nachricht
hinterlässt dort eine Zeile mit Zeitpunkt, Ergebnis, Absender, Betreff und —
wo es etwas zu erklären gibt — der Begründung.

| Ergebnis | Bedeutung |
| --- | --- |
| **verteilt** | Weitergegeben. Dahinter steht, wie viele Empfänger erreicht wurden; schlugen einzelne Zustellungen fehl, steht auch das dort. |
| **abgelehnt** | Abgewiesen, mit dem Grund. |
| **Aufnahmeantrag** | Antrag vorgemerkt oder — bei einer bekannten Adresse — unverändert gelassen. |
| **Abmeldung** | Teilnehmer ausgetragen. |
| **verworfen** | Maschinelle Antwort oder eigene Nachricht. |
| **Fehler** | Die Verarbeitung scheiterte; die Meldung steht dabei. |

Einträge lassen sich löschen, aber nicht ändern — ein nachträglich veränderbares
Protokoll wäre keines.

**Vorsicht beim Aufräumen:** Der Verlauf hat neben der Nachvollziehbarkeit eine
technische Aufgabe. Die gespeicherte Message-ID verhindert, dass eine Nachricht
ein zweites Mal verteilt wird. Wer aufräumt, sollte deshalb nur alte Einträge
löschen, nicht die der letzten Tage.

## Der Cronjob läuft nicht

Das häufigste Problem. In Contaos Voreinstellung wird der Cron über
Seitenaufrufe von Besuchern angestoßen. Auf einer wenig besuchten Seite liegen
Nachrichten dann stundenlang.

Abhilfe: ein echter Cronjob auf dem Server.

```bash
* * * * * /usr/bin/php /pfad/zum/projekt/vendor/bin/contao-console contao:cron
```

Ob überhaupt etwas geschieht, verrät die Übersicht der Mailinglisten: Hinter
jedem Namen steht, wann zuletzt geprüft wurde. Steht dort „nie", ist entweder
die Liste nicht aktiv oder der Cron läuft nicht.

## Das Postfach ist nicht erreichbar

```bash
vendor/bin/contao-console contao:mailingliste:abrufen --pruefen
```

Der Befehl verbindet sich nur und zählt die ungelesenen Nachrichten. Es wird
nichts verteilt, nichts markiert und nichts gelöscht; er lässt sich also
beliebig oft wiederholen.

Häufige Ursachen:

* **Falscher Port zur Verschlüsselung.** 993 gehört zu SSL, 143 zu STARTTLS.
  Vertauscht führt das zu einer Zeitüberschreitung statt einer klaren Meldung.
* **Anwendungskennwort nötig.** Anbieter mit Zwei-Faktor-Anmeldung weisen das
  gewöhnliche Kontokennwort ab.
* **Anmeldung von außen gesperrt.** Manche Anbieter schalten den IMAP-Zugriff
  erst nach ausdrücklicher Freigabe im Kundenkonto frei.
* **Ordnername falsch.** Unterordner heißen je nach Server `INBOX.Liste`,
  `INBOX/Liste` oder schlicht `Liste`.

## Nachrichten landen im Spam

Das Bundle setzt die Kopfzeilen so, dass es möglichst nicht dazu kommt: Absender
ist immer die Listenadresse, dazu kommen `List-Id`, `List-Post`, `Sender` und
`Precedence`. Zwei Dinge muss der Betreiber aber selbst leisten:

1. **Eigenen SMTP-Zugang eintragen.** Bleibt das Feld leer, versendet die Liste
   über den allgemeinen Contao-Mailer — und dessen Server ist für die
   Listenadresse in aller Regel nicht zuständig. SPF schlägt dann fehl.
2. **SPF und DKIM für die Domäne einrichten.** Das geschieht beim Anbieter der
   Domäne, nicht in Contao.

## Ablehnungen und Spam

Jede Ablehnung geht an die Adresse, die im Absender steht. Bei Spam ist diese
Adresse fast immer gefälscht — die Ablehnung belästigt also einen
Unbeteiligten, und das schadet auf Dauer dem Ruf des eigenen Mailservers.

Bekommt eine Listenadresse viel Spam, gehört die Einstellung **Absender über
die Ablehnung unterrichten** deshalb ausgeschaltet. Der Verlauf zeigt weiterhin
jede abgelehnte Nachricht; nur der Absender erfährt nichts mehr.

## Kennwörter und das Anwendungsgeheimnis

Die Postfach-Kennwörter liegen mit libsodium verschlüsselt in der Datenbank.
Der Schlüssel wird aus dem Anwendungsgeheimnis der Installation abgeleitet
(`APP_SECRET`, in Contao unter **Einstellungen → Anwendungsgeheimnis**).

Daraus folgt zweierlei:

* Ein Datenbankabzug allein genügt **nicht**, um an die Postfächer zu kommen.
* Wird das Anwendungsgeheimnis ausgetauscht, sind alle gespeicherten Kennwörter
  unlesbar und müssen neu eingetragen werden. Das Bundle bricht deswegen nicht
  ab: Der Verbindungsversuch scheitert, der Fehler landet im Verlauf, und die
  übrigen Listen werden ganz normal weiterbearbeitet.

Im Backend erscheint statt des Kennworts eine Reihe Sternchen. Bleiben sie
stehen, ändert sich nichts; ein geleertes Feld löscht das Kennwort. Das echte
Kennwort verlässt den Server nie — auch nicht im Quelltext des Formulars.

Wird beim Öffnen der Liste gemeldet, dass die Erweiterung `sodium` fehlt, muss
sie in PHP nachinstalliert werden. Ohne sie lassen sich keine Kennwörter
speichern.

## Eine Liste vorübergehend anhalten

Die Liste deaktivieren. Der Cronjob überspringt sie dann vollständig; das
Postfach bleibt unangetastet. Nach dem Wiedereinschalten werden die
zwischenzeitlich eingegangenen Nachrichten der Reihe nach abgearbeitet — je
Durchgang so viele, wie unter **Nachrichten je Durchgang** eingestellt sind.

Wer die aufgelaufenen Nachrichten **nicht** nachträglich verteilen will, muss
sie vor dem Wiedereinschalten im Postfach als gelesen markieren.

## Ein Durchgang dauert zu lange

Der Abruf holt die Anhänge vollständig in den Speicher. Bei Listen mit großen
Anhängen ist das der teuerste Teil.

Gegenmittel, in dieser Reihenfolge:

1. **Nachrichten je Durchgang** herabsetzen, etwa auf 5.
2. **Prüfintervall** herabsetzen, damit die kleineren Portionen häufiger kommen.
3. **Anhänge weitergeben** ausschalten, wenn die Dateien ohnehin nicht gebraucht
   werden.
