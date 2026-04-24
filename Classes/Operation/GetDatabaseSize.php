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

#[MonitoringOperation('GetDatabaseSize')]
class GetDatabaseSize implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionByName('Default');

        // Get the current database name
        $dbName = $connection->getDatabase();

        if (empty($dbName)) {
            return new OperationResult(false, 'Could not determine database name');
        }

        $sql = "SELECT
                    TABLE_NAME AS table_name,
                    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb,
                    TABLE_ROWS AS table_rows
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = :dbName
                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
                LIMIT 20";

        $result = $connection->executeQuery($sql, ['dbName' => $dbName]);
        $rows = $result->fetchAllAssociative();

        $tables = [];
        $totalSizeMB = 0.0;

        foreach ($rows as $row) {
            $sizeMB = (float)$row['size_mb'];
            $totalSizeMB += $sizeMB;
            $tables[] = [
                'name' => $row['table_name'],
                'sizeMB' => $sizeMB,
                'rows' => (int)$row['table_rows'],
            ];
        }

        // Get total size including tables beyond top 20
        $totalSql = "SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS total_size_mb
                     FROM information_schema.TABLES
                     WHERE TABLE_SCHEMA = :dbName";
        $totalResult = $connection->executeQuery($totalSql, ['dbName' => $dbName]);
        $totalRow = $totalResult->fetchAssociative();
        $totalSizeMB = (float)($totalRow['total_size_mb'] ?? $totalSizeMB);

        return new OperationResult(true, [
            'totalSizeMB' => $totalSizeMB,
            'tables' => $tables,
        ]);
    }
}
