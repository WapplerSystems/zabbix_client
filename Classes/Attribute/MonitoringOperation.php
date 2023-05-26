<?php

declare(strict_types=1);


namespace WapplerSystems\ZabbixClient\Attribute;

use Attribute;

/**
 * Service tag to autoconfigure operations
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MonitoringOperation
{
    public const TAG_NAME = 'zabbix_client.operation';

    public function __construct(
        public string $identifier
    ) {
    }
}
