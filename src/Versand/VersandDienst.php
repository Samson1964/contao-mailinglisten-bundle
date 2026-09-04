<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Versand;

use Psr\Log\LoggerInterface;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Sicherheit\Geheimspeicher;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/**
 * Verschickt die Nachrichten einer Mailingliste über deren eigenes Postfach.
 *
 * Der Versand über den SMTP-Zugang der Liste ist kein Selbstzweck: Nur so
 * stimmt die Absenderadresse mit dem versendenden Server überein, und nur dann
 * gehen SPF- und DKIM-Prüfung beim Empfänger auf. Ginge die Mail über den
 * allgemeinen Contao-Mailer hinaus, trüge sie eine Absenderadresse, für die
 * dieser Server nicht zuständig ist — der sicherste Weg in den Spam-Ordner.
 *
 * Ist an der Liste kein SMTP-Host hinterlegt, greift der Dienst auf den
 * Contao-Mailer zurück, damit eine unfertig eingerichtete Liste nicht
 * stillschweigend gar nichts versendet.
 */
class VersandDienst
{
    /**
     * Bereits aufgebaute Transporte, nach der ID der Mailingliste.
     *
     * Ohne diesen Zwischenspeicher würde für jeden einzelnen Empfänger eine
     * neue SMTP-Verbindung aufgebaut und wieder geschlossen. Bei einer Liste
     * mit hundert Teilnehmern sind das hundert Anmeldevorgänge, was die
     * meisten Anbieter als Missbrauch werten und sperren.
     *
     * @var array<int, TransportInterface|null>
     */
    private array $transporte = [];

    /**
     * Die Meldung des zuletzt gescheiterten Versands.
     *
     * Der Verteiler holt sie sich ab, um sie in den Verlauf zu schreiben. Ohne
     * das stünde dort nur eine Zahl gescheiterter Zustellungen, und der Grund
     * läge allein im Fehlerprotokoll der Anwendung — dort sucht bei einer
     * stillen Mailingliste niemand.
     *
     * @var string
     */
    private string $letzterFehler = '';

    /**
     * @param MailerInterface $standardMailer  Der Mailer aus der
     *                                         Contao-Installation; dient als
     *                                         Rückfallebene
     * @param Geheimspeicher  $geheimspeicher  Entschlüsselt das SMTP-Kennwort
     *                                         aus der Datenbank
     * @param LoggerInterface $logger          Nimmt Versandfehler auf; im
     *                                         Container ist das
     *                                         `monolog.logger.contao.error`
     */
    public function __construct(
        private readonly MailerInterface $standardMailer,
        private readonly Geheimspeicher $geheimspeicher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Versendet eine fertig aufgebaute Nachricht über den Zugang der Liste.
     *
     * Fehler beim Versand an einen einzelnen Empfänger werden protokolliert,
     * aber nicht weitergereicht: Eine einzige tote Adresse darf nicht
     * verhindern, dass die übrigen Teilnehmer die Nachricht bekommen.
     *
     * @param MailinglistenModel $liste Die Liste, über deren Zugang versendet wird
     * @param Email             $mail  Die vollständige Nachricht mit Absender,
     *                                 Empfänger, Betreff und Inhalt
     *
     * @return bool true, wenn die Nachricht an den Server übergeben wurde;
     *              false bei einem Versandfehler
     */
    public function versenden(MailinglistenModel $liste, Email $mail): bool
    {
        try {
            $transport = $this->transportFuer($liste);

            if (null === $transport) {
                $this->standardMailer->send($mail);
            } else {
                $transport->send($mail);
            }

            return true;
        } catch (\Throwable $e) {
            $empfaenger = implode(', ', array_map(static fn ($a) => $a->getAddress(), $mail->getTo()));

            $this->logger->error(sprintf(
                'Mailingliste "%s": Versand an "%s" fehlgeschlagen: %s',
                $liste->titel,
                $empfaenger,
                $e->getMessage(),
            ));

            $this->letzterFehler = sprintf('%s: %s', $empfaenger, $e->getMessage());

            // Ein Transport, der einmal gescheitert ist, steckt womöglich in
            // einem unbrauchbaren Zustand — etwa nach einer vom Server
            // gekappten Verbindung. Ihn wegzuwerfen kostet einen erneuten
            // Anmeldevorgang, rettet aber die restlichen Empfänger.
            unset($this->transporte[(int) $liste->id]);

            return false;
        }
    }

    /**
     * Gibt die Meldung des zuletzt gescheiterten Versands zurück.
     *
     * @return string Die Meldung samt Empfängeradresse, oder '' wenn seit dem
     *                letzten Abruf nichts fehlgeschlagen ist
     */
    public function letzterFehler(): string
    {
        return $this->letzterFehler;
    }

    /**
     * Wirft die zwischengespeicherten Transporte weg.
     *
     * Der Cronjob ruft das am Ende eines Durchgangs auf. Die SMTP-Verbindungen
     * werden dabei durch den Aufräumvorgang von PHP geschlossen. Wichtig ist
     * das vor allem im Dauerbetrieb (Messenger-Worker), wo derselbe Dienst
     * über Stunden lebt und eine stehengelassene Verbindung längst vom Server
     * gekappt wäre.
     *
     * @return void
     */
    public function verbindungenSchliessen(): void
    {
        foreach ($this->transporte as $transport) {
            if ($transport instanceof EsmtpTransport) {
                try {
                    $transport->stop();
                } catch (\Throwable) {
                    // Eine bereits gekappte Verbindung zu schließen ist kein
                    // Fehler, der jemanden interessiert.
                }
            }
        }

        $this->transporte = [];
    }

    /**
     * Baut den Transport für eine Liste auf oder holt ihn aus dem Speicher.
     *
     * Die Zuordnung von Verschlüsselungsart zum `$tls`-Parameter von Symfony
     * ist nicht selbsterklärend: `true` steht für die Verschlüsselung ab dem
     * ersten Byte (Port 465), `null` überlässt Symfony die Entscheidung und
     * führt bei Port 587 zum nachträglichen STARTTLS, `false` schaltet die
     * eingebaute TLS-Aushandlung ab.
     *
     * @param MailinglistenModel $liste Die Liste mit den SMTP-Angaben
     *
     * @return TransportInterface|null Der Transport, oder null wenn kein
     *                                 eigener SMTP-Zugang hinterlegt ist und
     *                                 der Contao-Mailer benutzt werden soll
     */
    private function transportFuer(MailinglistenModel $liste): ?TransportInterface
    {
        $id = (int) $liste->id;

        if (\array_key_exists($id, $this->transporte)) {
            return $this->transporte[$id];
        }

        if ('' === trim((string) $liste->smtpHost)) {
            return $this->transporte[$id] = null;
        }

        $tls = match ($liste->smtpVerschluesselung) {
            'ssl' => true,
            'tls' => null,
            default => false,
        };

        $transport = new EsmtpTransport(
            trim((string) $liste->smtpHost),
            (int) $liste->smtpPort ?: 587,
            $tls,
            null,
            $this->logger,
        );

        if ('' !== trim((string) $liste->smtpBenutzer)) {
            $transport->setUsername(trim((string) $liste->smtpBenutzer));
            $transport->setPassword($this->geheimspeicher->entschluesseln((string) $liste->smtpKennwort));
        }

        return $this->transporte[$id] = $transport;
    }
}
