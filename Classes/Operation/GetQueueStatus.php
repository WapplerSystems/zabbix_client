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

#[MonitoringOperation('GetQueueStatus')]
class GetQueueStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tableExists('messenger_messages')) {
                return new OperationResult(true, [
                    'queues' => [],
                    'totalPending' => 0,
                ]);
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('messenger_messages');
            $queryBuilder->getRestrictions()->removeAll();

            $rows = $queryBuilder
                ->select('queue_name')
                ->addSelectLiteral('COUNT(*) AS pending')
                ->from('messenger_messages')
                ->groupBy('queue_name')
                ->executeQuery()
                ->fetchAllAssociative();

            $stuckThreshold = new \DateTime('-1 hour');

            $queues = [];
            $totalPending = 0;

            foreach ($rows as $row) {
                $queueName = $row['queue_name'];
                $pending = (int)$row['pending'];

                $stuckBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('messenger_messages');
                $stuckBuilder->getRestrictions()->removeAll();

                $stuck = (int)$stuckBuilder
                    ->count('*')
                    ->from('messenger_messages')
                    ->where(
                        $stuckBuilder->expr()->eq('queue_name', $stuckBuilder->createNamedParameter($queueName)),
                        $stuckBuilder->expr()->lt('available_at', $stuckBuilder->createNamedParameter($stuckThreshold->format('Y-m-d H:i:s')))
                    )
                    ->executeQuery()
                    ->fetchOne();

                $queues[] = [
                    'name' => $queueName,
                    'pending' => $pending,
                    'stuck' => $stuck,
                ];

                $totalPending += $pending;
            }

            return new OperationResult(true, [
                'queues' => $queues,
                'totalPending' => $totalPending,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error querying queue status: ' . $e->getMessage());
        }
    }
}
