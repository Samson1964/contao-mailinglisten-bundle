<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

/*
 * Rechte für Benutzergruppen.
 *
 * Inhaltlich dasselbe wie in tl_user, nur für die Gruppe. Contao führt beides
 * zusammen: Ein Benutzer bekommt die Listen seiner Gruppen **und** die ihm
 * einzeln zugewiesenen. Die Beschriftungen stammen aus tl_user, damit sie nur
 * an einer Stelle gepflegt werden.
 */

PaletteManipulator::create()
	->addLegend('mailinglisten_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
	->addField(array('mailinglisten', 'mailinglistenp'), 'mailinglisten_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('default', 'tl_user_group')
;

$GLOBALS['TL_DCA']['tl_user_group']['fields']['mailinglisten'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['mailinglisten'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'foreignKey'              => 'tl_mailinglisten.titel',
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL",
	'relation'                => array('type'=>'hasMany', 'load'=>'lazy'),
);

$GLOBALS['TL_DCA']['tl_user_group']['fields']['mailinglistenp'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_user']['mailinglistenp'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'options'                 => array('create', 'delete'),
	'reference'               => &$GLOBALS['TL_LANG']['MSC'],
	'eval'                    => array('multiple'=>true),
	'sql'                     => "blob NULL",
);
