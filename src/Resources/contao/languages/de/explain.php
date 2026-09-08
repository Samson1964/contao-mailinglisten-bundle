<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Inhalt des Hilfe-Assistenten an den Textfeldern.
 *
 * Contao rendert dieses Feld als Tabelle. Jede Zeile ist ein Array:
 * array('headspan', '…') ergibt eine Überschrift über beide Spalten,
 * array('colspan', '…') einen Fließtext über beide Spalten, und
 * array('links', 'rechts') zwei Zellen. HTML ist erlaubt.
 *
 * Gelesen wird die Datei von Contao\BackendHelp, sobald ein Feld
 * `'explanation' => 'mlPlatzhalter'` trägt; den Verweis auf den Assistenten
 * erzeugt `'helpwizard' => true`.
 */
$GLOBALS['TL_LANG']['XPL']['mlPlatzhalter'] = array
(
	array('headspan', 'Platzhalter in den Texten'),

	array('colspan', 'In der Fußzeile, im Text der Ablehnung und im Text der Antragsbestätigung lassen sich Platzhalter verwenden. Sie werden beim Versand durch die jeweiligen Werte ersetzt — die Platzhalter selbst erscheinen also nie in einer verschickten Nachricht.'),

	array('headspan', 'Angaben der Mailingliste'),

	array('<code>##liste##</code>', 'Der Name der Liste, wie er oben unter „Name der Liste“ eingetragen ist. Beispiel: <em>Vorstand</em>'),
	array('<code>##adresse##</code>', 'Die E-Mail-Adresse der Liste. Beispiel: <em>vorstand@example.org</em>'),
	array('<code>##kennung##</code>', 'Das Kennwort für die Aufnahme, also das Wort, mit dem ein Betreff beginnen muss, damit die Nachricht als Aufnahmeantrag gilt. Beispiel: <em>Anmeldung</em>'),
	array('<code>##abmeldekennung##</code>', 'Das Kennwort für die Abmeldung. Beispiel: <em>Abmeldung</em>'),

	array('headspan', 'Angaben aus der eingegangenen Nachricht'),

	array('colspan', 'Diese vier beziehen sich auf die Nachricht, die den Versand ausgelöst hat. In der Fußzeile ist das die verteilte Nachricht, im Ablehnungstext die abgewiesene, im Bestätigungstext der Aufnahmeantrag.'),

	array('<code>##absender##</code>', 'Die E-Mail-Adresse des Absenders. Beispiel: <em>max.mustermann@example.org</em>'),
	array('<code>##absendername##</code>', 'Der angezeigte Name des Absenders, soweit sein Mailprogramm einen mitgeschickt hat. Er kann leer bleiben — dann steht an der Stelle nichts.'),
	array('<code>##betreff##</code>', 'Der Betreff der eingegangenen Nachricht, ohne das Betreffkennzeichen der Liste.'),

	array('headspan', 'Beispiel für eine Fußzeile'),

	array('colspan', '<pre style="white-space:pre-wrap">Diese Nachricht ging an alle Teilnehmer von ##liste##.
Zum Austragen eine E-Mail an ##adresse## mit dem Betreff "##abmeldekennung##".</pre>'),

	array('colspan', 'ergibt zum Beispiel:'),

	array('colspan', '<pre style="white-space:pre-wrap">Diese Nachricht ging an alle Teilnehmer von Vorstand.
Zum Austragen eine E-Mail an vorstand@example.org mit dem Betreff "Abmeldung".</pre>'),

	array('headspan', 'Der Abmeldehinweis'),

	array('colspan', 'Unter jede verteilte Nachricht setzt das Bundle <strong>selbsttätig</strong> eine Zeile, die den Abmeldeweg nennt — auch dann, wenn hier gar nichts eingetragen ist. Das ist beabsichtigt: Die Kopfzeile <code>List-Unsubscribe</code>, die jede Nachricht ebenfalls trägt, zeigt Thunderbird nur unter bestimmten Bedingungen an und die Mailprogramme der Mobiltelefone meist überhaupt nicht. Ohne sichtbaren Hinweis fände sich der Weg nirgends.'),

	array('colspan', 'Wer den Wortlaut selbst bestimmen möchte, schreibt ihn in die Fußzeile und verwendet dabei <code>##abmeldekennung##</code> oder <code>##adresse##</code>. Die Fußzeile gilt dann als selbsterklärend, und die zusätzliche Zeile entfällt.'),

	array('headspan', 'Wo die Texte erscheinen'),

	array('Fußzeile', 'Unter jeder Nachricht, die an die Teilnehmer verteilt wird. Abgetrennt durch die übliche Signaturmarke <code>--</code>.'),
	array('Text der Ablehnung', 'In der Antwort an jemanden, der an die Liste geschrieben hat, ohne dazuzugehören. Wird nur versendet, wenn „Absender über die Ablehnung unterrichten“ eingeschaltet ist.'),
	array('Text der Antragsbestätigung', 'In der Antwort an jemanden, der die Aufnahme beantragt hat. Bleibt das Feld leer, wird ein Standardtext verwendet.'),
);
