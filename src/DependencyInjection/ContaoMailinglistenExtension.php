<?php

declare(strict_types=1);

/*
 * Contao Mailinglisten Bundle.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoMailinglistenBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Lädt die Dienste des Bundles in den Symfony-Container.
 *
 * Symfony leitet den erwarteten Klassennamen aus dem Bundle-Namen ab:
 * ContaoMailinglistenExtension gehört zu ContaoMailinglistenBundle. Weicht eines
 * von beiden ab, findet der Kernel die Dienste nicht — und zwar ohne
 * Fehlermeldung. Der Cronjob liefe dann nie, ohne dass es auffiele.
 */
class ContaoMailinglistenExtension extends Extension
{
    /**
     * Liest die services.yaml des Bundles ein.
     *
     * Eine eigene Konfiguration über die Projektdatei `config/config.yaml`
     * bietet das Bundle nicht an: sämtliche Einstellungen — Postfachzugang,
     * Versandweg, Kennungen — stehen an der jeweiligen Mailingliste in der
     * Datenbank, damit sie ohne Dateizugriff pflegbar sind. Der Parameter
     * $configs bleibt deshalb ungenutzt.
     *
     * @param array<array-key, mixed> $configs   Konfiguration aus dem Projekt, hier leer
     * @param ContainerBuilder        $container Der im Aufbau befindliche Container
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
    }
}
