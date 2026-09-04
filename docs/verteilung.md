# Wie die Verteilung entscheidet

Für jede eingegangene Nachricht läuft dieselbe Kette von Prüfungen. Ihre
Reihenfolge ist nicht beliebig; jeder Schritt steht dort, wo er steht, aus
einem Grund.

## Die Reihenfolge

### 1. Eigene Nachrichten

Jede Nachricht, die das Bundle versendet, trägt die Kopfzeile
`X-Contao-Mailingliste` mit der ID der Liste. Kommt eine Nachricht mit dieser
Kopfzeile zurück ins Postfach, hat die Liste sie selbst verschickt und sie wird
verworfen.

Ohne diese Prüfung entsteht eine Schleife, sobald die Listenadresse selbst als
Teilnehmer eingetragen ist — und das passiert bei „Antwort an alle" ständig.
Jeder Durchgang würde die Zahl der Nachrichten vervielfachen.

### 2. Maschinelle Antworten

Verworfen wird, was eines dieser Merkmale trägt:

* leerer Absender (so kennzeichnen Mailserver ihre Unzustellbarkeitsberichte)
* `Auto-Submitted` mit einem anderen Wert als `no`
* `X-Autoreply` oder `X-Autorespond`
* `Precedence: bulk`, `auto_reply` oder `junk`

Eine einzige Abwesenheitsnotiz an eine Liste mit fünfzig Teilnehmern erzeugt
sonst fünfzig weitere Automatenantworten — und die antworten ihrerseits.

Umgekehrt versendet das Bundle seine eigenen Ablehnungen und Bestätigungen mit
`Auto-Submitted: auto-replied`, damit fremde Automaten sie nicht beantworten.

### 3. Bereits behandelt

Zu jeder verarbeiteten Nachricht steht die Message-ID im Verlauf. Taucht
dieselbe ID ein zweites Mal auf, wird sie verworfen.

Das greift, wenn ein Cron-Lauf zwischen Versand und dem Setzen des Lesezeichens
abbricht — ohne diese Prüfung bekäme jeder Teilnehmer die Nachricht ein zweites
Mal.

Nachrichten ganz ohne Message-ID gelten nie als bekannt. Sie einfach
durchzulassen ist harmloser, als sie alle für dieselbe zu halten und ab der
zweiten zu verwerfen.

### 4. Abmeldung

Beginnt der Betreff mit dem Abmelde-Kennwort **und** ist der Absender in der
Liste eingetragen, wird er ausgetragen und bekommt eine Bestätigung.

Der Eintrag wird **gelöscht**, nicht gesperrt: Wer sich abmeldet, soll sich
später ohne Hürde wieder anmelden können. Eine gesperrte Adresse bleibt
allerdings gesperrt — sonst wäre die Abmeldung ein bequemer Weg, eine Sperre
loszuwerden.

Dieser Schritt steht **vor** der Verteilung, weil er auch für aktive
Teilnehmer greifen muss.

### 5. Verteilung

Ist der Absender ein aktiver Teilnehmer mit Senderecht, geht die Nachricht an
alle Teilnehmer, die aktiv sind und den Empfang nicht abgeschaltet haben.

Jeder Empfänger bekommt eine **eigene** Ausfertigung, kein gemeinsames BCC.
Bei einem BCC trüge jede Nachricht dieselbe Message-ID; Mailprogramme, die
danach aussortieren, zeigten sie nur einmal an.

Der Verfasser selbst bekommt seine Nachricht mit — so sieht er, dass sie
angekommen ist, und hat den Verlauf vollständig im eigenen Postfach.

#### Absender und Antwortadresse

Die Absenderadresse ist **immer** die Adresse der Liste, nie die des
Verfassers. Der Verfasser steht im angezeigten Namen:

```
From:     "Max Mustermann via Vorstand" <vorstand@example.org>
Sender:   "Vorstand" <vorstand@example.org>
Reply-To: "Vorstand" <vorstand@example.org>
```

Das ist keine Bequemlichkeit, sondern Notwendigkeit. Wäre der Verfasser der
Absender, würde jede Weiterleitung die SPF-Prüfung des Empfängers reißen — und
bei einer DMARC-Richtlinie auf „reject" ginge die Nachricht schlicht verloren.

Steht **Antworten gehen an** auf „an den Absender", trägt `Reply-To` statt der
Liste den ursprünglichen Verfasser.

#### Weitere Kopfzeilen

Nach RFC 2919 und RFC 2369 bekommt jede verteilte Nachricht:

```
List-Id:   Vorstand <liste-3.example.org>
List-Post: <mailto:vorstand@example.org>
Precedence: list
```

Viele Mailprogramme bauen darauf ihre Filterregeln auf.

### 6. Aufnahmeantrag

Beginnt der Betreff eines Fremden mit dem Aufnahme-Kennwort, wird er mit dem
Status `beantragt` eingetragen. Er bekommt eine Bestätigung, die
Benachrichtigungsadressen bekommen eine Mitteilung.

Freigegeben wird im Backend, indem der Status auf `aktiv` gesetzt wird.

Eine bereits **gesperrte** Adresse wird nicht wieder auf `beantragt` gesetzt —
sonst ließe sich eine Sperre durch beharrliches Beantragen aushebeln. Der
Antragsteller bekommt trotzdem dieselbe Bestätigung wie alle anderen, damit die
Sperre nicht am Ausbleiben einer Antwort erkennbar wird.

Dieser Schritt steht **hinter** der Verteilung: Ein Teilnehmer, der
versehentlich mit dem Aufnahmewort schreibt, soll seine Nachricht verteilt
bekommen, statt einen zweiten Antrag zu stellen.

### 7. Ablehnung

Alles Übrige wird abgewiesen. Im Verlauf steht der Grund:

* Absender gehört nicht zur Liste
* Aufnahmeantrag ist noch nicht freigegeben
* Absender ist gesperrt
* Absender hat kein Senderecht

Ob der Absender davon erfährt, entscheidet die Einstellung der Liste.

## Wie die Kennwörter erkannt werden

Das Kennwort muss **am Anfang** des Betreffs stehen. Ein bloßes Vorkommen
irgendwo im Text würde harmlose Nachrichten umdeuten: Bei dem Kennwort
„Anmeldung" wäre der Betreff „Anmeldung zum Vereinsturnier läuft" plötzlich ein
Aufnahmeantrag, und der eigentliche Inhalt käme bei niemandem an.

Vorher abgetragen werden führende Antwort- und Weiterleitungskürzel sowie
Klammerpräfixe — auch mehrfach verschachtelte. Wer eine Ablehnung bekommt,
antwortet erfahrungsgemäß darauf, und dann steht dort „Re: Anmeldung".

Hinter dem Kennwort darf kein Buchstabe und keine Ziffer folgen. Sonst träfe
das Kennwort „Abo" auch auf „Abonnentenliste" zu.

| Betreff | Kennwort „Anmeldung" |
| --- | --- |
| `Anmeldung` | trifft zu |
| `anmeldung` | trifft zu |
| `Anmeldung zur Vorstandsliste` | trifft zu |
| `Anmeldung: bitte aufnehmen` | trifft zu |
| `Re: AW: [Vorstand] Anmeldung` | trifft zu |
| `Die Anmeldung zum Turnier läuft` | trifft **nicht** zu |
| `Anmeldungen sind eingegangen` | trifft **nicht** zu |

Ein leeres Kennwortfeld schaltet die jeweilige Funktion ab.

## Was mit der Nachricht im Postfach geschieht

Nach der Verarbeitung wird die Nachricht so behandelt, wie es in der Liste
eingestellt ist: als gelesen markiert, verschoben oder gelöscht.

Eine Ausnahme gibt es: Scheitert die Verarbeitung mit einem Fehler, bleibt die
Nachricht **ungelesen** liegen. Der Fehler steht im Verlauf, und weil dort auch
die Message-ID vermerkt ist, rennt der nächste Lauf nicht in dieselbe Falle,
sondern erkennt die Nachricht als bereits behandelt.
