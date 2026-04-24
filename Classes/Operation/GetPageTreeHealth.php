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

#[MonitoringOperation('GetPageTreeHealth')]
class GetPageTreeHealth implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            // Hidden pages
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $hiddenPages = (int)$queryBuilder
                ->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            // Orphaned pages (pid references a non-existent or deleted page)
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            $orphanedPages = (int)$connection->executeQuery(
                'SELECT COUNT(*) FROM pages WHERE pid NOT IN (SELECT uid FROM pages WHERE deleted = 0) AND pid > 0 AND deleted = 0'
            )->fetchOne();

            // Total pages (non-deleted, doktype < 200)
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $totalPages = (int)$queryBuilder
                ->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->lt('doktype', $queryBuilder->createNamedParameter(200, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            // Max depth via iterative approach
            $maxDepth = $this->calculateMaxDepth($connection);

            return new OperationResult(true, [
                'hiddenPages' => $hiddenPages,
                'orphanedPages' => $orphanedPages,
                'totalPages' => $totalPages,
                'maxDepth' => $maxDepth,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error checking page tree health: ' . $e->getMessage());
        }
    }

    private function calculateMaxDepth(Connection $connection): int
    {
        // Build a pid => [uids] lookup for non-deleted pages
        $rows = $connection->executeQuery(
            'SELECT uid, pid FROM pages WHERE deleted = 0'
        )->fetchAllAssociative();

        if (empty($rows)) {
            return 0;
        }

        $childrenByPid = [];
        foreach ($rows as $row) {
            $pid = (int)$row['pid'];
            $childrenByPid[$pid][] = (int)$row['uid'];
        }

        // BFS from root (pid=0) to find max depth
        $maxDepth = 0;
        $currentLevel = $childrenByPid[0] ?? [];
        $depth = 1;

        while (!empty($currentLevel)) {
            $maxDepth = $depth;
            $nextLevel = [];
            foreach ($currentLevel as $uid) {
                if (isset($childrenByPid[$uid])) {
                    foreach ($childrenByPid[$uid] as $childUid) {
                        $nextLevel[] = $childUid;
                    }
                }
            }
            $currentLevel = $nextLevel;
            $depth++;

            // Safety limit to prevent infinite loops from circular references
            if ($depth > 100) {
                break;
            }
        }

        return $maxDepth;
    }
}
