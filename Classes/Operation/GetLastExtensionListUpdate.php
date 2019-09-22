<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Extensionmanager\Task\UpdateExtensionListTask;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use WapplerSystems\ZabbixClient\OperationResult;


class GetLastExtensionListUpdate implements IOperation, SingletonInterface
{

    public function execute($parameter = [])
    {

        if (!ExtensionManagementUtility::isLoaded('scheduler')) {
            return new OperationResult(true, 0);
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder->select('t.*')
            ->addSelect(
                'g.groupName AS taskGroupName',
                'g.description AS taskGroupDescription',
                'g.deleted AS isTaskGroupDeleted'
            )
            ->from('tx_scheduler_task', 't')
            ->leftJoin(
                't',
                'tx_scheduler_task_group',
                'g',
                $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('t.deleted', 0)
            )
            ->orderBy('g.sorting')
            ->execute();

        while ($task = $result->fetch()) {

            $taskObj = unserialize($task['serialized_task_object'], [AbstractTask::class]);
            if (get_class($taskObj) === UpdateExtensionListTask::class) {
                if (!empty($task['lastexecution_time'])) {
                    return new OperationResult(true, (int)$task['lastexecution_time']);
                }
            }
        }

        return new OperationResult(true, 0);
    }

}