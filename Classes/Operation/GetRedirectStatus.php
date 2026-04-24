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

#[MonitoringOperation('GetRedirectStatus')]
class GetRedirectStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_redirect');

        // Check if table exists
        try {
            $schemaManager = $connection->createSchemaManager();
            if (!$schemaManager->tablesExist(['sys_redirect'])) {
                return new OperationResult(true, [
                    'total' => 0,
                    'disabled' => 0,
                    'unused' => 0,
                    'potentialLoops' => 0,
                ]);
            }
        } catch (\Throwable $e) {
            return new OperationResult(true, [
                'total' => 0,
                'disabled' => 0,
                'unused' => 0,
                'potentialLoops' => 0,
            ]);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();

        $total = (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();

        $disabled = (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->where($queryBuilder->expr()->eq('disabled', 1))
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();

        $unused = (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->where($queryBuilder->expr()->eq('hitcount', 0))
            ->executeQuery()
            ->fetchOne();

        // Check for potential loops: redirects where target contains the source_host
        $potentialLoops = 0;
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('source_host', 'target')
            ->from('sys_redirect')
            ->where($queryBuilder->expr()->neq('source_host', $queryBuilder->createNamedParameter('')))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            if (!empty($row['source_host']) && !empty($row['target']) && str_contains($row['target'], $row['source_host'])) {
                $potentialLoops++;
            }
        }

        return new OperationResult(true, [
            'total' => $total,
            'disabled' => $disabled,
            'unused' => $unused,
            'potentialLoops' => $potentialLoops,
        ]);
    }
}
