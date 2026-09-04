<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Erweiterung der Tabelle tl_module um das Anmeldemodul.
 *
 * Die Palette wird vollständig selbst gesetzt, weil es sich um einen eigenen
 * Modultyp handelt und nicht um die Ergänzung einer Kernpalette. Bewusst nicht
 * enthalten ist `guests`: Das Feld gibt es in Contao 5 nicht mehr, und ein
 * Palettenfeld ohne Definition führt dort zu einer leeren Zeile in der Maske.
 *
 * Die Auswahl der Mailingliste braucht einen options_callback. `foreignKey`
 * allein füllt eine Auswahlliste **nicht** — es dient nur der Darstellung in
 * Übersichten und der Beziehung zwischen den Tabellen.
 */

$GLOBALS['TL_DCA']['tl_module']['palettes']['mailinglistenanmeldung'] =
	'{title_legend},name,headline,type;'
	. '{config_legend},mlListe,mlEinwilligung;'
	. '{template_legend:hide},customTpl;'
	. '{protected_legend:hide},protected;'
	. '{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['mlListe'] = array
(
	'exclude'                 => true,
	'inputType'               => 'select',
	'foreignKey'              => 'tl_mailinglisten.titel',
	'eval'                    => array('mandatory'=>true, 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default 0",
	'relation'                => array('type'=>'hasOne', 'load'=>'lazy'),
);

$GLOBALS['TL_DCA']['tl_module']['fields']['mlEinwilligung'] = array
(
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('rows'=>3, 'style'=>'height:60px', 'decodeEntities'=>true, 'allowHtml'=>true, 'tl_class'=>'clr'),
	'sql'                     => "text NULL",
);
