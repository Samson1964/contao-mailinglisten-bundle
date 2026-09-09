<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglistenProtokollModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Nimmt die Ein-Klick-Abmeldung aus dem Mailprogramm entgegen.
 *
 * Das Verfahren ist in RFC 8058 beschrieben: Die Nachricht trägt neben
 * `List-Unsubscribe` die Kopfzeile `List-Unsubscribe-Post`, und das
 * Mailprogramm zeigt daraufhin einen Abmeldeknopf an. Ein Klick darauf schickt
 * einen **POST** an die angegebene Adresse — ohne Formular, ohne Rückfrage und
 * ohne dass der Absender die Webseite je zu sehen bekommt.
 *
 * Deshalb ist dies eine eigene Route und **kein Frontend-Modul**: Contaos
 * RequestTokenListener weist jeden POST ohne gültiges REQUEST_TOKEN mit 403 ab,
 * und ein Mailprogramm kennt dieses Merkmal naturgemäß nicht. Die Route trägt
 * daher `_token_check: false`.
 *
 * **GET und POST sind absichtlich verschieden:**
 *
 * * **POST** meldet sofort ab. So verlangt es RFC 8058.
 * * **GET** zeigt nur eine Seite mit einem Knopf. Ein GET, der sofort abmeldet,
 *   wäre gefährlich: Sicherheitsprüfungen mancher Mailanbieter rufen jede
 *   Adresse in einer Nachricht vorab auf, und Teilnehmer flögen reihenweise aus
 *   der Liste, ohne je geklickt zu haben.
 */
class AbmeldenController
{
    /**
     * @param ContaoFramework $framework Muss vor jedem Model-Zugriff laufen
     * @param LoggerInterface $logger    Nimmt auf, was schiefging
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Behandelt einen Aufruf des Abmeldeknopfes.
     *
     * Die Antwort ist immer 200 und immer freundlich, auch bei einem unbekannten
     * Merkmal. Ein Mailprogramm wertet einen Fehlercode als „Abmeldung
     * gescheitert" und zeigt dem Benutzer eine Warnung — bei einem Merkmal, das
     * schlicht schon eingelöst wurde, wäre das irreführend. Wer den Knopf ein
     * zweites Mal drückt, soll dieselbe Bestätigung sehen wie beim ersten Mal.
     *
     * @param Request $request Die laufende Anfrage
     * @param string  $token   Das dauerhafte Abmeldemerkmal aus der Adresse
     *
     * @return Response Eine schlichte HTML-Seite; bei POST die Bestätigung,
     *                  bei GET die Seite mit dem Knopf
     */
    public function __invoke(Request $request, string $token): Response
    {
        $this->framework->initialize();

        $eintrag = MailinglistenAbonnentModel::findByAbmeldeToken($token);

        if (!$request->isMethod('POST')) {
            return $this->seite(
                'Abmeldung bestätigen',
                null === $eintrag
                    ? '<p>Dieser Abmeldelink ist nicht mehr gültig. Möglicherweise wurde die Abmeldung bereits vollzogen.</p>'
                    : sprintf(
                        '<p>Möchten Sie sich von der Mailingliste <strong>%s</strong> abmelden?</p>'
                        .'<form method="post"><button type="submit">Ja, abmelden</button></form>',
                        htmlspecialchars((string) ($this->listeVon($eintrag)?->titel ?? ''), ENT_QUOTES, 'UTF-8'),
                    ),
            );
        }

        if (null === $eintrag) {
            return $this->seite('Abmeldung', '<p>Sie erhalten keine weiteren Nachrichten dieser Liste.</p>');
        }

        $liste = $this->listeVon($eintrag);
        $adresse = (string) $eintrag->email;

        try {
            // Wie bei der Abmeldung per Betreff: Der Eintrag wird gelöscht und
            // nicht gesperrt, damit eine spätere Anmeldung ohne Hürde möglich
            // bleibt. Eine Sperre bleibt der Betreuung vorbehalten — sonst
            // ließe sie sich über den Abmeldeknopf abstreifen.
            if (MailinglistenAbonnentModel::STATUS_GESPERRT !== $eintrag->status) {
                $eintrag->delete();
            }

            if (null !== $liste) {
                MailinglistenProtokollModel::protokollieren(
                    (int) $liste->id,
                    '',
                    $adresse,
                    '',
                    MailinglistenProtokollModel::AKTION_ABMELDUNG,
                    0,
                    'Abmeldung über den Knopf im Mailprogramm (Ein-Klick).',
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Ein-Klick-Abmeldung für "%s" fehlgeschlagen: %s', $adresse, $e->getMessage()));

            return $this->seite('Abmeldung', '<p>Die Abmeldung konnte gerade nicht gespeichert werden. Bitte versuchen Sie es später noch einmal.</p>');
        }

        return $this->seite(
            'Abmeldung',
            sprintf(
                '<p>Sie wurden von der Mailingliste <strong>%s</strong> abgemeldet und erhalten keine weiteren Nachrichten.</p>',
                htmlspecialchars((string) ($liste?->titel ?? ''), ENT_QUOTES, 'UTF-8'),
            ),
        );
    }

    /**
     * Holt die Liste zu einem Teilnehmereintrag.
     *
     * @param MailinglistenAbonnentModel $eintrag Der Teilnehmer
     *
     * @return MailinglistenModel|null Die Liste, oder null wenn sie inzwischen
     *                                 gelöscht wurde
     */
    private function listeVon(MailinglistenAbonnentModel $eintrag): ?MailinglistenModel
    {
        return MailinglistenModel::findByPk((int) $eintrag->pid);
    }

    /**
     * Baut eine schlichte, in sich geschlossene HTML-Seite.
     *
     * Bewusst ohne Contao-Template: Die Seite wird aus einem Mailprogramm
     * heraus aufgerufen, oft in einem kleinen eingebetteten Fenster ohne
     * Sitzung und ohne Seitenkontext. Ein Layout der Webseite zu laden hieße,
     * von einer Seitenstruktur abzuhängen, die es an dieser Stelle gar nicht
     * gibt — und ein fehlendes Layout würde die Abmeldung scheitern lassen.
     *
     * @param string $titel Titel und Überschrift der Seite
     * @param string $inhalt HTML des Textkörpers
     *
     * @return Response Die fertige Antwort, immer mit Status 200
     */
    private function seite(string $titel, string $inhalt): Response
    {
        $html = sprintf(
            '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<meta name="robots" content="noindex,nofollow">'
            .'<title>%1$s</title><style>'
            .'body{font:16px/1.5 system-ui,sans-serif;margin:0;padding:2rem;color:#222;background:#f5f5f5}'
            .'main{max-width:34rem;margin:0 auto;background:#fff;padding:1.5rem 2rem;border-radius:.5rem}'
            .'h1{font-size:1.3rem;margin:0 0 1rem}'
            .'button{font:inherit;padding:.6rem 1.2rem;border:0;border-radius:.3rem;background:#2b6cb0;color:#fff;cursor:pointer}'
            .'</style></head><body><main><h1>%1$s</h1>%2$s</main></body></html>',
            htmlspecialchars($titel, ENT_QUOTES, 'UTF-8'),
            $inhalt,
        );

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
