<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Die Beschriftung einer Legende wird immer unter dem Namen der eigenen
 * Tabelle nachgeschlagen — in Contao 4.13 im DC_Table selbst, in Contao 5 über
 * `boxes.label` im PaletteBuilder, beide Male als
 * `$GLOBALS['TL_LANG'][<Tabelle>][<Legende>] ?? <Legende>`. Der Eintrag unter
 * `tl_user` gilt deshalb nicht mit, und ohne diese Datei stünde in der
 * Benutzergruppe der nackte Schlüssel „mailinglisten_legend“.
 *
 * Die Feldbeschriftungen fehlen hier bewusst: Der Kern ruft am Anfang seiner
 * tl_user_group.php ein `System::loadLanguageFile('tl_user')` auf, und unsere
 * DCA verweist mit `&$GLOBALS['TL_LANG']['tl_user'][…]` auf dieselben Texte.
 */
$GLOBALS['TL_LANG']['tl_user_group']['mailinglisten_legend'] = 'Mailinglisten';
