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

#[MonitoringOperation('HasInsecureBackendUsers')]
class HasInsecureBackendUsers implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');

        // Total admin count
        $totalAdmins = (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('admin', 1),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->executeQuery()
            ->fetchOne();

        // Admins without MFA
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $adminsWithoutMfa = (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('admin', 1),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('mfa'),
                    $queryBuilder->expr()->eq('mfa', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->eq('mfa', $queryBuilder->createNamedParameter('[]'))
                )
            )
            ->executeQuery()
            ->fetchOne();

        // Stale users (not logged in for >365 days, but have logged in at least once)
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $threshold = time() - (365 * 86400);
        $staleUsers = (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->gt('lastlogin', 0),
                $queryBuilder->expr()->lt('lastlogin', $threshold)
            )
            ->executeQuery()
            ->fetchOne();

        // Users with empty email
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $noEmail = (int)$queryBuilder
            ->count('uid')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('email'),
                    $queryBuilder->expr()->eq('email', $queryBuilder->createNamedParameter(''))
                )
            )
            ->executeQuery()
            ->fetchOne();

        return new OperationResult(true, [
            'adminsWithoutMfa' => $adminsWithoutMfa,
            'staleUsers' => $staleUsers,
            'noEmail' => $noEmail,
            'totalAdmins' => $totalAdmins,
        ]);
    }
}
