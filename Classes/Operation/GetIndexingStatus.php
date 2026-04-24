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

#[MonitoringOperation('GetIndexingStatus')]
class GetIndexingStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        // Try Apache Solr first
        if ($this->tableExists('tx_solr_indexqueue_item')) {
            return $this->getSolrStatus();
        }

        // Try indexed_search
        if ($this->tableExists('index_words')) {
            return $this->getIndexedSearchStatus();
        }

        return new OperationResult(false, 'No search indexing configured');
    }

    private function getSolrStatus(): OperationResult
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $queryBuilder->getRestrictions()->removeAll();

        $total = (int)$queryBuilder
            ->count('uid')
            ->from('tx_solr_indexqueue_item')
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $queryBuilder->getRestrictions()->removeAll();

        $pending = (int)$queryBuilder
            ->count('uid')
            ->from('tx_solr_indexqueue_item')
            ->where($queryBuilder->expr()->eq('indexed', 0))
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $queryBuilder->getRestrictions()->removeAll();

        $failed = (int)$queryBuilder
            ->count('uid')
            ->from('tx_solr_indexqueue_item')
            ->where($queryBuilder->expr()->neq('errors', $queryBuilder->createNamedParameter('')))
            ->executeQuery()
            ->fetchOne();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_solr_indexqueue_item');
        $queryBuilder->getRestrictions()->removeAll();

        $lastIndexed = $queryBuilder
            ->select('indexed')
            ->from('tx_solr_indexqueue_item')
            ->where($queryBuilder->expr()->gt('indexed', 0))
            ->orderBy('indexed', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return new OperationResult(true, [
            'total' => $total,
            'pending' => $pending,
            'failed' => $failed,
            'lastIndexed' => $lastIndexed ? (int)$lastIndexed : 0,
        ]);
    }

    private function getIndexedSearchStatus(): OperationResult
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('index_words');
        $queryBuilder->getRestrictions()->removeAll();

        $total = (int)$queryBuilder
            ->count('uid')
            ->from('index_words')
            ->executeQuery()
            ->fetchOne();

        return new OperationResult(true, [
            'total' => $total,
            'pending' => 0,
            'failed' => 0,
            'lastIndexed' => 0,
        ]);
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($tableName);
            $schemaManager = $connection->createSchemaManager();
            return $schemaManager->tablesExist([$tableName]);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
