<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetFailedLoginCount')]
class GetFailedLoginCount implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $hours = isset($parameter['hours']) ? (int)$parameter['hours'] : 24;
        $since = time() - ($hours * 3600);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_log');

        $count = (int)$queryBuilder
            ->count('uid')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->eq('type', 255),
                $queryBuilder->expr()->eq('error', 3),
                $queryBuilder->expr()->gte('tstamp', $since)
            )
            ->executeQuery()
            ->fetchOne();

        return new OperationResult(true, $count);
    }
}
