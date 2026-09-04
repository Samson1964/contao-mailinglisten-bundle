<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Fields
$GLOBALS['TL_LANG']['tl_mailinglisten']['titel'] = array('List name', 'Appears in the subject line and in the displayed sender name.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['adresse'] = array('List address', 'The e-mail address people write to and the list sends from. It has to belong to the mailbox configured below.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['beschreibung'] = array('Description', 'For your own overview in the back end only; it is never sent anywhere.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['imapHost'] = array('IMAP server', 'Host name without protocol, e.g. "imap.example.org".');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapPort'] = array('IMAP port', 'Usually 993 with SSL and 143 with STARTTLS.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapVerschluesselung'] = array('IMAP encryption', 'SSL encrypts from the first byte (port 993), STARTTLS upgrades an open connection (port 143).');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapBenutzer'] = array('IMAP user name', 'With most providers this is the full e-mail address.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapKennwort'] = array('IMAP password', 'Stored encrypted. The asterisks stand for the saved password — leave them untouched and nothing changes. An empty field deletes the password.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapOrdner'] = array('IMAP folder', 'The folder holding the incoming messages, usually "INBOX".');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapZertifikat'] = array('Validate certificate', 'Turn off only on a test setup using a self-signed certificate.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapNachbehandlung'] = array('After processing', 'What to do with a processed message in the mailbox.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapOrdnerErledigt'] = array('Target folder', 'Where processed messages are moved to. The folder has to exist on the server already.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpHost'] = array('SMTP server', 'If left empty, the list sends through the general Contao mailer. The sender address then often does not match the sending server, which makes messages more likely to be treated as spam.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpPort'] = array('SMTP port', 'Usually 587 with STARTTLS and 465 with SSL.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpVerschluesselung'] = array('SMTP encryption', 'SSL encrypts from the first byte (port 465), STARTTLS upgrades an open connection (port 587).');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpBenutzer'] = array('SMTP user name', 'If left empty, mail is sent without authentication.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpKennwort'] = array('SMTP password', 'Stored encrypted. The asterisks stand for the saved password — leave them untouched and nothing changes.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['betreffPraefix'] = array('Subject prefix', 'Prepended to every distributed subject, e.g. "[Board]". An existing prefix is not added a second time.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortAn'] = array('Replies go to', '"To the list" turns the distributor into a discussion group, "to the sender" makes it a newsletter with private replies.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['anhaengeUebernehmen'] = array('Pass on attachments', 'Without this only the text is distributed. That spares the recipients\' mailboxes but loses files.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['fussnote'] = array('Footer', 'Added below every distributed message. Available placeholders: ##liste##, ##adresse##, ##kennung##, ##abmeldekennung##, ##absender##, ##absendername##, ##betreff##.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['aufnahmeKennung'] = array('Subscription keyword', 'If a stranger\'s subject starts with this word, the message counts as a request to join. An empty field disables the feature.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['abmeldeKennung'] = array('Unsubscribe keyword', 'If the subject starts with this word, the sender removes themselves from the list. An empty field disables the feature.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['benachrichtigung'] = array('Notify', 'These addresses are told about a new request to join. Separate multiple addresses with commas.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['ablehnungMelden'] = array('Report rejections to the maintainers', 'The addresses above are told about every rejected message, including sender, subject, reason and the beginning of the text. Better turned off for an address that receives a lot of spam.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['bestaetigungText'] = array('Request confirmation text', 'Sent to the applicant. Left empty, a default text is used. Placeholders as for the footer.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['ablehnungSenden'] = array('Tell senders about a rejection', 'Better turned off for an address that receives a lot of spam: every rejection sent to a forged sender address bothers an uninvolved third party.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['ablehnungText'] = array('Rejection text', 'Left empty, a default text is used. Placeholders as for the footer.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['pruefintervall'] = array('Check interval in minutes', 'How often the mailbox is polled. 0 means on every cron run.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['hoechstzahl'] = array('Messages per run', 'Limits the duration of a single run. Whatever is left over is handled next time.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['published'] = array('List active', 'Only active lists are polled by the cron job. The mailbox of a disabled list is left untouched.');

// Legends
$GLOBALS['TL_LANG']['tl_mailinglisten']['titel_legend'] = 'Basic data';
$GLOBALS['TL_LANG']['tl_mailinglisten']['postfach_legend'] = 'Mailbox (receiving via IMAP)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['versand_legend'] = 'Sending (SMTP)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verteilung_legend'] = 'Distribution';
$GLOBALS['TL_LANG']['tl_mailinglisten']['aufnahme_legend'] = 'Joining, leaving and rejection';
$GLOBALS['TL_LANG']['tl_mailinglisten']['lauf_legend'] = 'Scheduled run';
$GLOBALS['TL_LANG']['tl_mailinglisten']['published_legend'] = 'Publish';

// Options
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['ssl'] = 'SSL/TLS (from the first byte)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['tls'] = 'STARTTLS (upgraded)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['keine'] = 'No encryption';

$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['gelesen'] = 'Mark as read';
$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['verschieben'] = 'Move to another folder';
$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['loeschen'] = 'Delete';

$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortZiel']['liste'] = 'to the list';
$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortZiel']['absender'] = 'to the sender';

// Operations
$GLOBALS['TL_LANG']['tl_mailinglisten']['teilnehmer'] = array('Members', 'Edit the members of mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['protokoll'] = array('History', 'Show the history of mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['edit'] = array('Edit', 'Edit mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['copy'] = array('Duplicate', 'Duplicate mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['delete'] = array('Delete', 'Delete mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['toggle'] = array('Publish/unpublish', 'Publish or unpublish mailing list ID %s');
$GLOBALS['TL_LANG']['tl_mailinglisten']['show'] = array('Details', 'Show the details of mailing list ID %s');

// Messages
$GLOBALS['TL_LANG']['tl_mailinglisten']['fehltSodium'] = 'The PHP extension "sodium" is missing. Mailbox passwords cannot be encrypted.';
$GLOBALS['TL_LANG']['tl_mailinglisten']['fehltImap'] = 'The package "webklex/php-imap" is not installed. No messages will be fetched.';
