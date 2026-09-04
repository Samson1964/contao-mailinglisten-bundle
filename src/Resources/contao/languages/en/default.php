<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Texts of the front end subscription form.
 *
 * The closing message is deliberately vague ("if a subscription is possible"):
 * it appears unchanged whether the address is new, already on the list or
 * blocked. Otherwise the form could be used to find out who is a member.
 */
$GLOBALS['TL_LANG']['MSC']['mlAbschluss'] = 'Thank you. If a subscription is possible, we have sent a message to %s. Please follow the instructions in it.';
$GLOBALS['TL_LANG']['MSC']['mlBestaetigt'] = 'Thank you. Your subscription is confirmed and now awaits approval by the maintainers of the list. You will hear from us once it has been decided.';
$GLOBALS['TL_LANG']['MSC']['mlLinkUngueltig'] = 'This confirmation link is invalid or has expired. Please subscribe again.';
$GLOBALS['TL_LANG']['MSC']['mlEmailUngueltig'] = 'Please enter a valid e-mail address.';
$GLOBALS['TL_LANG']['MSC']['mlEinwilligungFehlt'] = 'Please confirm the privacy notice.';
$GLOBALS['TL_LANG']['MSC']['mlFehler'] = 'The subscription could not be saved. Please try again later.';
$GLOBALS['TL_LANG']['MSC']['mlKeineListe'] = 'No active mailing list has been assigned to this module.';
$GLOBALS['TL_LANG']['MSC']['mlFeldEmail'] = 'E-mail address';
$GLOBALS['TL_LANG']['MSC']['mlFeldVorname'] = 'First name';
$GLOBALS['TL_LANG']['MSC']['mlFeldNachname'] = 'Last name';
$GLOBALS['TL_LANG']['MSC']['mlAbsenden'] = 'Subscribe';
$GLOBALS['TL_LANG']['MSC']['mlHonigtopf'] = 'Please leave this field empty';
