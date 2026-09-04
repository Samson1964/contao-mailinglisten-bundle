<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Definition der Tabelle tl_mailinglisten_abonnent.
 *
 * Die Teilnehmer einer Mailingliste. Der Status entscheidet über alles
 * Weitere: Nur „aktiv" nimmt am Verkehr teil, „beantragt" wartet auf die
 * Freigabe durch die Betreuung, „gesperrt" ist ausgeschlossen und bleibt es
 * auch bei einem erneuten Antrag.
 *
 * Die beiden Rechte sind unabhängig voneinander. Damit lässt sich ein
 * Nur-Leser einrichten (empfangen ja, senden nein) ebenso wie ein reiner
 * Einreicher, etwa ein Formular, das Meldungen an die Liste schickt, ohne die
 * Antworten zu bekommen.
 */
$GLOBALS['TL_DCA']['tl_mailinglisten_abonnent'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_mailinglisten',
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'          => 'primary',
				'pid'         => 'index',
				'pid,email'   => 'index',
				'pid,status'  => 'index',
			),
		),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('email'),
			'flag'                    => DataContainer::SORT_ASC,
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('titel', 'adresse', 'beschreibung'),
			'child_record_class'      => 'no_padding',
			// Keine Zwischenüberschrift je Anfangsbuchstabe der Adresse — bei
			// einer Teilnehmerliste steht der Name im Vordergrund, nicht das
			// Alphabet. Wirkt in 4.13 wie in 5.7.
			'disableGrouping'         => true,
		),
		'global_operations' => array
		(
			'all' => array
			(
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"',
			),
		),
		'operations' => array
		(
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'edit.svg',
			),
			'copy' => array
			(
				'href'                => 'act=copy',
				'icon'                => 'copy.svg',
			),
			'delete' => array
			(
				'href'                => 'act=delete',
				'icon'                => 'delete.svg',
				'attributes'          => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"',
			),
			'show' => array
			(
				'href'                => 'act=show',
				'icon'                => 'show.svg',
			),
		),
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{person_legend},email,vorname,nachname;'
		                               . '{status_legend},status,darfSenden,darfEmpfangen;'
		                               . '{notiz_legend},notiz',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment",
		),
		'pid' => array
		(
			'foreignKey'              => 'tl_mailinglisten.titel',
			'sql'                     => "int(10) unsigned NOT NULL default 0",
			'relation'                => array('type'=>'belongsTo', 'load'=>'lazy'),
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		),
		'email' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'tl_class'=>'long'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'vorname' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(128) NOT NULL default ''",
		),
		'nachname' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''",
		),
		'status' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'default'                 => 'aktiv',
			'options'                 => array('aktiv', 'beantragt', 'gesperrt'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten_abonnent']['statusWerte'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(16) NOT NULL default 'aktiv'",
		),
		'darfSenden' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'default'                 => '1',
			'eval'                    => array('tl_class'=>'w50 clr m12'),
			'sql'                     => "char(1) NOT NULL default '1'",
		),
		'darfEmpfangen' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'default'                 => '1',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default '1'",
		),
		'beigetreten' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		),
		'notiz' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>3, 'style'=>'height:60px', 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),
	),
);
