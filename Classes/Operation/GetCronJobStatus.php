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

#[MonitoringOperation('GetCronJobStatus')]
class GetCronJobStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $maxHours = isset($parameter['maxHours']) ? (int)$parameter['maxHours'] : 2;

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');

        $lastRun = $queryBuilder
            ->addSelectLiteral('MAX(lastexecution_time) AS last_execution')
            ->from('tx_scheduler_task')
            ->executeQuery()
            ->fetchAssociative();

        $lastRunTimestamp = (int)($lastRun['last_execution'] ?? 0);

        if ($lastRunTimestamp === 0) {
            return new OperationResult(true, [
                'lastRun' => 0,
                'secondsAgo' => -1,
                'isOverdue' => true,
            ]);
        }

        $secondsAgo = time() - $lastRunTimestamp;
        $isOverdue = $secondsAgo > ($maxHours * 3600);

        return new OperationResult(true, [
            'lastRun' => $lastRunTimestamp,
            'secondsAgo' => $secondsAgo,
            'isOverdue' => $isOverdue,
        ]);
    }
}
