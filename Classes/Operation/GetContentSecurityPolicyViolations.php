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

#[MonitoringOperation('GetContentSecurityPolicyViolations')]
class GetContentSecurityPolicyViolations implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $hours = (int)($parameter['hours'] ?? 24);

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
            $queryBuilder->getRestrictions()->removeAll();

            $threshold = time() - ($hours * 3600);

            $count = (int)$queryBuilder
                ->count('uid')
                ->from('sys_log')
                ->where(
                    $queryBuilder->expr()->eq('type', 5),
                    $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($threshold, \TYPO3\CMS\Core\Database\Connection::PARAM_INT)),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->like('details', $queryBuilder->quote('%Content-Security-Policy%')),
                        $queryBuilder->expr()->like('details', $queryBuilder->quote('%csp%'))
                    )
                )
                ->executeQuery()
                ->fetchOne();

            return new OperationResult(true, $count);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error querying CSP violations: ' . $e->getMessage());
        }
    }
}
