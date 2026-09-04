<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteAbonnentModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteModel;
use Schachbulle\ContaoMailinglistenBundle\Model\MailinglisteProtokollModel;

/*
 * Diese Datei wird von Contao 4.13 und Contao 5.7 gleichermaßen gelesen.
 *
 * Der früher übliche Kopf `if (!defined('TL_ROOT')) die(...)` fehlt bewusst:
 * Die Konstante gibt es in Contao 5 nicht mehr, und die Zeile würde den Aufruf
 * dort kommentarlos beenden — die Backend-Module und Models wären ohne jede
 * Fehlermeldung verschwunden.
 */

/*
 * Backend-Module.
 *
 * Alle drei Tabellen hängen an einem Modul. Der Sprung von der Liste zu ihren
 * Teilnehmern und ihrem Verlauf geschieht über die Operationen in der
 * Übersicht (`table=...`), nicht über eigene Menüeinträge — sonst müsste man
 * die Liste zweimal auswählen.
 */
$GLOBALS['BE_MOD']['content']['mailingliste'] = array
(
	'tables' => array('tl_mailingliste', 'tl_mailingliste_abonnent', 'tl_mailingliste_protokoll'),
);

/*
 * Models.
 *
 * Ohne diese Zuordnung liefert `Model::getClassFromTable()` keine Klasse, und
 * die Beziehungen zwischen den Tabellen (`relation`) blieben unaufgelöst.
 */
$GLOBALS['TL_MODELS']['tl_mailingliste'] = MailinglisteModel::class;
$GLOBALS['TL_MODELS']['tl_mailingliste_abonnent'] = MailinglisteAbonnentModel::class;
$GLOBALS['TL_MODELS']['tl_mailingliste_protokoll'] = MailinglisteProtokollModel::class;
