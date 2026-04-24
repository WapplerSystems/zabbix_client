<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('HasBrokenContentReferences')]
class HasBrokenContentReferences implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');

            // Count sys_file_reference records pointing to non-existent sys_file records
            $sql = 'SELECT COUNT(*) FROM sys_file_reference WHERE uid_local NOT IN (SELECT uid FROM sys_file) AND deleted = 0';
            $brokenReferences = (int)$connection->executeQuery($sql)->fetchOne();

            // Count sys_file records where missing flag is set
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
            $queryBuilder->getRestrictions()->removeAll();
            $missingFiles = (int)$queryBuilder
                ->count('uid')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            return new OperationResult(true, [
                'brokenReferences' => $brokenReferences,
                'missingFiles' => $missingFiles,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error checking content references: ' . $e->getMessage());
        }
    }
}
