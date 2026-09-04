<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Liest Postfächer über die Bibliothek webklex/php-imap.
 *
 * Die Bibliothek spricht IMAP in reinem PHP und braucht deshalb **nicht** die
 * Erweiterung `ext-imap`, die seit PHP 8.4 nicht mehr zum Kern gehört und auf
 * vielen Hostings fehlt. Das ist der Grund für ihren Einsatz, obwohl sie mit
 * `nesbot/carbon` und `illuminate/pagination` zwei Pakete aus dem
 * Laravel-Umfeld mitbringt.
 *
 * Die gesamte Berührung mit der Bibliothek liegt in dieser Klasse. Wer sie
 * ersetzen will, schreibt eine zweite Umsetzung von PostfachLeserInterface und
 * tauscht den Dienst in der services.yaml — der Verteiler merkt nichts davon.
 */
class WebklexPostfachLeser implements PostfachLeserInterface
{
    /**
     * Verbindet sich mit dem Postfach und arbeitet die ungelesenen Nachrichten ab.
     *
     * Die Nachrichten werden ausdrücklich **ungelesen** geholt
     * (`leaveUnread()`), damit erst der Rückgabewert des Rückrufs über das
     * Lesezeichen entscheidet. Bricht die Verarbeitung mittendrin ab, bleibt
     * die betroffene Nachricht ungelesen liegen und wird beim nächsten
     * Cron-Lauf erneut angefasst.
     *
     * Die Verbindung wird in jedem Fall wieder getrennt, auch wenn der Rückruf
     * eine Ausnahme wirft — sonst bliebe bei einem Fehler eine IMAP-Sitzung
     * offen, und viele Anbieter begrenzen die Zahl gleichzeitiger Sitzungen
     * scharf.
     *
     * @param Postfachzugang $zugang      Zugangsdaten des Postfachs
     * @param int            $hoechstzahl Obergrenze für diesen Durchgang; Werte
     *                                    unter 1 werden auf 1 angehoben
     * @param callable       $verarbeiter Rückruf, der je Nachricht eine
     *                                    Nachbehandlung zurückgibt
     *
     * @return int Anzahl der verarbeiteten Nachrichten
     *
     * @throws PostfachFehler Bei Verbindungs-, Anmelde- oder Ordnerfehlern
     */
    public function verarbeiten(Postfachzugang $zugang, int $hoechstzahl, callable $verarbeiter): int
    {
        if (!$zugang->istVollstaendig()) {
            throw new PostfachFehler('Die Zugangsdaten des Postfachs sind unvollständig.');
        }

        $hoechstzahl = max(1, $hoechstzahl);
        $client = null;
        $anzahl = 0;

        try {
            $client = (new ClientManager())->make([
                'host' => $zugang->host,
                'port' => $zugang->port,
                'protocol' => 'imap',
                'encryption' => \in_array($zugang->verschluesselung, ['ssl', 'tls'], true) ? $zugang->verschluesselung : false,
                'validate_cert' => $zugang->zertifikatPruefen,
                'username' => $zugang->benutzer,
                'password' => $zugang->kennwort,
                'authentication' => null,
                'timeout' => 30,
            ]);

            $client->connect();

            $ordner = $client->getFolderByPath('' !== $zugang->ordner ? $zugang->ordner : 'INBOX');

            if (null === $ordner) {
                throw new PostfachFehler(sprintf('Der IMAP-Ordner "%s" wurde nicht gefunden.', $zugang->ordner));
            }

            $nachrichten = $ordner->query()
                ->unseen()
                ->leaveUnread()
                ->limit($hoechstzahl)
                ->get()
            ;

            foreach ($nachrichten as $nachricht) {
                $ergebnis = $verarbeiter($this->umwandeln($nachricht));
                $this->nachbehandeln($nachricht, $ergebnis instanceof Nachbehandlung ? $ergebnis : Nachbehandlung::Gelesen, $zugang);
                ++$anzahl;
            }
        } catch (PostfachFehler $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new PostfachFehler(sprintf('Das Postfach "%s" auf "%s" konnte nicht gelesen werden: %s', $zugang->benutzer, $zugang->host, $e->getMessage()), 0, $e);
        } finally {
            $client?->disconnect();
        }

        return $anzahl;
    }

    /**
     * Übersetzt eine Nachricht der Bibliothek in das eigene Wertobjekt.
     *
     * Die Bibliothek liefert die meisten Felder als `Attribute`-Objekte, die
     * sich erst über `toString()` zu einer Zeichenkette machen lassen; bei
     * Adressfeldern steckt hinter `first()` ein Address-Objekt mit den
     * Eigenschaften `mail` und `personal`. Beides wird hier eingeebnet, damit
     * der Rest des Bundles nur noch mit Zeichenketten arbeitet.
     *
     * Der Rohinhalt der Anhänge wird vollständig in den Speicher gelesen. Bei
     * sehr großen Anhängen ist das der teuerste Teil des Durchgangs — die
     * Höchstzahl je Cron-Lauf begrenzt den Schaden.
     *
     * @param Message $nachricht Die Nachricht aus der Bibliothek
     *
     * @return EingehendeNachricht Das eingeebnete Wertobjekt
     */
    private function umwandeln(Message $nachricht): EingehendeNachricht
    {
        $absender = $nachricht->getFrom()->first();

        $kopfzeilen = [];
        $kopf = $nachricht->getHeader();

        if (null !== $kopf) {
            foreach ($kopf->getAttributes() as $name => $wert) {
                // Die Bibliothek ersetzt Bindestriche durch Unterstriche
                // ('reply-to' wird zu 'reply_to'). Für die Kopfzeilen des
                // Wertobjekts wird das zurückgedreht, damit dort die
                // Schreibweise aus der E-Mail selbst steht.
                $kopfzeilen[str_replace('_', '-', strtolower($name))] = $this->alsText($wert);
            }
        }

        $anhaenge = [];

        foreach ($nachricht->getAttachments() as $anhang) {
            /* @var Attachment $anhang */
            $inhalt = $anhang->getContent();

            if (null === $inhalt || '' === $inhalt) {
                continue;
            }

            $anhaenge[] = [
                'name' => (string) ($anhang->getName() ?: 'anhang.dat'),
                'inhalt' => $inhalt,
                'mimetyp' => (string) ($anhang->getMimeType() ?: 'application/octet-stream'),
            ];
        }

        return new EingehendeNachricht(
            messageId: trim($this->alsText($nachricht->getMessageId()), '<> '),
            absender: strtolower(trim((string) ($absender?->mail ?? ''))),
            absenderName: trim((string) ($absender?->personal ?? '')),
            betreff: $this->alsText($nachricht->getSubject()),
            text: (string) $nachricht->getTextBody(),
            html: (string) $nachricht->getHTMLBody(),
            kopfzeilen: $kopfzeilen,
            anhaenge: $anhaenge,
            datum: $this->alsDatum($nachricht),
            kennung: (string) $nachricht->getUid(),
        );
    }

    /**
     * Setzt das Lesezeichen, verschiebt oder löscht die Nachricht.
     *
     * Bei `Verschieben` ohne hinterlegten Zielordner wird bewusst nur das
     * Lesezeichen gesetzt: Eine Nachricht in einen nicht vorhandenen Ordner zu
     * schieben würde sie in manchen Serverkonfigurationen verlieren.
     *
     * Fehler beim Verschieben oder Löschen werden nicht durchgereicht, sondern
     * durch das Lesezeichen ersetzt. Andernfalls bliebe eine Nachricht, die
     * sich nicht verschieben lässt, für immer ungelesen und würde bei jedem
     * Lauf ein weiteres Mal verteilt.
     *
     * @param Message        $nachricht Die Nachricht aus der Bibliothek
     * @param Nachbehandlung $was       Vom Verteiler gewünschte Behandlung
     * @param Postfachzugang $zugang    Liefert den Zielordner zum Verschieben
     *
     * @return void
     */
    private function nachbehandeln(Message $nachricht, Nachbehandlung $was, Postfachzugang $zugang): void
    {
        if (Nachbehandlung::Behalten === $was) {
            return;
        }

        try {
            if (Nachbehandlung::Loeschen === $was) {
                $nachricht->delete();

                return;
            }

            if (Nachbehandlung::Verschieben === $was && '' !== $zugang->ordnerErledigt) {
                $nachricht->setFlag('Seen');
                $nachricht->move($zugang->ordnerErledigt);

                return;
            }

            $nachricht->setFlag('Seen');
        } catch (\Throwable) {
            // Der letzte Rettungsanker: Wenigstens das Lesezeichen setzen,
            // damit die Nachricht kein zweites Mal verteilt wird.
            try {
                $nachricht->setFlag('Seen');
            } catch (\Throwable) {
                // Auch das schlug fehl — hier ist nichts mehr zu retten. Der
                // Aufrufer erfährt es über die Zählung der Durchgänge.
            }
        }
    }

    /**
     * Macht aus einem Wert der Bibliothek eine schlichte Zeichenkette.
     *
     * `Attribute` kennt `toString()`, einfache Werte kommen dagegen schon als
     * Zeichenkette oder Zahl an. Arrays entstehen bei mehrfach vorhandenen
     * Kopfzeilen; sie werden mit Komma verbunden, weil für die Zwecke dieses
     * Bundles nur das Vorhandensein zählt.
     *
     * @param mixed $wert Ein Attribute-Objekt, eine Zeichenkette, ein Array
     *                    oder null
     *
     * @return string Der Textwert, '' wenn nichts Verwertbares vorliegt
     */
    private function alsText(mixed $wert): string
    {
        if (null === $wert) {
            return '';
        }

        if (\is_array($wert)) {
            return implode(', ', array_map(fn ($einzeln) => $this->alsText($einzeln), $wert));
        }

        if (\is_object($wert) && method_exists($wert, 'toString')) {
            return trim((string) $wert->toString());
        }

        if (\is_object($wert) && !method_exists($wert, '__toString')) {
            return '';
        }

        return trim((string) $wert);
    }

    /**
     * Liest das Versanddatum der Nachricht.
     *
     * Die Bibliothek liefert je nach Fassung ein Carbon-Objekt oder eine
     * Zeichenkette; beides wird über den Umweg der Textform in ein
     * DateTimeImmutable gebracht. Ein unlesbares oder fehlendes Datum ist kein
     * Fehler — der Verteiler benutzt dann die aktuelle Zeit.
     *
     * @param Message $nachricht Die Nachricht aus der Bibliothek
     *
     * @return \DateTimeImmutable|null Das Versanddatum, oder null
     */
    private function alsDatum(Message $nachricht): ?\DateTimeImmutable
    {
        $roh = $this->alsText($nachricht->getDate());

        if ('' === $roh) {
            return null;
        }

        try {
            return new \DateTimeImmutable($roh);
        } catch (\Exception) {
            return null;
        }
    }
}
