<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetMemoryUsage')]
class GetMemoryUsage implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        return new OperationResult(true, [
            'currentUsageMB' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peakUsageMB' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'memoryLimit' => ini_get('memory_limit'),
        ]);
    }
}
