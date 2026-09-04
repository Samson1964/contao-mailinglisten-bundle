<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Fields
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['datum'] = array('Time', 'When the message was processed.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['messageId'] = array('Message ID', 'The identifier of the message. The bundle uses it to recognise a message it has already handled.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['absender'] = array('Sender', 'The address the message came from.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['betreff'] = array('Subject', 'The subject of the incoming message.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktion'] = array('Result', 'What happened to the message.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['empfaenger'] = array('Recipients', 'How many members actually received the message.');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['meldung'] = array('Explanation', 'The reason for a rejection or the message of an error.');

// Options
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['verteilt'] = 'distributed';
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['abgelehnt'] = 'rejected';
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['antrag'] = 'membership request';
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['abmeldung'] = 'unsubscribed';
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['ignoriert'] = 'discarded';
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['aktionen']['fehler'] = 'error';

// Operations
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['delete'] = array('Delete', 'Delete entry ID %s');
$GLOBALS['TL_LANG']['tl_mailingliste_protokoll']['show'] = array('Details', 'Show the details of entry ID %s');
