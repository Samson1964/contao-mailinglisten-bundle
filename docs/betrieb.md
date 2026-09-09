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

## Das System-Log

Der Verlauf steht nur im Mailinglisten-Modul und zeigt nur diese eine Liste.
Wer als Administrator dem Weg einer Nachricht durch die ganze Installation
folgt, schaut ins **System-Log** von Contao. Dort steht seit Fassung 1.2.1:

| Aktion | Wann |
| --- | --- |
| **E-Mail** | Jede verteilte Nachricht, mit Liste, Absender und Empfängerzahl. |
| **Fehler** | Gescheiterte Zustellungen, unlesbare Nachrichten, abgewiesene Postfachverbindungen, Fehler bei An- und Abmeldung. |
| **Cron** | Die Zusammenfassung eines Durchgangs — aber nur, wenn wirklich etwas geschah. Sonst schriebe der Minutentakt das Log zu. |

Nicht im System-Log stehen Ablehnungen, Aufnahmeanträge und Abmeldungen. Sie
gehören zum Betrieb der einzelnen Liste und stehen vollständig im Verlauf; im
System-Log wären sie Rauschen.

**Bleibt das System-Log trotzdem leer**, fehlt in der Installation der Handler,
der es füllt. Er steht in `config/config_prod.yaml` und stammt aus der Vorlage
des Contao Managers:

```yaml
monolog:
    handlers:
        contao:
            type: service
            id: contao.monolog.handler
```

Wurde die Datei einmal von Hand überarbeitet, kann der Block herausgefallen
sein. Dann schreibt kein einziges Contao-Bundle mehr ins System-Log, nicht nur
dieses.

Wer die Einträge lieber nur in `var/logs/` hätte, überschreibt in der eigenen
`config/services.yaml` die drei Dienste
`schachbulle_mailinglisten.logger.email`, `…logger.fehler` und `…logger.cron`
mit den nackten Monolog-Kanälen (`@monolog.logger.contao.error` bzw.
`@monolog.logger.contao.cron`). Ohne Contaos `SystemLogger` fehlt der
`ContaoContext`, und der `ContaoTableHandler` verwirft den Datensatz — was
genau der Zustand bis Fassung 1.2.0 war.

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

## Der Versand scheitert mit „Connection refused"

Steht im Verlauf oder im System-Log eine Zeile wie

```
Versand an "…" fehlgeschlagen: Connection could not be established with
host "ssl://sslout.de:465": Connection refused
```

dann liegt es nicht am Bundle und meist auch nicht am Mailserver, sondern an
einer **Sperre ausgehender SMTP-Verbindungen auf dem Webserver**. Viele
Anbieter sperren die Ports **25 und 465**, damit eine gekaperte Webanwendung
keinen Spam versenden kann. Der Submission-Port **587** bleibt dabei in aller
Regel offen, weil er eine Anmeldung erzwingt.

Der Nachweis dauert eine Minute. Auf dem Webserver:

```bash
php -r 'foreach ([["sslout.de",465],["sslout.de",587],["sslout.de",25],["smtp.gmail.com",465],["sslin.de",993]] as [$h,$p]) { $t=microtime(true); $f=@stream_socket_client("tcp://$h:$p",$e,$s,8); printf("%-16s %-5d %-6s %5d ms %s\n",$h,$p,$f?"OFFEN":"ZU",round((microtime(true)-$t)*1000),$f?"":"($e $s)"); if($f) fclose($f); }'
```

Den eigenen SMTP-Server dabei gegen einen fremden halten — erst das trennt
eine Portsperre von einer Störung beim Anbieter:

| Ergebnis | Bedeutung |
| --- | --- |
| 25 und 465 zu, **bei beiden Zielen**, 587 offen | Portsperre auf dem Webserver |
| Nur der eigene Server zu, der fremde offen | Störung oder Sperre beim Anbieter |
| Antwort nach 0–1 ms | lokale Firewallregel mit `REJECT` |
| Antwort erst nach Sekunden | Sperre weiter außen im Netz |

**Die Abhilfe ist eine Einstellung, kein Eingriff:** In der Liste unter
*Versand* den Port auf **587** und die Verschlüsselung auf **TLS (STARTTLS)**
setzen. Bei den meisten Anbietern — Domainfactory eingeschlossen — ist das
derselbe Server unter einem anderen Port; SPF und DKIM verhalten sich
unverändert, weil dieselbe Maschine ausliefert.

Ob dort wirklich ein passender Dienst antwortet, zeigt ein EHLO. Gesucht sind
die Zeilen `STARTTLS` und `AUTH`:

```bash
php -r '$f=stream_socket_client("tcp://sslout.de:587",$e,$s,10); echo fgets($f); fwrite($f,"EHLO test\r\n"); while(($z=fgets($f))!==false){ echo $z; if(preg_match("/^250 /",$z)) break; } fwrite($f,"QUIT\r\n");'
```

**Nicht auf einen lokalen Postfix ausweichen.** Der Webserver steht in aller
Regel nicht im SPF der Absenderdomäne und besitzt keinen DKIM-Schlüssel dafür.
Direkt versandte Nachrichten scheitern dann an beidem — und bei einer
DMARC-Richtlinie oberhalb von `p=none` landen sie im Spam-Ordner oder werden
abgewiesen. Der Weg über den Relay des Domänen-Anbieters ist die einzige
Konfiguration, die beide Prüfungen besteht.

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

### Wenn es trotzdem passiert: DMARC-Berichte auswerten

Landen Nachrichten weiterhin im Spam, obwohl SPF und DKIM bestehen, hilft nur
die Absenderreputation — und die beginnt mit der Frage, wer alles im Namen der
Domäne verschickt. Ein DMARC-Eintrag mit Berichtsadresse liefert die Antwort:

```
_dmarc.<domäne>   TXT   v=DMARC1; p=none; rua=mailto:dmarc@<domäne>; fo=1
```

Nach einigen Tagen treffen Aggregatberichte der großen Anbieter ein. Die beiden
Werkzeuge im Bundle werten sie aus, ohne dass man die Anhänge von Hand
entpacken müsste — sie lesen `.eml` unmittelbar und kommen mit gzip wie zip
zurecht:

```bash
php tools/dmarc-auswerten.php /pfad/zum/ordner
```

Das zeigt jede Quell-IP mit Umkehrname, Anzahl und SPF/DKIM-Ergebnis.

```bash
php tools/dmarc-fehler.php /pfad/zum/ordner
```

Das zeigt nur die Nachrichten, die DMARC **nicht** bestehen — also solche, bei
denen weder SPF noch DKIM ausgerichtet ist. Nur diese wären von einer
Anhebung auf `p=quarantine` betroffen.

**Eine Fehlerquote immer nach Meldern aufschlüsseln, bevor man sie dem eigenen
Versand anlastet.** Weiterleitungen brechen DKIM prinzipbedingt; ein Anbieter,
bei dem viele weitergeleitete Nachrichten ankommen, meldet deshalb reihenweise
Fehlschläge, während alle anderen dieselbe Signatur einwandfrei bestätigen.
Erst wenn die Fehler über die Melder streuen, liegt es am eigenen Versand.

Ist kein unbekannter Versandweg mehr in den Berichten, kann die Richtlinie von
`p=none` auf `p=quarantine` steigen — zunächst mit `pct=25`, damit ein
übersehener Weg auffällt, bevor er flächendeckend schadet. Eine durchgesetzte
Richtlinie zählt bei allen großen Anbietern positiv.

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
