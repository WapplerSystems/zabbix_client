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

#[MonitoringOperation('GetBackendSessionCount')]
class GetBackendSessionCount implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('be_sessions');
            $queryBuilder->getRestrictions()->removeAll();

            $activeSessions = (int)$queryBuilder
                ->count('ses_id')
                ->from('be_sessions')
                ->executeQuery()
                ->fetchOne();

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('be_sessions');
            $queryBuilder->getRestrictions()->removeAll();

            $uniqueUsers = (int)$queryBuilder
                ->addSelectLiteral($queryBuilder->expr()->count('DISTINCT ' . $queryBuilder->quoteIdentifier('ses_userid'), 'cnt'))
                ->from('be_sessions')
                ->executeQuery()
                ->fetchOne();
        } catch (\Throwable $e) {
            return new OperationResult(true, [
                'activeSessions' => 0,
                'uniqueUsers' => 0,
            ]);
        }

        return new OperationResult(true, [
            'activeSessions' => $activeSessions,
            'uniqueUsers' => $uniqueUsers,
        ]);
    }
}
