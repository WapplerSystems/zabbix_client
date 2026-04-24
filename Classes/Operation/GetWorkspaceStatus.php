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

#[MonitoringOperation('GetWorkspaceStatus')]
class GetWorkspaceStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            $schemaManager = $connection->createSchemaManager();

            if (!$schemaManager->tableExists('sys_workspace')) {
                return new OperationResult(false, 'Workspaces not installed');
            }

            // Count workspaces
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
            $queryBuilder->getRestrictions()->removeAll();
            $workspaceCount = (int)$queryBuilder
                ->count('uid')
                ->from('sys_workspace')
                ->where(
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            // Count unpublished pages in workspaces
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll();
            $unpublishedPages = (int)$queryBuilder
                ->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->gt('t3ver_state', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            // Count unpublished content in workspaces
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
            $queryBuilder->getRestrictions()->removeAll();
            $unpublishedContent = (int)$queryBuilder
                ->count('uid')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->gt('t3ver_wsid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                    $queryBuilder->expr()->gt('t3ver_state', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            return new OperationResult(true, [
                'workspaces' => $workspaceCount,
                'unpublishedPages' => $unpublishedPages,
                'unpublishedContent' => $unpublishedContent,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error querying workspace status: ' . $e->getMessage());
        }
    }
}
