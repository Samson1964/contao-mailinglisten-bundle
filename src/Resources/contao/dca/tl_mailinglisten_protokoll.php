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
 * Definition der Tabelle tl_mailinglisten_protokoll.
 *
 * Der Verlauf einer Mailingliste. Die Einträge entstehen ausschließlich beim
 * Cron-Lauf; im Backend lassen sie sich ansehen und löschen, aber nicht
 * anlegen oder ändern — ein nachträglich veränderbares Protokoll wäre keines.
 *
 * Neben der Nachvollziehbarkeit hat die Tabelle eine zweite, technische
 * Aufgabe: Die gespeicherte Message-ID verhindert, dass eine Nachricht ein
 * zweites Mal verteilt wird, falls ein Cron-Lauf zwischen Versand und
 * Lesezeichen abbricht. Wer hier aufräumt, sollte deshalb nur alte Einträge
 * löschen, nicht die der letzten Tage.
 */
$GLOBALS['TL_DCA']['tl_mailinglisten_protokoll'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ptable'                      => 'tl_mailinglisten',
		'closed'                      => true,
		'notEditable'                 => true,
		'notCopyable'                 => true,
		'sql' => array
		(
			'keys' => array
			(
				'id'             => 'primary',
				'pid'            => 'index',
				'pid,messageId'  => 'index',
				'datum'          => 'index',
			),
		),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_PARENT,
			'fields'                  => array('datum DESC'),
			'flag'                    => DataContainer::SORT_DESC,
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('titel', 'adresse'),
			'child_record_class'      => 'no_padding',
		),
		'operations' => array
		(
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
		'datum' => array
		(
			'sorting'                 => true,
			'flag'                    => DataContainer::SORT_DESC,
			'eval'                    => array('rgxp'=>'datim'),
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		),
		'messageId' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'absender' => array
		(
			'search'                  => true,
			'filter'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'betreff' => array
		(
			'search'                  => true,
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'aktion' => array
		(
			'filter'                  => true,
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten_protokoll']['aktionen'],
			'sql'                     => "varchar(16) NOT NULL default ''",
		),
		'empfaenger' => array
		(
			'sql'                     => "smallint(5) unsigned NOT NULL default 0",
		),
		'meldung' => array
		(
			'sql'                     => "text NULL",
		),
	),
);
