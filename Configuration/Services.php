<?php

declare(strict_types=1);

namespace WapplerSystems\ZabbixClient;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        MonitoringOperation::class,
        static function (ChildDefinition $definition, MonitoringOperation $attribute): void {
            $definition->addTag(MonitoringOperation::TAG_NAME, ['identifier' => $attribute->identifier]);
        }
    );
};
