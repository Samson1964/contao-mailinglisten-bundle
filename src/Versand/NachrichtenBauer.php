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

        // Ein Abmeldeweg im Kopf ist heute Pflicht für jeden, der an viele
        // verteilt: Google und Yahoo verlangen ihn seit Februar 2024
        // ausdrücklich, Microsoft bewertet ihn ebenso. Sein Fehlen ist ein
        // Spam-Merkmal — und zwar bei **jedem** Empfänger, während eine
        // Zulassungsliste immer nur den einen Anbieter erreicht, bei dem sie
        // eingetragen wurde.
        //
        // Der Verweis nutzt die Abmeldekennung, die die Liste ohnehin kennt;
        // eine Ein-Klick-Abmeldung (List-Unsubscribe-Post) bliebe wirkungslos,
        // weil sie einen HTTP-Endpunkt verlangt, den dieses Bundle nicht hat.
        $abmelden = trim((string) $liste->abmeldeKennung);

        if ('' !== $abmelden) {
            $mail->getHeaders()->addTextHeader(
                'List-Unsubscribe',
                sprintf('<mailto:%s?subject=%s>', $liste->adresse, rawurlencode($abmelden)),
            );
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
     * Baut die Mail mit dem Bestätigungslink zur Anmeldung über die Webseite.
     *
     * Diese Mail ist der einzige Beweis, dass der Eintragende auch Zugriff auf
     * das angegebene Postfach hat — beim Formular auf der Webseite kann
     * schließlich jeder eine fremde Adresse hineinschreiben. Erst der Klick
     * macht aus dem Eintrag einen Antrag, über den die Betreuung entscheidet.
     *
     * Der Text sagt ausdrücklich, was zu tun ist, wenn man sich **nicht**
     * angemeldet hat: nichts. Dann läuft der Link ab und der Eintrag ist
     * gegenstandslos. Wer eine solche Mail unerwartet bekommt, soll nicht das
     * Gefühl haben, aktiv werden zu müssen.
     *
     * @param MailinglistenModel $liste  Die beantragte Liste
     * @param string             $an     Die einzutragende Adresse
     * @param string             $name   Angezeigter Name, darf leer sein
     * @param string             $link   Vollständige Adresse des Bestätigungslinks
     *
     * @return Email Die versandfertige Bestätigungsanfrage
     */
    public function anmeldeBestaetigung(MailinglistenModel $liste, string $an, string $name, string $link): Email
    {
        $text = sprintf(
            "%sfür die Mailingliste \"%s\" wurde diese Adresse eingetragen.\n\n"
            ."Bitte bestätigen Sie die Anmeldung über diesen Link:\n\n"
            ."%s\n\n"
            ."Danach entscheidet die Betreuung der Liste über die Aufnahme; Sie "
            ."bekommen davon eine weitere Nachricht.\n\n"
            ."Der Link gilt zwei Tage.\n\n"
            ."Falls Sie sich nicht angemeldet haben, brauchen Sie nichts zu tun. "
            ."Ohne Ihre Bestätigung geschieht nichts weiter, und der Eintrag "
            ."verfällt von selbst.",
            '' !== trim($name) ? 'Guten Tag '.trim($name).",\n\n" : "Guten Tag,\n\n",
            $liste->titel,
            $link,
        );

        $mail = new Email();
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($an)
            ->subject(sprintf('Bitte bestätigen: Anmeldung zu %s', $liste->titel))
            ->text($text)
        ;

        // Bewusst ohne die Listenkopfzeilen aus grundgeruest(): Diese Mail ist
        // keine Nachricht der Liste, und der Empfänger ist noch kein
        // Teilnehmer. Ein List-Unsubscribe wäre hier sogar irreführend.
        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');

        return $mail;
    }

    /**
     * Baut die Auskunft an jemanden, der sich erneut anmelden wollte.
     *
     * Auf dem Bildschirm erfährt der Besucher nie, ob eine Adresse schon auf
     * der Liste steht — sonst ließe sich über das Formular der Mitgliederkreis
     * abfragen. Die Auskunft geht deshalb hierher: an das Postfach, dessen
     * Inhaber ohnehin ein Recht darauf hat, seinen Stand zu erfahren.
     *
     * @param MailinglistenModel $liste Die betroffene Liste
     * @param string             $an    Die angefragte Adresse
     * @param string             $lage  'aktiv' für bereits eingetragen,
     *                                  'beantragt' für einen laufenden Antrag
     *
     * @return Email Die versandfertige Auskunft
     */
    public function anmeldeHinweis(MailinglistenModel $liste, string $an, string $lage): Email
    {
        $text = match ($lage) {
            'beantragt' => sprintf(
                "Guten Tag,\n\n"
                ."für die Mailingliste \"%s\" liegt für diese Adresse bereits ein Antrag vor.\n\n"
                ."Er wartet auf die Freigabe durch die Betreuung der Liste. Sie brauchen "
                ."nichts weiter zu tun; sobald entschieden ist, hören Sie von uns.",
                $liste->titel,
            ),
            default => sprintf(
                "Guten Tag,\n\n"
                ."diese Adresse ist bereits Teilnehmer der Mailingliste \"%s\".\n\n"
                ."Eine erneute Anmeldung ist nicht nötig. Wenn Sie keine Nachrichten der "
                ."Liste mehr erhalten möchten, senden Sie eine E-Mail an %s mit dem "
                ."Betreff \"%s\".",
                $liste->titel,
                $liste->adresse,
                $liste->abmeldeKennung,
            ),
        };

        $mail = new Email();
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($an)
            ->subject(sprintf('Ihre Anmeldung zu %s', $liste->titel))
            ->text($text)
        ;

        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');

        return $mail;
    }

    /**
     * Meldet der Betreuung einen über die Webseite bestätigten Antrag.
     *
     * Die Meldung geht erst nach dem Klick auf den Bestätigungslink hinaus.
     * Vorher wäre sie eine Mitteilung über jemanden, der von seiner
     * angeblichen Anmeldung womöglich gar nichts weiß — und bei mutwilligen
     * Eintragungen eine bequeme Möglichkeit, die Betreuung zuzuschütten.
     *
     * @param MailinglistenModel         $liste   Die betroffene Liste
     * @param MailinglistenAbonnentModel $eintrag Der bestätigte Eintrag
     * @param string                     $an      Adresse der Betreuung
     *
     * @return Email Die versandfertige Meldung
     */
    public function antragUeberWebseite(MailinglistenModel $liste, MailinglistenAbonnentModel $eintrag, string $an): Email
    {
        $name = trim($eintrag->vorname.' '.$eintrag->nachname);

        $text = sprintf(
            "Für die Mailingliste \"%s\" liegt ein Aufnahmeantrag über die Webseite vor.\n\n"
            ."Adresse: %s\n"
            ."Name:    %s\n\n"
            ."Die Adresse wurde per Bestätigungslink überprüft; der Antragsteller hat "
            ."also Zugriff auf dieses Postfach.\n\n"
            ."Der Eintrag steht im Backend unter Mailinglisten auf \"Aufnahme beantragt\". "
            ."Zum Aufnehmen den Status auf \"aktiv\" setzen, zum Ablehnen den Eintrag löschen.",
            $liste->titel,
            $eintrag->email,
            '' !== $name ? $name : '(nicht angegeben)',
        );

        $mail = new Email();
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($an)
            ->subject(sprintf('[%s] Aufnahmeantrag von %s', $liste->titel, $eintrag->email))
            ->text($text)
            ->replyTo(new Address((string) $eintrag->email, '' !== $name ? $name : (string) $eintrag->email))
        ;

        $mail->getHeaders()->addTextHeader('Auto-Submitted', 'auto-generated');

        return $mail;
    }

    /**
     * Baut die Mitteilung an die Betreuung über eine abgewiesene Nachricht.
     *
     * Ohne diese Mitteilung bleibt eine Ablehnung unbemerkt, bis jemand von
     * sich aus in den Verlauf sieht. Gerade der häufigste Fall — ein
     * Teilnehmer schreibt von einer zweiten, nicht eingetragenen Adresse —
     * fällt dann niemandem auf, und der Absender wartet vergeblich.
     *
     * Die Mitteilung enthält den Anfang des Nachrichtentextes. Damit lässt
     * sich ohne Blick ins Postfach entscheiden, ob es sich um Spam handelt
     * oder um jemanden, der aufgenommen werden sollte.
     *
     * @param MailinglistenModel   $liste   Die ablehnende Liste
     * @param EingehendeNachricht $eingang Die abgewiesene Nachricht
     * @param string              $an      Adresse der Betreuung
     * @param string              $grund   Der Grund der Ablehnung im Klartext
     *
     * @return Email Die versandfertige Mitteilung
     */
    public function ablehnungsMeldung(MailinglistenModel $liste, EingehendeNachricht $eingang, string $an, string $grund): Email
    {
        $auszug = trim($eingang->textOderAusHtml());

        if (mb_strlen($auszug) > 500) {
            $auszug = mb_substr($auszug, 0, 500).' […]';
        }

        $text = sprintf(
            "Die Mailingliste \"%s\" hat eine Nachricht abgewiesen.\n\n"
            ."Absender: %s%s\n"
            ."Betreff:  %s\n"
            ."Grund:    %s\n\n"
            ."%s\n\n"
            ."--\n"
            ."Wenn der Absender aufgenommen werden soll, lässt er sich im Backend "
            ."unter Mailinglisten als Teilnehmer eintragen. Diese Mitteilung lässt "
            ."sich an der Liste abschalten.",
            $liste->titel,
            $eingang->absender,
            '' !== $eingang->absenderName ? ' ('.$eingang->absenderName.')' : '',
            '' !== $eingang->betreff ? $eingang->betreff : '(ohne Betreff)',
            $grund,
            '' !== $auszug ? "Anfang der Nachricht:\n\n".$auszug : '(Die Nachricht hatte keinen lesbaren Text.)',
        );

        $mail = $this->grundgeruest($liste);
        $mail
            ->from(new Address((string) $liste->adresse, (string) $liste->titel))
            ->to($an)
            ->subject(sprintf('[%s] Abgewiesen: %s', $liste->titel, $eingang->betreff))
            ->text($text)
        ;

        // Antworten sollen an den ursprünglichen Absender gehen können, ohne
        // dass die Betreuung dessen Adresse heraussuchen muss.
        $mail->replyTo(new Address($eingang->absender, $eingang->absenderName ?: $eingang->absender));
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
