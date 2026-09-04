<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['email'] = array('E-Mail-Adresse', 'Wird immer kleingeschrieben gespeichert und darf in dieser Liste nur einmal vorkommen.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['vorname'] = array('Vorname', 'Erscheint im Empfängernamen der verteilten Nachrichten.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['nachname'] = array('Nachname', 'Erscheint im Empfängernamen der verteilten Nachrichten.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['status'] = array('Status', 'Nur "aktiv" nimmt am Verkehr teil. "Beantragt" wartet auf die Freigabe, "gesperrt" ist ausgeschlossen und bleibt es auch bei einem erneuten Antrag.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['darfSenden'] = array('Darf an die Liste schreiben', 'Ohne dieses Recht werden Nachrichten dieser Adresse abgewiesen — der Teilnehmer liest also nur mit.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['darfEmpfangen'] = array('Erhält die Nachrichten der Liste', 'Ohne dieses Recht darf der Teilnehmer einreichen, bekommt aber nichts zugestellt.');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['notiz'] = array('Notiz', 'Nur für die eigene Übersicht. Bei einem Antrag per E-Mail trägt das Bundle hier Datum und Betreff ein.');

// Legenden
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['person_legend'] = 'Person';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['status_legend'] = 'Status und Rechte';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['notiz_legend'] = 'Notiz';

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['aktiv'] = 'aktiv';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['beantragt'] = 'Aufnahme beantragt';
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['statusWerte']['gesperrt'] = 'gesperrt';

// Operationen
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['edit'] = array('Bearbeiten', 'Den Teilnehmer ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['copy'] = array('Duplizieren', 'Den Teilnehmer ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['delete'] = array('Löschen', 'Den Teilnehmer ID %s löschen');
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['show'] = array('Details', 'Die Details des Teilnehmers ID %s anzeigen');

// Meldungen
$GLOBALS['TL_LANG']['tl_mailingliste_abonnent']['dublette'] = 'Die Adresse "%s" ist in dieser Mailingliste bereits eingetragen.';
