<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Hauptklasse des Bundles.
 *
 * Symfony erkennt ein Bundle ausschließlich an einer solchen Klasse. Sie
 * bleibt bewusst leer: Dienste, Sprachdateien und DCA-Dateien werden über die
 * Extension und die Verzeichnisstruktur gefunden, nicht über überschriebene
 * Methoden.
 *
 * Aus dem Klassennamen leitet Symfony auch den Namen der DI-Extension ab —
 * ContaoMailinglistenBundle erwartet ContaoMailinglistenExtension. Weicht eines
 * von beiden ab, bleibt die services.yaml ungelesen, und zwar ohne Fehler.
 */
class ContaoMailinglistenBundle extends Bundle
{
}
