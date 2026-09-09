<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Content of the help wizard shown at the text fields.
 *
 * Contao renders this as a table. Each row is an array: array('headspan', '…')
 * gives a heading across both columns, array('colspan', '…') a paragraph across
 * both columns, and array('left', 'right') two cells. HTML is allowed.
 */
$GLOBALS['TL_LANG']['XPL']['mlPlatzhalter'] = array
(
	array('headspan', 'Placeholders in the texts'),

	array('colspan', 'The footer, the rejection text and the request confirmation text may contain placeholders. They are replaced when the message is sent — the placeholders themselves never appear in a delivered message.'),

	array('headspan', 'Details of the mailing list'),

	array('<code>##liste##</code>', 'The name of the list as entered under “List name” above. Example: <em>Board</em>'),
	array('<code>##adresse##</code>', 'The e-mail address of the list. Example: <em>board@example.org</em>'),
	array('<code>##kennung##</code>', 'The subscription keyword, i.e. the word a subject has to start with for the message to count as a request to join. Example: <em>Subscribe</em>'),
	array('<code>##abmeldekennung##</code>', 'The unsubscribe keyword. Example: <em>Unsubscribe</em>'),

	array('headspan', 'Details of the incoming message'),

	array('colspan', 'These four refer to the message that triggered the delivery: the distributed message in the footer, the rejected one in the rejection text, and the request to join in the confirmation text.'),

	array('<code>##absender##</code>', 'The e-mail address of the sender. Example: <em>john.doe@example.org</em>'),
	array('<code>##absendername##</code>', 'The display name of the sender, as far as their mail program supplied one. It may be empty — then nothing appears in its place.'),
	array('<code>##betreff##</code>', 'The subject of the incoming message, without the subject prefix of the list.'),

	array('headspan', 'Example of a footer'),

	array('colspan', '<pre style="white-space:pre-wrap">This message went to all members of ##liste##.
To unsubscribe, send an e-mail to ##adresse## with the subject "##abmeldekennung##".</pre>'),

	array('colspan', 'results in, for example:'),

	array('colspan', '<pre style="white-space:pre-wrap">This message went to all members of Board.
To unsubscribe, send an e-mail to board@example.org with the subject "Unsubscribe".</pre>'),

	array('headspan', 'Example of a rejection text'),

	array('colspan', 'It goes to someone who wrote to the list without belonging to it:'),

	array('colspan', '<pre style="white-space:pre-wrap">Your message "##betreff##" to ##liste## was not delivered.

The address ##absender## is not among the members of this list.
Would you like to join? Then send an e-mail to ##adresse## with the
subject "##kennung##". The maintainers of the list decide about
admission.</pre>'),

	array('headspan', 'Example of a request confirmation text'),

	array('colspan', 'It goes to someone who requested to join:'),

	array('colspan', '<pre style="white-space:pre-wrap">Hello ##absendername##,

your request to join ##liste## has been received.

The address ##absender## has been noted. As soon as the maintainers
have approved the request you will receive a message and from then on
get all contributions of the list.</pre>'),

	array('colspan', 'If either field is left empty, the bundle uses a default text of its own — no message ever goes out without content.'),

	array('headspan', 'Anonymous members'),

	array('colspan', 'If a member posts anonymously, <code>##absender##</code> and <code>##absendername##</code> are replaced by <code>[Anonym]</code> — in the footer as well as in the displayed sender name. The reply address then points to the list instead of the author, so that clicking “Reply” does not give them away.'),

	array('colspan', 'What the bundle <strong>cannot</strong> remove is the text of the message itself: a signature, a phone number, the footer added by the mail program. The member has to watch out for that; the confirmation sent when switching points this out.'),

	array('headspan', 'The unsubscribe notice'),

	array('colspan', 'The bundle <strong>automatically</strong> adds a line naming the way to unsubscribe below every distributed message, even when nothing is entered here. This is deliberate: the <code>List-Unsubscribe</code> header every message also carries is only shown by Thunderbird under certain conditions and mostly not at all by the mail programs on mobile phones. Without a visible notice the way could not be found anywhere.'),

	array('colspan', 'To word it yourself, write it into the footer using <code>##abmeldekennung##</code> or <code>##adresse##</code>. The footer then counts as self-explanatory and the additional line is omitted.'),

	array('headspan', 'Where the texts appear'),

	array('Footer', 'Below every message distributed to the members, separated by the usual signature marker <code>--</code>.'),
	array('Rejection text', 'In the reply to someone who wrote to the list without belonging to it. Only sent if “Tell senders about a rejection” is switched on.'),
	array('Request confirmation text', 'In the reply to someone who requested to join. Left empty, a default text is used.'),
);
