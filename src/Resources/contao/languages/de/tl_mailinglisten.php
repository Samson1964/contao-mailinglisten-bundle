<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_mailinglisten']['titel'] = array('Name der Liste', 'Erscheint im Betreff und im angezeigten Absendernamen, zum Beispiel "Vorstand".');
$GLOBALS['TL_LANG']['tl_mailinglisten']['adresse'] = array('Adresse der Liste', 'Die E-Mail-Adresse, an die geschrieben wird und unter der die Liste versendet. Sie muss zum unten angegebenen Postfach gehören.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['beschreibung'] = array('Beschreibung', 'Nur für die eigene Übersicht im Backend; sie wird nirgends versendet.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['imapHost'] = array('IMAP-Server', 'Rechnername ohne Protokoll, zum Beispiel "imap.example.org".');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapPort'] = array('IMAP-Port', 'Üblich sind 993 mit SSL und 143 mit STARTTLS.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapVerschluesselung'] = array('IMAP-Verschlüsselung', 'SSL verschlüsselt ab dem ersten Byte (Port 993), STARTTLS schaltet nachträglich um (Port 143).');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapBenutzer'] = array('IMAP-Benutzername', 'Bei den meisten Anbietern die vollständige E-Mail-Adresse.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapKennwort'] = array('IMAP-Kennwort', 'Wird verschlüsselt gespeichert. Die Sternchen stehen für das hinterlegte Kennwort — bleiben sie stehen, ändert sich nichts. Ein leeres Feld löscht das Kennwort.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapOrdner'] = array('IMAP-Ordner', 'Der Ordner, in dem die eingehenden Nachrichten liegen. In aller Regel "INBOX".');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapZertifikat'] = array('Zertifikat prüfen', 'Abschalten nur bei einem Testaufbau mit selbstsigniertem Zertifikat.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapNachbehandlung'] = array('Nach der Verarbeitung', 'Was mit einer abgearbeiteten Nachricht im Postfach geschehen soll.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['imapOrdnerErledigt'] = array('Zielordner', 'Der Ordner, in den verarbeitete Nachrichten verschoben werden. Er muss auf dem Server bereits vorhanden sein.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpHost'] = array('SMTP-Server', 'Bleibt das Feld leer, versendet die Liste über den allgemeinen Mailer der Contao-Installation. Dann stimmt die Absenderadresse oft nicht mit dem versendenden Server überein, und die Nachrichten landen häufiger im Spam.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpPort'] = array('SMTP-Port', 'Üblich sind 587 mit STARTTLS und 465 mit SSL.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpVerschluesselung'] = array('SMTP-Verschlüsselung', 'SSL verschlüsselt ab dem ersten Byte (Port 465), STARTTLS schaltet nachträglich um (Port 587).');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpBenutzer'] = array('SMTP-Benutzername', 'Bleibt das Feld leer, wird ohne Anmeldung versendet.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['smtpKennwort'] = array('SMTP-Kennwort', 'Wird verschlüsselt gespeichert. Die Sternchen stehen für das hinterlegte Kennwort — bleiben sie stehen, ändert sich nichts.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['betreffPraefix'] = array('Betreffkennzeichen', 'Wird jedem verteilten Betreff vorangestellt, zum Beispiel "[Vorstand]". Ein bereits vorhandenes Kennzeichen wird nicht ein zweites Mal angehängt.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortAn'] = array('Antworten gehen an', 'Bei "an die Liste" wird aus dem Verteiler ein Gesprächskreis, bei "an den Absender" ein Rundschreiben mit persönlichen Rückfragen.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['anhaengeUebernehmen'] = array('Anhänge weitergeben', 'Ohne diese Einstellung wird nur der Text verteilt. Das schont das Postfach der Empfänger, verliert aber Dateien.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['fussnote'] = array('Fußzeile', 'Wird unter jede verteilte Nachricht gesetzt. Verfügbare Platzhalter: ##liste##, ##adresse##, ##kennung##, ##abmeldekennung##, ##absender##, ##absendername##, ##betreff##.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['aufnahmeKennung'] = array('Kennwort für die Aufnahme', 'Steht dieses Wort am Anfang des Betreffs, gilt die Nachricht eines Fremden als Aufnahmeantrag. Ein leeres Feld schaltet die Funktion ab.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['abmeldeKennung'] = array('Kennwort für die Abmeldung', 'Steht dieses Wort am Anfang des Betreffs, trägt sich der Absender selbst aus. Ein leeres Feld schaltet die Funktion ab.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['benachrichtigung'] = array('Benachrichtigung an', 'Diese Adressen erfahren von einem neuen Aufnahmeantrag. Mehrere durch Komma trennen.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['bestaetigungText'] = array('Text der Antragsbestätigung', 'Geht an den Antragsteller. Bleibt das Feld leer, wird ein Standardtext verwendet. Platzhalter wie bei der Fußzeile.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['ablehnungSenden'] = array('Absender über die Ablehnung unterrichten', 'Bei einer Adresse, die viel Spam bekommt, besser abschalten: Jede Ablehnung an eine gefälschte Absenderadresse belästigt einen Unbeteiligten.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['ablehnungText'] = array('Text der Ablehnung', 'Bleibt das Feld leer, wird ein Standardtext verwendet. Platzhalter wie bei der Fußzeile.');

$GLOBALS['TL_LANG']['tl_mailinglisten']['pruefintervall'] = array('Prüfintervall in Minuten', 'Wie oft das Postfach abgefragt wird. 0 heißt: bei jedem Cron-Durchgang.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['hoechstzahl'] = array('Nachrichten je Durchgang', 'Begrenzt die Laufzeit eines Durchgangs. Was übrig bleibt, kommt beim nächsten Mal an die Reihe.');
$GLOBALS['TL_LANG']['tl_mailinglisten']['published'] = array('Liste aktiv', 'Nur aktive Listen werden vom Cronjob abgefragt. Das Postfach einer abgeschalteten Liste bleibt unangetastet.');

// Legenden
$GLOBALS['TL_LANG']['tl_mailinglisten']['titel_legend'] = 'Grunddaten';
$GLOBALS['TL_LANG']['tl_mailinglisten']['postfach_legend'] = 'Postfach (Empfang per IMAP)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['versand_legend'] = 'Versand (SMTP)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verteilung_legend'] = 'Verteilung';
$GLOBALS['TL_LANG']['tl_mailinglisten']['aufnahme_legend'] = 'Aufnahme, Abmeldung und Ablehnung';
$GLOBALS['TL_LANG']['tl_mailinglisten']['lauf_legend'] = 'Regelmäßiger Lauf';
$GLOBALS['TL_LANG']['tl_mailinglisten']['published_legend'] = 'Veröffentlichung';

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['ssl'] = 'SSL/TLS (ab dem ersten Byte)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['tls'] = 'STARTTLS (nachträglich)';
$GLOBALS['TL_LANG']['tl_mailinglisten']['verschluesselung']['keine'] = 'Keine Verschlüsselung';

$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['gelesen'] = 'Als gelesen markieren';
$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['verschieben'] = 'In einen anderen Ordner verschieben';
$GLOBALS['TL_LANG']['tl_mailinglisten']['nachbehandlung']['loeschen'] = 'Löschen';

$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortZiel']['liste'] = 'an die Liste';
$GLOBALS['TL_LANG']['tl_mailinglisten']['antwortZiel']['absender'] = 'an den Absender';

// Operationen
$GLOBALS['TL_LANG']['tl_mailinglisten']['teilnehmer'] = array('Teilnehmer', 'Die Teilnehmer der Mailingliste ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_mailinglisten']['protokoll'] = array('Verlauf', 'Den Verlauf der Mailingliste ID %s ansehen');
$GLOBALS['TL_LANG']['tl_mailinglisten']['edit'] = array('Bearbeiten', 'Die Mailingliste ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_mailinglisten']['copy'] = array('Duplizieren', 'Die Mailingliste ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_mailinglisten']['delete'] = array('Löschen', 'Die Mailingliste ID %s löschen');
$GLOBALS['TL_LANG']['tl_mailinglisten']['toggle'] = array('Aktivieren/deaktivieren', 'Die Mailingliste ID %s aktivieren oder deaktivieren');
$GLOBALS['TL_LANG']['tl_mailinglisten']['show'] = array('Details', 'Die Details der Mailingliste ID %s anzeigen');

// Meldungen
$GLOBALS['TL_LANG']['tl_mailinglisten']['fehltSodium'] = 'Die PHP-Erweiterung "sodium" fehlt. Die Postfach-Kennwörter können nicht verschlüsselt werden.';
$GLOBALS['TL_LANG']['tl_mailinglisten']['fehltImap'] = 'Das Paket "webklex/php-imap" ist nicht installiert. Es werden keine Nachrichten abgeholt.';
