<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

// Felder
$GLOBALS['TL_LANG']['tl_mailingliste']['titel'] = array('Name der Liste', 'Erscheint im Betreff und im angezeigten Absendernamen, zum Beispiel "Vorstand".');
$GLOBALS['TL_LANG']['tl_mailingliste']['adresse'] = array('Adresse der Liste', 'Die E-Mail-Adresse, an die geschrieben wird und unter der die Liste versendet. Sie muss zum unten angegebenen Postfach gehören.');
$GLOBALS['TL_LANG']['tl_mailingliste']['beschreibung'] = array('Beschreibung', 'Nur für die eigene Übersicht im Backend; sie wird nirgends versendet.');

$GLOBALS['TL_LANG']['tl_mailingliste']['imapHost'] = array('IMAP-Server', 'Rechnername ohne Protokoll, zum Beispiel "imap.example.org".');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapPort'] = array('IMAP-Port', 'Üblich sind 993 mit SSL und 143 mit STARTTLS.');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapVerschluesselung'] = array('IMAP-Verschlüsselung', 'SSL verschlüsselt ab dem ersten Byte (Port 993), STARTTLS schaltet nachträglich um (Port 143).');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapBenutzer'] = array('IMAP-Benutzername', 'Bei den meisten Anbietern die vollständige E-Mail-Adresse.');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapKennwort'] = array('IMAP-Kennwort', 'Wird verschlüsselt gespeichert. Die Sternchen stehen für das hinterlegte Kennwort — bleiben sie stehen, ändert sich nichts. Ein leeres Feld löscht das Kennwort.');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapOrdner'] = array('IMAP-Ordner', 'Der Ordner, in dem die eingehenden Nachrichten liegen. In aller Regel "INBOX".');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapZertifikat'] = array('Zertifikat prüfen', 'Abschalten nur bei einem Testaufbau mit selbstsigniertem Zertifikat.');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapNachbehandlung'] = array('Nach der Verarbeitung', 'Was mit einer abgearbeiteten Nachricht im Postfach geschehen soll.');
$GLOBALS['TL_LANG']['tl_mailingliste']['imapOrdnerErledigt'] = array('Zielordner', 'Der Ordner, in den verarbeitete Nachrichten verschoben werden. Er muss auf dem Server bereits vorhanden sein.');

$GLOBALS['TL_LANG']['tl_mailingliste']['smtpHost'] = array('SMTP-Server', 'Bleibt das Feld leer, versendet die Liste über den allgemeinen Mailer der Contao-Installation. Dann stimmt die Absenderadresse oft nicht mit dem versendenden Server überein, und die Nachrichten landen häufiger im Spam.');
$GLOBALS['TL_LANG']['tl_mailingliste']['smtpPort'] = array('SMTP-Port', 'Üblich sind 587 mit STARTTLS und 465 mit SSL.');
$GLOBALS['TL_LANG']['tl_mailingliste']['smtpVerschluesselung'] = array('SMTP-Verschlüsselung', 'SSL verschlüsselt ab dem ersten Byte (Port 465), STARTTLS schaltet nachträglich um (Port 587).');
$GLOBALS['TL_LANG']['tl_mailingliste']['smtpBenutzer'] = array('SMTP-Benutzername', 'Bleibt das Feld leer, wird ohne Anmeldung versendet.');
$GLOBALS['TL_LANG']['tl_mailingliste']['smtpKennwort'] = array('SMTP-Kennwort', 'Wird verschlüsselt gespeichert. Die Sternchen stehen für das hinterlegte Kennwort — bleiben sie stehen, ändert sich nichts.');

$GLOBALS['TL_LANG']['tl_mailingliste']['betreffPraefix'] = array('Betreffkennzeichen', 'Wird jedem verteilten Betreff vorangestellt, zum Beispiel "[Vorstand]". Ein bereits vorhandenes Kennzeichen wird nicht ein zweites Mal angehängt.');
$GLOBALS['TL_LANG']['tl_mailingliste']['antwortAn'] = array('Antworten gehen an', 'Bei "an die Liste" wird aus dem Verteiler ein Gesprächskreis, bei "an den Absender" ein Rundschreiben mit persönlichen Rückfragen.');
$GLOBALS['TL_LANG']['tl_mailingliste']['anhaengeUebernehmen'] = array('Anhänge weitergeben', 'Ohne diese Einstellung wird nur der Text verteilt. Das schont das Postfach der Empfänger, verliert aber Dateien.');
$GLOBALS['TL_LANG']['tl_mailingliste']['fussnote'] = array('Fußzeile', 'Wird unter jede verteilte Nachricht gesetzt. Verfügbare Platzhalter: ##liste##, ##adresse##, ##kennung##, ##abmeldekennung##, ##absender##, ##absendername##, ##betreff##.');

$GLOBALS['TL_LANG']['tl_mailingliste']['aufnahmeKennung'] = array('Kennwort für die Aufnahme', 'Steht dieses Wort am Anfang des Betreffs, gilt die Nachricht eines Fremden als Aufnahmeantrag. Ein leeres Feld schaltet die Funktion ab.');
$GLOBALS['TL_LANG']['tl_mailingliste']['abmeldeKennung'] = array('Kennwort für die Abmeldung', 'Steht dieses Wort am Anfang des Betreffs, trägt sich der Absender selbst aus. Ein leeres Feld schaltet die Funktion ab.');
$GLOBALS['TL_LANG']['tl_mailingliste']['benachrichtigung'] = array('Benachrichtigung an', 'Diese Adressen erfahren von einem neuen Aufnahmeantrag. Mehrere durch Komma trennen.');
$GLOBALS['TL_LANG']['tl_mailingliste']['bestaetigungText'] = array('Text der Antragsbestätigung', 'Geht an den Antragsteller. Bleibt das Feld leer, wird ein Standardtext verwendet. Platzhalter wie bei der Fußzeile.');
$GLOBALS['TL_LANG']['tl_mailingliste']['ablehnungSenden'] = array('Absender über die Ablehnung unterrichten', 'Bei einer Adresse, die viel Spam bekommt, besser abschalten: Jede Ablehnung an eine gefälschte Absenderadresse belästigt einen Unbeteiligten.');
$GLOBALS['TL_LANG']['tl_mailingliste']['ablehnungText'] = array('Text der Ablehnung', 'Bleibt das Feld leer, wird ein Standardtext verwendet. Platzhalter wie bei der Fußzeile.');

$GLOBALS['TL_LANG']['tl_mailingliste']['pruefintervall'] = array('Prüfintervall in Minuten', 'Wie oft das Postfach abgefragt wird. 0 heißt: bei jedem Cron-Durchgang.');
$GLOBALS['TL_LANG']['tl_mailingliste']['hoechstzahl'] = array('Nachrichten je Durchgang', 'Begrenzt die Laufzeit eines Durchgangs. Was übrig bleibt, kommt beim nächsten Mal an die Reihe.');
$GLOBALS['TL_LANG']['tl_mailingliste']['published'] = array('Liste aktiv', 'Nur aktive Listen werden vom Cronjob abgefragt. Das Postfach einer abgeschalteten Liste bleibt unangetastet.');

// Legenden
$GLOBALS['TL_LANG']['tl_mailingliste']['titel_legend'] = 'Grunddaten';
$GLOBALS['TL_LANG']['tl_mailingliste']['postfach_legend'] = 'Postfach (Empfang per IMAP)';
$GLOBALS['TL_LANG']['tl_mailingliste']['versand_legend'] = 'Versand (SMTP)';
$GLOBALS['TL_LANG']['tl_mailingliste']['verteilung_legend'] = 'Verteilung';
$GLOBALS['TL_LANG']['tl_mailingliste']['aufnahme_legend'] = 'Aufnahme, Abmeldung und Ablehnung';
$GLOBALS['TL_LANG']['tl_mailingliste']['lauf_legend'] = 'Regelmäßiger Lauf';
$GLOBALS['TL_LANG']['tl_mailingliste']['published_legend'] = 'Veröffentlichung';

// Auswahlwerte
$GLOBALS['TL_LANG']['tl_mailingliste']['verschluesselung']['ssl'] = 'SSL/TLS (ab dem ersten Byte)';
$GLOBALS['TL_LANG']['tl_mailingliste']['verschluesselung']['tls'] = 'STARTTLS (nachträglich)';
$GLOBALS['TL_LANG']['tl_mailingliste']['verschluesselung']['keine'] = 'Keine Verschlüsselung';

$GLOBALS['TL_LANG']['tl_mailingliste']['nachbehandlung']['gelesen'] = 'Als gelesen markieren';
$GLOBALS['TL_LANG']['tl_mailingliste']['nachbehandlung']['verschieben'] = 'In einen anderen Ordner verschieben';
$GLOBALS['TL_LANG']['tl_mailingliste']['nachbehandlung']['loeschen'] = 'Löschen';

$GLOBALS['TL_LANG']['tl_mailingliste']['antwortZiel']['liste'] = 'an die Liste';
$GLOBALS['TL_LANG']['tl_mailingliste']['antwortZiel']['absender'] = 'an den Absender';

// Operationen
$GLOBALS['TL_LANG']['tl_mailingliste']['teilnehmer'] = array('Teilnehmer', 'Die Teilnehmer der Mailingliste ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_mailingliste']['protokoll'] = array('Verlauf', 'Den Verlauf der Mailingliste ID %s ansehen');
$GLOBALS['TL_LANG']['tl_mailingliste']['edit'] = array('Bearbeiten', 'Die Mailingliste ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_mailingliste']['copy'] = array('Duplizieren', 'Die Mailingliste ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_mailingliste']['delete'] = array('Löschen', 'Die Mailingliste ID %s löschen');
$GLOBALS['TL_LANG']['tl_mailingliste']['toggle'] = array('Aktivieren/deaktivieren', 'Die Mailingliste ID %s aktivieren oder deaktivieren');
$GLOBALS['TL_LANG']['tl_mailingliste']['show'] = array('Details', 'Die Details der Mailingliste ID %s anzeigen');

// Meldungen
$GLOBALS['TL_LANG']['tl_mailingliste']['fehltSodium'] = 'Die PHP-Erweiterung "sodium" fehlt. Die Postfach-Kennwörter können nicht verschlüsselt werden.';
$GLOBALS['TL_LANG']['tl_mailingliste']['fehltImap'] = 'Das Paket "webklex/php-imap" ist nicht installiert. Es werden keine Nachrichten abgeholt.';
