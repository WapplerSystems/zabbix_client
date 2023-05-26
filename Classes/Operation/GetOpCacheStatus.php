<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetOpCacheStatus')]
class GetOpCacheStatus implements IOperation, SingletonInterface
{
    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute($parameter = [])
    {

        /** @var OpcodeCacheService $opCacheService */
        $opCacheService = GeneralUtility::makeInstance(OpcodeCacheService::class);

        return new OperationResult(true, $opCacheService->getAllActive());
    }
}
