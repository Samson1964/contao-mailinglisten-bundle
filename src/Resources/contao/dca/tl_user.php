<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Rechte für einzelne Backend-Benutzer.
 *
 * Aufgebaut wie bei den Nachrichtenarchiven des Kerns: ein Feld für die
 * Auswahl der Listen, ein zweites für die Rechte zum Anlegen und Löschen. Der
 * Zugriff auf eine Liste schließt deren Teilnehmer und deren Verlauf mit ein —
 * eine feinere Aufteilung wäre bei drei zusammengehörigen Tabellen mehr
 * Verwaltung als Nutzen.
 *
 * Die Palette wird über den PaletteManipulator erweitert und nicht als
 * Zeichenkette gesetzt: Der Inhalt der Kernpalette unterscheidet sich zwischen
 * Contao 4.13 und 5, eine fest geschriebene Palette verlöre in einer der
 * beiden Fassungen Felder.
 */

PaletteManipulator::create()
	->addLegend('mailinglisten_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('mailinglisten', 'mailinglistenp'), 'mailinglisten_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('extend', 'tl_user')
	->applyToPalette('custom', 'tl_user')
;

$GLOBALS['TL_DCA']['tl_user']['fields']['mailinglisten'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_mailinglisten.titel',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('type'=>'hasMany', 'load'=>'lazy'),
);

$GLOBALS['TL_DCA']['tl_user']['fields']['mailinglistenp'] = array
(
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	// Die Beschriftungen „Neue … anlegen“ und „… löschen“ bringt Contao
	// selbst mit; ein eigener Text wäre nur eine schlechtere Übersetzung.
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL",
);
