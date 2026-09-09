<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Schachbulle\ContaoMailinglistenBundle\ContaoMailinglistenBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Meldet das Bundle beim Contao Manager an.
 *
 * Ohne den Eintrag `extra.contao-manager-plugin` in der composer.json wird
 * diese Klasse nicht gefunden und das Bundle nicht in den Kernel geladen.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    /**
     * Gibt die Ladereihenfolge des Bundles an.
     *
     * Das Bundle wird nach dem Contao-Kern geladen, weil es dessen
     * Backend-Module, den Cron-Dienst und die Model-Registrierung nutzt.
     * Weitere Abhängigkeiten zu anderen Bundles bestehen nicht; der
     * IMAP-Zugriff läuft über eine Composer-Bibliothek und nicht über ein
     * Contao-Bundle.
     *
     * @param ParserInterface $parser Wird vom Contao Manager übergeben, um
     *                                zusätzliche Konfigurationsdateien zu
     *                                lesen; hier nicht benötigt.
     *
     * @return BundleConfig[] Genau ein Eintrag für dieses Bundle
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoMailinglistenBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * Meldet die Route für die Ein-Klick-Abmeldung an.
     *
     * Der Parameter heißt zwar „Resolver“, ist aber keiner der Lader selbst:
     * Erst `resolve()` liefert den Lader, der die Datei tatsächlich lesen kann.
     * Wird die Datei von keinem Lader angenommen, gibt `resolve()` `false`
     * zurück — dann bleibt es bei keiner Route, statt an einem Aufruf auf
     * `false` abzustürzen.
     *
     * @param LoaderResolverInterface $resolver Sucht den passenden Lader
     * @param KernelInterface         $kernel   Der laufende Kernel, hier ungenutzt
     *
     * @return RouteCollection|null Die Routen des Bundles, oder null wenn die
     *                              Datei nicht gelesen werden konnte
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $datei = __DIR__.'/../Resources/config/routing.yaml';
        $lader = $resolver->resolve($datei);

        if (false === $lader) {
            return null;
        }

        return $lader->load($datei);
    }
}
