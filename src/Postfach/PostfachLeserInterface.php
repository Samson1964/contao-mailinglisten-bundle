<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

/**
 * Holt ungelesene Nachrichten aus einem Postfach und behandelt sie nach.
 *
 * Die Schnittstelle hat bewusst nur eine Methode: Verbindungsaufbau, Abruf,
 * Nachbehandlung und Trennung gehören zusammen und sollen nicht über mehrere
 * Aufrufe verteilt werden, bei denen eine offene Verbindung zwischen den
 * Aufrufen gehalten werden müsste.
 *
 * Für Tests lässt sich die Schnittstelle mit einer Attrappe belegen, die eine
 * feste Nachrichtenliste liefert — der Verteiler braucht dann weder Netz noch
 * Postfach.
 */
interface PostfachLeserInterface
{
    /**
     * Verbindet sich, geht die ungelesenen Nachrichten durch und trennt wieder.
     *
     * Der Rückruf bekommt jede Nachricht einzeln und bestimmt mit seinem
     * Rückgabewert, was danach mit ihr im Postfach geschieht. Wirft der
     * Rückruf eine Ausnahme, bleibt die betroffene Nachricht unangetastet
     * (also ungelesen) und die Ausnahme wird weitergereicht; bereits
     * behandelte Nachrichten bleiben behandelt.
     *
     * @param Postfachzugang $zugang     Vollständige Zugangsdaten. Ein
     *                                   unvollständiger Zugang führt zu einer
     *                                   PostfachFehler-Ausnahme.
     * @param int            $hoechstzahl Wie viele Nachrichten dieser Aufruf
     *                                    höchstens holt. Begrenzt die Laufzeit
     *                                    eines Cron-Durchgangs; der Rest kommt
     *                                    beim nächsten Mal.
     * @param callable       $verarbeiter Wird als $verarbeiter(EingehendeNachricht)
     *                                    aufgerufen und muss eine Nachbehandlung
     *                                    zurückgeben
     *
     * @return int Anzahl der tatsächlich verarbeiteten Nachrichten
     *
     * @throws PostfachFehler Wenn keine Verbindung zustande kommt, die
     *                        Anmeldung scheitert oder der Ordner fehlt
     */
    public function verarbeiten(Postfachzugang $zugang, int $hoechstzahl, callable $verarbeiter): int;
}
