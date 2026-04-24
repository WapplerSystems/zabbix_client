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

#[MonitoringOperation('HasBrokenLinks')]
class HasBrokenLinks implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tableExists('tx_linkvalidator_link')) {
                return new OperationResult(false, 'linkvalidator not installed');
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_linkvalidator_link');
            $queryBuilder->getRestrictions()->removeAll();

            $rows = $queryBuilder
                ->select('link_type')
                ->addSelectLiteral('COUNT(*) AS cnt')
                ->from('tx_linkvalidator_link')
                ->groupBy('link_type')
                ->executeQuery()
                ->fetchAllAssociative();

            $byType = [];
            $total = 0;

            foreach ($rows as $row) {
                $type = $row['link_type'];
                $count = (int)$row['cnt'];
                $byType[$type] = $count;
                $total += $count;
            }

            return new OperationResult(true, [
                'total' => $total,
                'byType' => $byType,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error querying broken links: ' . $e->getMessage());
        }
    }
}
