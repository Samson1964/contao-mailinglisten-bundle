<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\ModuleModel;
use Contao\Template;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Versand\NachrichtenBauer;
use Schachbulle\ContaoMailinglistenBundle\Versand\VersandDienst;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend-Modul zur Anmeldung an einer Mailingliste.
 *
 * Der Weg ist zweistufig und das aus gutem Grund: In ein Formular auf einer
 * öffentlichen Seite kann jeder eine **fremde** Adresse eintragen. Erst der
 * Klick auf den Bestätigungslink beweist, dass der Eintragende Zugriff auf das
 * Postfach hat. Dann — und erst dann — wird daraus ein Antrag, über den die
 * Betreuung im Backend entscheidet.
 *
 *   Formular  →  Status „unbestaetigt“  →  Klick auf den Link
 *             →  Status „beantragt“     →  Freigabe im Backend
 *             →  Status „aktiv“
 *
 * Die Meldung auf dem Bildschirm ist in **jedem** Fall dieselbe, gleich ob die
 * Adresse neu ist, schon auf der Liste steht oder gesperrt wurde. Andernfalls
 * ließe sich über das Formular abfragen, wer Mitglied der Liste ist — bei
 * einem Verteiler zu einem Thema wie Inklusion wäre das eine Auskunft über
 * Gesundheitsdaten. Was die Lage wirklich ist, erfährt allein der Inhaber des
 * Postfachs, per Nachricht.
 */
class AnmeldungController extends AbstractFrontendModuleController
{
    /**
     * Wie lange ein Bestätigungslink gilt, in Sekunden.
     */
    private const GUELTIGKEIT = 172800;

    /**
     * @param NachrichtenBauer        $bauer        Baut die Bestätigungs- und Hinweismails
     * @param VersandDienst           $versand      Verschickt sie über den Zugang der Liste
     * @param ContaoCsrfTokenManager  $tokenManager Liefert das REQUEST_TOKEN für das Formular
     * @param LoggerInterface         $logger       Nimmt Fehler auf, die dem Besucher nicht
     *                                              zumutbar sind
     */
    public function __construct(
        private readonly NachrichtenBauer $bauer,
        private readonly VersandDienst $versand,
        private readonly ContaoCsrfTokenManager $tokenManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Stellt das Modul dar und verarbeitet Formular wie Bestätigungslink.
     *
     * Der Parameter ist mit `Contao\Template` typisiert, nicht mit dem in
     * Contao 5 verwendeten `FragmentTemplate`: PHP erlaubt beim Überschreiben
     * einen **breiteren** Parametertyp, und `Template` ist dessen Elternklasse.
     * Dieselbe Datei erfüllt damit die abstrakte Signatur beider
     * Contao-Fassungen.
     *
     * @param Template    $template Das Modul-Template
     * @param ModuleModel $model    Die Einstellungen des Moduls
     * @param Request     $request  Die laufende Anfrage
     *
     * @return Response Die gerenderte Ausgabe
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $liste = MailinglistenModel::findByPk((int) $model->mlListe);

        // Eine gelöschte oder abgeschaltete Liste ergibt kein Formular. Der
        // Besucher soll dann nichts sehen, was er ohnehin nicht benutzen kann.
        if (null === $liste || !$liste->published) {
            $template->verfuegbar = false;

            return $template->getResponse();
        }

        $template->verfuegbar = true;
        $template->listentitel = $liste->titel;
        $template->listenbeschreibung = $liste->beschreibung;
        $template->einwilligungstext = trim((string) $model->mlEinwilligung);
        $template->requestToken = $this->tokenManager->getDefaultTokenValue();
        $template->formSubmit = 'mailingliste_anmeldung_'.$model->id;
        $template->honigtopf = 'ml_'.$model->id.'_web';
        $template->formularZeigen = true;
        $template->meldung = '';
        $template->fehler = [];
        $template->werte = ['email' => '', 'vorname' => '', 'nachname' => ''];

        $merkmal = trim((string) $request->query->get('bestaetigung', ''));

        if ('' !== $merkmal) {
            $this->bestaetigen($template, $merkmal);

            return $template->getResponse();
        }

        if ($request->isMethod('POST') && $template->formSubmit === $request->request->get('FORM_SUBMIT')) {
            $this->eintragen($template, $liste, $request);
        }

        return $template->getResponse();
    }

    /**
     * Löst einen Bestätigungslink ein.
     *
     * Aus dem unbestätigten Eintrag wird ein Antrag; das Merkmal wird dabei
     * gelöscht, damit derselbe Link kein zweites Mal wirkt. Erst jetzt erfährt
     * die Betreuung von dem Antrag — vorher wäre es eine Meldung über jemanden,
     * der von seiner angeblichen Anmeldung womöglich nichts weiß.
     *
     * @param Template $template Das Modul-Template
     * @param string   $merkmal  Der Wert aus dem Link
     *
     * @return void
     */
    private function bestaetigen(Template $template, string $merkmal): void
    {
        $template->formularZeigen = false;
        $eintrag = MailinglistenAbonnentModel::findByMerkmal($merkmal, self::GUELTIGKEIT);

        if (null === $eintrag) {
            $template->fehler[] = $GLOBALS['TL_LANG']['MSC']['mlLinkUngueltig']
                ?? 'Dieser Bestätigungslink ist ungültig oder abgelaufen. Bitte melden Sie sich erneut an.';

            $template->formularZeigen = true;

            return;
        }

        $liste = MailinglistenModel::findByPk((int) $eintrag->pid);

        $eintrag->status = MailinglistenAbonnentModel::STATUS_BEANTRAGT;
        $eintrag->token = '';
        $eintrag->tokenErzeugt = 0;
        $eintrag->tstamp = time();
        $eintrag->notiz = trim($eintrag->notiz."\n".sprintf('Am %s über die Webseite bestätigt.', date('d.m.Y H:i')));
        $eintrag->save();

        if (null !== $liste) {
            $this->betreuungBenachrichtigen($liste, $eintrag);
        }

        $template->meldung = $GLOBALS['TL_LANG']['MSC']['mlBestaetigt']
            ?? 'Vielen Dank. Ihre Anmeldung ist bestätigt und liegt nun der Betreuung der Liste zur Freigabe vor.';
    }

    /**
     * Nimmt das ausgefüllte Formular entgegen.
     *
     * Die Bildschirmmeldung am Ende ist immer dieselbe — siehe die Erläuterung
     * am Kopf der Klasse. Unterschiedlich ist allein, was per Nachricht an die
     * angegebene Adresse geht.
     *
     * @param Template            $template Das Modul-Template
     * @param MailinglistenModel  $liste    Die Liste, für die angemeldet wird
     * @param Request             $request  Die laufende Anfrage
     *
     * @return void
     */
    private function eintragen(Template $template, MailinglistenModel $liste, Request $request): void
    {
        $email = strtolower(trim((string) $request->request->get('email', '')));
        $vorname = strip_tags(trim((string) $request->request->get('vorname', '')));
        $nachname = strip_tags(trim((string) $request->request->get('nachname', '')));

        $template->werte = ['email' => $email, 'vorname' => $vorname, 'nachname' => $nachname];

        // Der Honigtopf ist im Stylesheet versteckt. Ein Mensch sieht ihn
        // nicht und füllt ihn nicht aus; ein Formularroboter trägt in jedes
        // Feld etwas ein. Wir tun so, als sei alles in Ordnung — dann merkt
        // die Gegenseite nicht, woran sie gescheitert ist.
        if ('' !== trim((string) $request->request->get($template->honigtopf, ''))) {
            $template->formularZeigen = false;
            $template->meldung = $this->abschlussmeldung($email);

            return;
        }

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $template->fehler[] = $GLOBALS['TL_LANG']['MSC']['mlEmailUngueltig']
                ?? 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
        }

        if ('' !== trim((string) $template->einwilligungstext) && !$request->request->get('einwilligung')) {
            $template->fehler[] = $GLOBALS['TL_LANG']['MSC']['mlEinwilligungFehlt']
                ?? 'Bitte bestätigen Sie den Hinweis zum Datenschutz.';
        }

        if ($template->fehler) {
            return;
        }

        try {
            $this->anmeldungVerarbeiten($liste, $email, $vorname, $nachname, $request);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Mailingliste "%s": Anmeldung von "%s" fehlgeschlagen: %s', $liste->titel, $email, $e->getMessage()));

            $template->fehler[] = $GLOBALS['TL_LANG']['MSC']['mlFehler']
                ?? 'Die Anmeldung konnte nicht gespeichert werden. Bitte versuchen Sie es später noch einmal.';

            return;
        }

        $template->formularZeigen = false;
        $template->meldung = $this->abschlussmeldung($email);
    }

    /**
     * Entscheidet, was mit der angegebenen Adresse zu geschehen hat.
     *
     * Vier Lagen sind zu unterscheiden. Nur in zweien wird etwas gespeichert;
     * in den übrigen bleibt der Bestand unangetastet, damit sich weder eine
     * Sperre noch eine bestehende Mitgliedschaft über das Formular aushebeln
     * lässt.
     *
     * @param MailinglistenModel $liste    Die Liste
     * @param string             $email    Die geprüfte Adresse
     * @param string             $vorname  Vorname aus dem Formular
     * @param string             $nachname Nachname aus dem Formular
     * @param Request            $request  Für den Aufbau des Bestätigungslinks
     *
     * @return void
     */
    private function anmeldungVerarbeiten(MailinglistenModel $liste, string $email, string $vorname, string $nachname, Request $request): void
    {
        $vorhanden = MailinglistenAbonnentModel::findByListeUndEmail((int) $liste->id, $email);

        // Gesperrt: still bleiben. Weder eine Nachricht noch eine Änderung —
        // sonst wäre die Sperre über das Formular zu umgehen oder zumindest zu
        // erkennen.
        if (null !== $vorhanden && MailinglistenAbonnentModel::STATUS_GESPERRT === $vorhanden->status) {
            return;
        }

        if (null !== $vorhanden && MailinglistenAbonnentModel::STATUS_AKTIV === $vorhanden->status) {
            $this->versand->versenden($liste, $this->bauer->anmeldeHinweis($liste, $email, 'aktiv'));

            return;
        }

        if (null !== $vorhanden && MailinglistenAbonnentModel::STATUS_BEANTRAGT === $vorhanden->status) {
            $this->versand->versenden($liste, $this->bauer->anmeldeHinweis($liste, $email, 'beantragt'));

            return;
        }

        // Neu oder ein noch unbestätigter Eintrag: In beiden Fällen ein
        // frisches Merkmal und ein neuer Link. Ein zweiter Anlauf soll nicht
        // daran scheitern, dass die erste Nachricht im Spam gelandet ist.
        $eintrag = $vorhanden ?? new MailinglistenAbonnentModel();

        if (null === $vorhanden) {
            $eintrag->pid = (int) $liste->id;
            $eintrag->email = $email;
            $eintrag->darfSenden = '1';
            $eintrag->darfEmpfangen = '1';
            $eintrag->beigetreten = time();
            $eintrag->notiz = sprintf('Am %s über die Webseite eingetragen.', date('d.m.Y H:i'));
        }

        $eintrag->tstamp = time();
        $eintrag->status = MailinglistenAbonnentModel::STATUS_UNBESTAETIGT;
        $eintrag->token = bin2hex(random_bytes(16));
        $eintrag->tokenErzeugt = time();

        // Namen nur ergänzen, nie überschreiben: Sonst könnte ein Fremder die
        // Anzeige eines bestehenden Eintrags verändern.
        if ('' === trim((string) $eintrag->vorname)) {
            $eintrag->vorname = $vorname;
        }

        if ('' === trim((string) $eintrag->nachname)) {
            $eintrag->nachname = $nachname;
        }

        $eintrag->save();

        $link = sprintf(
            '%s%s?bestaetigung=%s',
            $request->getSchemeAndHttpHost(),
            $request->getPathInfo(),
            $eintrag->token,
        );

        $this->versand->versenden($liste, $this->bauer->anmeldeBestaetigung($liste, $email, trim($vorname.' '.$nachname), $link));
    }

    /**
     * Meldet der Betreuung, dass ein bestätigter Antrag vorliegt.
     *
     * @param MailinglistenModel        $liste   Die Liste
     * @param MailinglistenAbonnentModel $eintrag Der bestätigte Eintrag
     *
     * @return void
     */
    private function betreuungBenachrichtigen(MailinglistenModel $liste, MailinglistenAbonnentModel $eintrag): void
    {
        $roh = trim((string) $liste->benachrichtigung);

        if ('' === $roh) {
            return;
        }

        foreach (array_filter(array_map('trim', explode(',', $roh))) as $adresse) {
            $this->versand->versenden($liste, $this->bauer->antragUeberWebseite($liste, $eintrag, $adresse));
        }
    }

    /**
     * Liefert die Meldung, die nach jedem Formularversand erscheint.
     *
     * Sie ist bewusst in allen Fällen gleich und nennt keine Einzelheiten
     * über den Bestand der Liste.
     *
     * @param string $email Die angegebene Adresse, zur Rückversicherung des Besuchers
     *
     * @return string Der anzuzeigende Text
     */
    private function abschlussmeldung(string $email): string
    {
        $vorlage = $GLOBALS['TL_LANG']['MSC']['mlAbschluss']
            ?? 'Vielen Dank. Wenn eine Anmeldung möglich ist, haben wir eine Nachricht an %s geschickt. Bitte folgen Sie den Hinweisen darin.';

        return sprintf($vorlage, htmlspecialchars($email, ENT_QUOTES, 'UTF-8'));
    }
}
