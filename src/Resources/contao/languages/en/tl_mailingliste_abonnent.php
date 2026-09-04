<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Fields
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['email'] = array('E-mail address', 'Always stored in lower case and allowed only once per list.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['vorname'] = array('First name', 'Appears in the recipient name of the distributed messages.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['nachname'] = array('Last name', 'Appears in the recipient name of the distributed messages.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['status'] = array('Status', 'Only "active" takes part. "Requested" waits for approval, "blocked" stays excluded even after another request.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['darfSenden'] = array('May write to the list', 'Without this right messages from this address are rejected — the member only reads along.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['darfEmpfangen'] = array('Receives the list messages', 'Without this right the member may submit but receives nothing.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['notiz'] = array('Note', 'For your own overview. On a request by e-mail the bundle records date and subject here.');

// Legends
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['person_legend'] = 'Person';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['status_legend'] = 'Status and rights';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['notiz_legend'] = 'Note';

// Options
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['aktiv'] = 'active';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['beantragt'] = 'membership requested';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['gesperrt'] = 'blocked';

// Operations
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['edit'] = array('Edit', 'Edit member ID %s');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['copy'] = array('Duplicate', 'Duplicate member ID %s');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['delete'] = array('Delete', 'Delete member ID %s');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['show'] = array('Details', 'Show the details of member ID %s');

// Messages
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['dublette'] = 'The address "%s" is already part of this mailing list.';
