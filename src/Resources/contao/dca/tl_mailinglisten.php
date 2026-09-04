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
 * Definition der Tabelle tl_mailinglisten.
 *
 * Eine Mailingliste bündelt drei Dinge: den Zugang zu einem Postfach, den Weg
 * für den Versand und die Regeln, nach denen eingehende Nachrichten behandelt
 * werden. Die Teilnehmer stehen in tl_mailinglisten_abonnent, der Verlauf in
 * tl_mailinglisten_protokoll.
 *
 * Die beiden Kennwortfelder enthalten in der Datenbank verschlüsselte Werte.
 * Im Formular erscheint statt des Kennworts eine Reihe Sternchen; wird sie
 * unverändert gelassen, bleibt das gespeicherte Kennwort bestehen. Ein leeres
 * Feld löscht es.
 */
$GLOBALS['TL_DCA']['tl_mailinglisten'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'               => DC_Table::class,
		'ctable'                      => array('tl_mailinglisten_abonnent', 'tl_mailinglisten_protokoll'),
		'enableVersioning'            => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
			),
		),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => DataContainer::MODE_SORTED,
			'fields'                  => array('titel'),
			'flag'                    => DataContainer::SORT_ASC,
			'panelLayout'             => 'search,limit',
		),
		'label' => array
		(
			'fields'                  => array('titel', 'adresse'),
			'format'                  => '%s <span style="color:#999">[%s]</span>',
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
			'teilnehmer' => array
			(
				'href'                => 'table=tl_mailinglisten_abonnent',
				'icon'                => 'mgroup.svg',
			),
			'protokoll' => array
			(
				'href'                => 'table=tl_mailinglisten_protokoll',
				'icon'                => 'show.svg',
			),
			'edit' => array
			(
				'href'                => 'act=edit',
				'icon'                => 'header.svg',
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
			'toggle' => array
			(
				'href'                => 'act=toggle&amp;field=published',
				'icon'                => 'visible.svg',
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
		'__selector__'                => array('imapNachbehandlung', 'ablehnungSenden'),
		'default'                     => '{titel_legend},titel,adresse,beschreibung;'
		                               . '{postfach_legend},imapHost,imapPort,imapVerschluesselung,imapBenutzer,imapKennwort,imapOrdner,imapZertifikat,imapNachbehandlung;'
		                               . '{versand_legend},smtpHost,smtpPort,smtpVerschluesselung,smtpBenutzer,smtpKennwort;'
		                               . '{verteilung_legend},betreffPraefix,antwortAn,anhaengeUebernehmen,fussnote;'
		                               . '{aufnahme_legend},aufnahmeKennung,abmeldeKennung,benachrichtigung,bestaetigungText,ablehnungSenden;'
		                               . '{lauf_legend},pruefintervall,hoechstzahl;'
		                               . '{published_legend},published',
	),

	// Subpalettes
	'subpalettes' => array
	(
		'imapNachbehandlung_verschieben' => 'imapOrdnerErledigt',
		'ablehnungSenden'                => 'ablehnungText',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL auto_increment",
		),
		'tstamp' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		),

		// --- Grunddaten ---------------------------------------------------

		'titel' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''",
		),
		'adresse' => array
		(
			'exclude'                 => true,
			'search'                  => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'beschreibung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>3, 'style'=>'height:60px', 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),

		// --- Postfach (IMAP) ----------------------------------------------

		'imapHost' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'imapPort' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 993,
			'eval'                    => array('rgxp'=>'natural', 'maxlength'=>5, 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 993",
		),
		'imapVerschluesselung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'default'                 => 'ssl',
			'options'                 => array('ssl', 'tls', 'keine'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(8) NOT NULL default 'ssl'",
		),
		'imapBenutzer' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'imapKennwort' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			// hideInput macht daraus ein Kennwortfeld. Der angezeigte Wert ist
			// nicht das Kennwort, sondern der Platzhalter aus dem
			// load_callback — das echte Kennwort verlässt den Server nie.
			'eval'                    => array('hideInput'=>true, 'preserveTags'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(512) NOT NULL default ''",
		),
		'imapOrdner' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 'INBOX',
			'eval'                    => array('maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default 'INBOX'",
		),
		'imapZertifikat' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'                 => '1',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default '1'",
		),
		'imapNachbehandlung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'default'                 => 'gelesen',
			'options'                 => array('gelesen', 'verschieben', 'loeschen'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung'],
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(16) NOT NULL default 'gelesen'",
		),
		'imapOrdnerErledigt' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('mandatory'=>true, 'maxlength'=>128, 'tl_class'=>'w50'),
			'sql'                     => "varchar(128) NOT NULL default ''",
		),

		// --- Versand (SMTP) -----------------------------------------------

		'smtpHost' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'smtpPort' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 587,
			'eval'                    => array('rgxp'=>'natural', 'maxlength'=>5, 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 587",
		),
		'smtpVerschluesselung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'default'                 => 'tls',
			'options'                 => array('ssl', 'tls', 'keine'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(8) NOT NULL default 'tls'",
		),
		'smtpBenutzer' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'smtpKennwort' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('hideInput'=>true, 'preserveTags'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                     => "varchar(512) NOT NULL default ''",
		),

		// --- Verteilung ---------------------------------------------------

		'betreffPraefix' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''",
		),
		'antwortAn' => array
		(
			'exclude'                 => true,
			'inputType'               => 'select',
			'default'                 => 'liste',
			'options'                 => array('liste', 'absender'),
			'reference'               => &$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortZiel'],
			'eval'                    => array('tl_class'=>'w50'),
			'sql'                     => "varchar(16) NOT NULL default 'liste'",
		),
		'anhaengeUebernehmen' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'                 => '1',
			'eval'                    => array('tl_class'=>'w50 m12'),
			'sql'                     => "char(1) NOT NULL default '1'",
		),
		'fussnote' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>4, 'style'=>'height:80px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),

		// --- Aufnahme und Ablehnung ---------------------------------------

		'aufnahmeKennung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 'Anmeldung',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''",
		),
		'abmeldeKennung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 'Abmeldung',
			'eval'                    => array('maxlength'=>64, 'tl_class'=>'w50'),
			'sql'                     => "varchar(64) NOT NULL default ''",
		),
		'benachrichtigung' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('rgxp'=>'emails', 'maxlength'=>255, 'tl_class'=>'w50 clr'),
			'sql'                     => "varchar(255) NOT NULL default ''",
		),
		'bestaetigungText' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>5, 'style'=>'height:100px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),
		'ablehnungSenden' => array
		(
			'exclude'                 => true,
			'inputType'               => 'checkbox',
			'default'                 => '1',
			'eval'                    => array('submitOnChange'=>true, 'tl_class'=>'clr'),
			'sql'                     => "char(1) NOT NULL default '1'",
		),
		'ablehnungText' => array
		(
			'exclude'                 => true,
			'inputType'               => 'textarea',
			'eval'                    => array('rows'=>5, 'style'=>'height:100px', 'decodeEntities'=>true, 'tl_class'=>'clr'),
			'sql'                     => "text NULL",
		),

		// --- Lauf ---------------------------------------------------------

		'pruefintervall' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 5,
			'eval'                    => array('rgxp'=>'natural', 'maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 5",
		),
		'hoechstzahl' => array
		(
			'exclude'                 => true,
			'inputType'               => 'text',
			'default'                 => 25,
			'eval'                    => array('rgxp'=>'natural', 'maxlength'=>4, 'tl_class'=>'w50'),
			'sql'                     => "smallint(5) unsigned NOT NULL default 25",
		),
		'letztePruefung' => array
		(
			'sql'                     => "int(10) unsigned NOT NULL default 0",
		),

		// --- Veröffentlichung ---------------------------------------------

		'published' => array
		(
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'checkbox',
			'toggle'                  => true,
			'eval'                    => array('doNotCopy'=>true),
			'sql'                     => "char(1) NOT NULL default ''",
		),
	),
);
