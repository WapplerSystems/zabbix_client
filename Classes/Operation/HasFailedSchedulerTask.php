<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 *
 */
class HasFailedSchedulerTask implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task');
        $count = $queryBuilder
            ->count('uid')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->neq('lastexecution_failure', $queryBuilder->createNamedParameter(''))
                )
            ->execute()
            ->fetchColumn(0);

        return new OperationResult(true, $count > 0);
    }
}
