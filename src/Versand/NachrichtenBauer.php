<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Versand;

use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Postfach\EingehendeNachricht;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Baut die ausgehenden Nachrichten einer Mailingliste.
 *
 * Der Bauer kennt weder Datenbank noch Postfach; er bekommt alles übergeben
 * und gibt fertige Symfony-Nachrichten zurück. Dadurch lässt sich das
 * heikelste Stück — die Kopfzeilen — ohne Netz und ohne Contao prüfen.
 *
 * Die Absenderadresse ist grundsätzlich die Adresse der Liste, nie die des
 * ursprünglichen Verfassers. Das ist keine Bequemlichkeit, sondern Notwendigkeit:
 * Wäre der Verfasser der Absender, würde jede Weiterleitung die SPF-Prüfung des
 * Empfängers reißen, und bei einer DMARC-Richtlinie auf „reject" ginge die
 * Nachricht verloren. Der Verfasser steht stattdessen im angezeigten Namen und
 * in der Antwortadresse.
 */
class NachrichtenBauer
{
    /**
     * Name der eigenen Kopfzeile, an der das Bundle seine Nachrichten erkennt.
     *
     * Kommt eine Nachricht mit dieser Kopfzeile ins Postfach zurück, hat die
     * Liste sie selbst versendet. Sie erneut zu verteilen, würde eine Schleife
     * auslösen, die sich mit jedem Durchgang vervielfacht.
     */
    public const KOPF_KENNUNG = 'X-Contao-Mailingliste';

    /**
     * Baut die Nachricht, die ein Teilnehmer der Liste bekommt.
     *
     * Der ursprüngliche Verfasser erscheint als angezeigter Name („Max
     * Mustermann via Vorstandsliste"), die Antwortadresse richtet sich nach der
     * Einstellung der Liste: entweder zurück an die Liste, damit ein Gespräch
     * entsteht, oder unmittelbar an den Verfasser.
     *
     * @param MailinglistenModel             $liste      Die verteilende Liste
     * @param EingehendeNachricht           $eingang    Die eingegangene Nachricht
     * @param MailinglistenAbonnentModel     $empfaenger Der Teilnehmer, an den
     *                                                  diese Ausfertigung geht
     *
     * @return Email Die versandfertige Nachricht
     */
    public function verteilung(MailinglistenModel $liste, EingehendeNachricht $eingang, MailinglistenAbonnentModel $empfaenger): Email
    {
        $mail = $this->grundgeruest($liste);

        $anzeigename = '' !== $eingang->absenderName ? $eingang->absenderName : $eingang->absender;

        $mail
            ->from(new Address((string) $liste->adresse, sprintf('%s via %s', $anzeigename, $liste->titel)))
            ->to(new Address((string) $empfaenger->email, trim($empfaenger->vorname.' '.$empfaenger->nachname)))
            ->subject($this->betreff($liste, $eingang->betreff))
        ;

        // Antwortadresse: Die Voreinstellung „an die Liste" macht aus dem
        // Verteiler ein Gesprächsforum. „An den Absender" eignet sich für
        // reine Rundschreiben, bei denen Rückfragen nicht alle angehen.
        if ('absender' === $liste->antwortAn) {
            $mail->replyTo(new Address($eingang->absender, $anzeigename));
        } else {
            $mail->replyTo(new Address((string) $liste->adresse, (string) $liste->titel));
        }

        $text = $eingang->textOderAusHtml();
        $fussnote = trim((string) $liste->fussnote);

        if ('' !== $fussnote) {
            $text = rtrim($text)."\n\n-- \n".$this->platzhalter($fussnote, $liste, $eingang);
        }

        $mail->text($text);

        if ('' !== trim($eingang->html)) {
            $html = $eingang->html;

            if ('' !== $fussnote) {
                $html .= '<hr><p style="font-size:0.9em;color:#666">'
                    .nl2br(htmlspecialchars($this->platzhalter($fussnote, $liste, $eingang), ENT_QUOTES, 'UTF-8'))
                    .'</p>';
            }

            $mail->html($html);
        }

        if ($liste->anhaengeUebernehmen) {
            foreach ($eingang->anhaenge as $anhang) {
                $mail->attach($anhang['inhalt'], $anhang['name'], $anhang['mimetyp']);
            }
        }

        return $mail;
    }

    /**
     * Baut die Antwort an einen Absender, der nicht zur Liste gehört.
     *
     * Die Nachricht trägt `Auto-Submitted: auto-replied`. Das ist die
     * Kennzeichnung, an der andere Mailserver eine automatische Antwort
     * erkennen und ihrerseits nicht antworten — ohne sie können zwei
     * Automaten einander endlos beschreiben.
     *
     * @param MailinglistenModel   $liste   Die ablehnende Liste
     * @param EingehendeNachricht $eingang Die abgewiesene Nachricht
     *
     * @return Email Die versandfertige Ablehnung
     */
    public function ablehnung(MailinglistenModel $liste, EingehendeNachricht $eingang): Email
    {
        $vorlage = trim((string) $liste->ablehnungText);

        if ('' === $vorlage) {
            $vorlage = "Ihre Nachricht an ##liste## wurde nicht zugestellt.\n\n"
                ."Die Adresse ##absender## gehört nicht zu den Teilnehmern dieser Liste. "
                ."Wenn Sie aufgenommen werden möchten, senden Sie eine E-Mail an ##adresse## "
                ."mit dem Betreff \"##kennung##\".";
        }

        return $this->automatischeAntwort($liste, $eingang, sprintf('Nicht zugestellt: %s', $eingang->betreff), $vorlage);
    }

    /**
     * Baut die Empfangsbestätigung für einen Aufnahmeantrag.
     *
     * Der Antragsteller erfährt damit, dass sein Antrag angekommen ist und
     * dass noch jemand darüber entscheiden muss. Ohne diese Nachricht schickt
     * er den Antrag erfahrungsgemäß mehrfach.
     *
     * @param MailinglistenModel   $liste   Die beantragte Liste
     * @param EingehendeNachricht $eingang Der Aufnahmeantrag
     *
     * @return Email Die versandfertige Bestätigung
     */
    public function antragsBestaetigung(MailinglistenModel $liste, EingehendeNachricht $eingang): Email
    {
        $vorlage = trim((string) $liste->bestaetigungText);

        if ('' === $vorlage) {
            $vorlage = "Ihr Antrag auf Aufnahme in ##liste## ist eingegangen.\n\n"
                ."Die Adresse ##absender## wurde vorgemerkt. Sobald die Betreuung der Liste "
                ."den Antrag freigegeben hat, erhalten Sie die Nachrichten der Liste. "
                ."Sie bekommen keine weitere Mitteilung, wenn die Freigabe ausbleibt.";
        }

        return $this->automatischeAntwort($liste, $eingang, sprintf('Aufnahmeantrag für %s', $liste->titel), $vorlage);
    }

    /**
     * Baut die Bestätigung einer Abmeldung.
     *
     * @param MailinglistenModel   $liste   Die verlassene Liste
     * @param EingehendeNachricht $eingang Die Abmeldung
     *
     * @return Email Die versandfertige Bestätigung
     */
    public function abmeldeBestaetigung(MailinglistenModel $liste, EingehendeNachricht $eingang): Email
    {
        $text = "Sie wurden aus ##liste## ausgetragen.\n\n"
            ."Die Adresse ##absender## erhält ab sofort keine Nachrichten dieser Liste mehr. "
            ."Für eine erneute Aufnahme senden Sie eine E-Mail an ##adresse## "
            ."mit dem Betreff \"##kennung##\".";

        return $this->automatischeAntwort($liste, $eingang, sprintf('Abmeldung von %s', $liste->titel), $text);
    }

    /**
     * Baut die Mitteilung an die Betreuung, dass ein Antrag vorliegt.
     *
     * Ohne diese Mitteilung bliebe ein Antrag im Backend liegen, bis jemand
     * zufällig hineinsieht. Der Antragsteller wartet dann wochenlang.
     *
     * @param MailinglistenModel   $liste   Die betroffene Liste
     * @param EingehendeNachricht $eingang Der Aufnahmeantrag
     * @param string              $an      Adresse der Betreuung
     *
     * @return Email Die versandfertige Mitteilung
     */
    public function betreuerBenachrichtigung(MailinglistenModel $liste, EingehendeNachricht $eingang, string $an): Email
    {
        $text = sprintf(
            "Für die Mailingliste \"%s\" liegt ein Aufnahmeantrag vor.\n\n"
            ."Absender: %s%s\n"
            ."Betreff:  %s\n\n"
            ."Der Eintrag wurde mit dem Status \"beantragt\" angelegt. "
            ."Im Backend unter Mailinglisten lässt er sich auf \"aktiv\" setzen oder löschen.",
            $liste->titel,
            $eingang->absender,
            '' !== $eingang->absenderName ? ' ('.$eingang->absenderName.')' : '',
            $eingang->betreff,
        );

        $mail = $this->grundgeruest($liste);
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($an)
            ->subject(sprintf('[%s] Aufnahmeantrag von %s', $liste->titel, $eingang->absender))
            ->text($text)
        ;

        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');

        return $mail;
    }

    /**
     * Legt die Kopfzeilen an, die jede Nachricht dieser Liste tragen muss.
     *
     * `List-Id` und `List-Post` sind die in RFC 2919 und RFC 2369
     * beschriebenen Kennzeichen einer Mailingliste; viele Mailprogramme bauen
     * darauf ihre Filter auf. Die eigene Kennung dient dem Schleifenschutz:
     * Der Verteiler verwirft jede eingehende Nachricht, die sie schon trägt.
     *
     * @param MailinglistenModel $liste Die Liste, deren Kennzeichen gesetzt werden
     *
     * @return Email Eine leere Nachricht mit gesetzten Listenkopfzeilen
     */
    private function grundgeruest(MailinglistenModel $liste): Email
    {
        $mail = new Email();
        $kopf = $mail->getHeaders();

        $kopf->addTextHeader(self::KOPF_KENNUNG, (string) $liste->id);
        $kopf->addTextHeader('List-Id', sprintf('%s <%s>', $liste->titel, $this->listenKennung($liste)));
        $kopf->addTextHeader('List-Post', sprintf('<mailto:%s>', $liste->adresse));
        $kopf->addTextHeader('Precedence', 'list');

        // Der Umschlagabsender (Return-Path) soll die Listenadresse sein,
        // damit Unzustellbarkeitsberichte im Postfach der Liste landen und
        // nicht beim ursprünglichen Verfasser.
        $mail->sender(new Address((string) $liste->adresse, (string) $liste->titel));

        return $mail;
    }

    /**
     * Baut eine automatische Antwort an den Absender einer Nachricht.
     *
     * @param MailinglistenModel   $liste   Die antwortende Liste
     * @param EingehendeNachricht $eingang Die auslösende Nachricht
     * @param string              $betreff Betreff der Antwort, ohne Präfix
     * @param string              $vorlage Text mit Platzhaltern
     *
     * @return Email Die versandfertige Antwort
     */
    private function automatischeAntwort(MailinglistenModel $liste, EingehendeNachricht $eingang, string $betreff, string $vorlage): Email
    {
        $mail = $this->grundgeruest($liste);
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($eingang->absender)
            ->subject($betreff)
            ->text($this->platzhalter($vorlage, $liste, $eingang))
        ;

        // Ohne diese Kopfzeile beantworten Abwesenheitsautomaten die Ablehnung,
        // worauf die Liste erneut ablehnt — und so fort.
        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-replied');

        return $mail;
    }

    /**
     * Setzt den Betreff der Verteilung zusammen.
     *
     * Das Präfix wird nur ergänzt, wenn es nicht schon dasteht. Sonst wüchse
     * bei jeder Antwort innerhalb der Liste eine weitere Klammer an den
     * Betreff — nach fünf Antworten wäre vom eigentlichen Thema nichts mehr zu
     * sehen.
     *
     * @param MailinglistenModel $liste   Liefert das eingestellte Präfix
     * @param string            $betreff Der ursprüngliche Betreff
     *
     * @return string Der Betreff mit höchstens einem Präfix
     */
    private function betreff(MailinglistenModel $liste, string $betreff): string
    {
        $praefix = trim((string) $liste->betreffPraefix);

        if ('' === $betreff) {
            $betreff = '(ohne Betreff)';
        }

        if ('' === $praefix || false !== stripos($betreff, $praefix)) {
            return $betreff;
        }

        return $praefix.' '.$betreff;
    }

    /**
     * Ersetzt die Platzhalter in einem Textbaustein.
     *
     * Es sind bewusst keine Contao-Insert-Tags: Die Texte werden von der
     * Listenbetreuung gepflegt, nicht von Redakteuren, und ein Insert-Tag im
     * Cron-Kontext hätte weder Seite noch Anfrage zur Verfügung.
     *
     * @param string              $text    Der Baustein mit ##...##-Platzhaltern
     * @param MailinglistenModel   $liste   Liefert Titel, Adresse und Kennung
     * @param EingehendeNachricht $eingang Liefert Absender und Betreff
     *
     * @return string Der Text mit eingesetzten Werten
     */
    private function platzhalter(string $text, MailinglistenModel $liste, EingehendeNachricht $eingang): string
    {
        return strtr($text, [
            '##liste##' => (string) $liste->titel,
            '##adresse##' => (string) $liste->adresse,
            '##kennung##' => (string) $liste->aufnahmeKennung,
            '##abmeldekennung##' => (string) $liste->abmeldeKennung,
            '##absender##' => $eingang->absender,
            '##absendername##' => $eingang->absenderName,
            '##betreff##' => $eingang->betreff,
        ]);
    }

    /**
     * Bildet die List-Id einer Liste.
     *
     * Nach RFC 2919 soll die Kennung wie ein Domänenname aufgebaut sein und
     * dauerhaft gleich bleiben. Der Domänenteil wird aus der Listenadresse
     * gewonnen; fehlt dort ein „@", springt „lists.invalid" ein, was nach RFC
     * 2606 garantiert nie eine echte Domäne ist.
     *
     * @param MailinglistenModel $liste Die Liste
     *
     * @return string Die Kennung in der Form "liste-7.example.org"
     */
    private function listenKennung(MailinglistenModel $liste): string
    {
        $adresse = (string) $liste->adresse;
        $domaene = substr(strrchr($adresse, '@') ?: '', 1);

        return sprintf('liste-%d.%s', (int) $liste->id, '' !== $domaene ? $domaene : 'lists.invalid');
    }
}
