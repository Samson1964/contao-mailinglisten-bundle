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

	array('headspan', 'Beispiel für den Text der Ablehnung'),

	array('colspan', 'Er geht an jemanden, der an die Liste geschrieben hat, ohne dazuzugehören:'),

	array('colspan', '<pre style="white-space:pre-wrap">Ihre Nachricht "##betreff##" an ##liste## wurde nicht zugestellt.

Die Adresse ##absender## gehört nicht zu den Teilnehmern dieser Liste.
Möchten Sie aufgenommen werden? Dann senden Sie eine E-Mail an
##adresse## mit dem Betreff "##kennung##". Über die Aufnahme
entscheidet die Betreuung der Liste.</pre>'),

	array('headspan', 'Beispiel für den Text der Antragsbestätigung'),

	array('colspan', 'Er geht an jemanden, der die Aufnahme beantragt hat:'),

	array('colspan', '<pre style="white-space:pre-wrap">Guten Tag ##absendername##,

Ihr Antrag auf Aufnahme in ##liste## ist eingegangen.

Die Adresse ##absender## wurde vorgemerkt. Sobald die Betreuung
den Antrag freigegeben hat, erhalten Sie eine Nachricht und
bekommen ab dann alle Beiträge der Liste.</pre>'),

	array('colspan', 'Bleibt eines der beiden Felder leer, verwendet das Bundle einen eigenen Standardtext — es geht also nie eine Nachricht ohne Inhalt hinaus.'),

	array('headspan', 'Anonyme Teilnehmer'),

	array('colspan', 'Schreibt ein Teilnehmer anonym, werden <code>##absender##</code> und <code>##absendername##</code> durch <code>[Anonym]</code> ersetzt — in der Fußzeile ebenso wie im angezeigten Absendernamen. Auch die Antwortadresse zeigt dann auf die Liste statt auf den Verfasser, damit ein Klick auf „Antworten“ ihn nicht preisgibt.'),

	array('colspan', 'Was das Bundle <strong>nicht</strong> entfernen kann, ist der Text der Nachricht selbst: eine Unterschrift, eine Telefonnummer, die Signatur des Mailprogramms. Darauf muss der Teilnehmer selbst achten; die Bestätigung beim Umschalten weist ihn darauf hin.'),

	array('headspan', 'Der Abmeldehinweis'),

	array('colspan', 'Unter jede verteilte Nachricht setzt das Bundle <strong>selbsttätig</strong> eine Zeile, die den Abmeldeweg nennt — auch dann, wenn hier gar nichts eingetragen ist. Das ist beabsichtigt: Die Kopfzeile <code>List-Unsubscribe</code>, die jede Nachricht ebenfalls trägt, zeigt Thunderbird nur unter bestimmten Bedingungen an und die Mailprogramme der Mobiltelefone meist überhaupt nicht. Ohne sichtbaren Hinweis fände sich der Weg nirgends.'),

	array('colspan', 'Wer den Wortlaut selbst bestimmen möchte, schreibt ihn in die Fußzeile und verwendet dabei <code>##abmeldekennung##</code> oder <code>##adresse##</code>. Die Fußzeile gilt dann als selbsterklärend, und die zusätzliche Zeile entfällt.'),

	array('headspan', 'Wo die Texte erscheinen'),

	array('Fußzeile', 'Unter jeder Nachricht, die an die Teilnehmer verteilt wird. Abgetrennt durch die übliche Signaturmarke <code>--</code>.'),
	array('Text der Ablehnung', 'In der Antwort an jemanden, der an die Liste geschrieben hat, ohne dazuzugehören. Wird nur versendet, wenn „Absender über die Ablehnung unterrichten“ eingeschaltet ist.'),
	array('Text der Antragsbestätigung', 'In der Antwort an jemanden, der die Aufnahme beantragt hat. Bleibt das Feld leer, wird ein Standardtext verwendet.'),
);
