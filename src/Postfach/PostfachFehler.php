<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\Postfach;

/**
 * Zeigt an, dass ein Postfach nicht gelesen werden konnte.
 *
 * Die Klasse fasst die verschiedenen Ausnahmen der IMAP-Bibliothek zu einer
 * einzigen zusammen, damit der Cronjob nur einen Typ abfangen muss und die
 * Fachschichten die Bibliothek nicht kennen müssen. Die ursprüngliche
 * Ausnahme hängt als `previous` daran und steht im Protokoll.
 */
class PostfachFehler extends \RuntimeException
{
}
