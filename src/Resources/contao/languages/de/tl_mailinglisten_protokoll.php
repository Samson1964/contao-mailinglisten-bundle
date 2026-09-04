<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['datum'] = array('Zeitpunkt', 'Wann die Nachricht verarbeitet wurde.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['messageId'] = array('Message-ID', 'Die Kennung der Nachricht. Über sie erkennt das Bundle, dass eine Nachricht bereits behandelt wurde.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['absender'] = array('Absender', 'Die Adresse, von der die Nachricht kam.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['betreff'] = array('Betreff', 'Der Betreff der eingegangenen Nachricht.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktion'] = array('Ergebnis', 'Was mit der Nachricht geschehen ist.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['empfaenger'] = array('Empfänger', 'An wie viele Teilnehmer die Nachricht tatsächlich ging.');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['meldung'] = array('Erläuterung', 'Der Grund einer Ablehnung oder die Meldung eines Fehlers.');

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['verteilt'] = 'verteilt';
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['abgelehnt'] = 'abgelehnt';
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['antrag'] = 'Aufnahmeantrag';
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['abmeldung'] = 'Abmeldung';
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['ignoriert'] = 'verworfen';
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen']['fehler'] = 'Fehler';

// Operationen
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['delete'] = array('Löschen', 'Den Eintrag ID %s löschen');
$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['show'] = array('Details', 'Die Details des Eintrags ID %s anzeigen');
