<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WapplerSystems\ZabbixClient\OperationResult;

class HasStuckSchedulerTask implements IOperation, SingletonInterface
{
    const MAX_RUNNING_HOURS = 6;

    /**
     * @param array $parameter
     * @return OperationResult
     *
     * @throws Exception|DBALException
     */
    public function execute($parameter = [])
    {
        $maxRunningHours = intval(isset($parameter['maxRunningHours']) ? $parameter['maxRunningHours'] : 0);
        // Make sure we do not use a number smaller than 1 here.
        if($maxRunningHours < 1) {
            $maxRunningHours = self::MAX_RUNNING_HOURS;
        }

        $schedulerRecords = $this->fetchSchedulerTasks();
        if (0 === count($schedulerRecords)) {
            // No tasks found.
            return new OperationResult(true, false);
        }

        foreach ($schedulerRecords as $schedulerRecord) {
            // Check if the task is running.
            $isRunning = !empty($schedulerRecord['serialized_executions']);
            if (!$isRunning) {
                continue;
            }
            // Validate for required column value (lastexecution_time).
            if (empty($schedulerRecord['lastexecution_time'])) {
                continue;
            }

            // Compare lastexecution_time with current time.
            $currentDateTime = new DateTime();
            $lastExecutedDateTime = (new DateTime())->setTimestamp((int)$schedulerRecord['lastexecution_time']);
            $runningHours = intval($currentDateTime->diff($lastExecutedDateTime)->format('%h'));
            $runningHours += intval($currentDateTime->diff($lastExecutedDateTime)->days) * 24;

            if ($runningHours >= $maxRunningHours) {
                return new OperationResult(true, true);
            }
        }

        // No task is running.
        return new OperationResult(true, false);
    }

    /**
     * @return array
     *
     * @throws Exception|DBALException
     */
    private function fetchSchedulerTasks()
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder
            ->select('uid', 'serialized_executions', 'lastexecution_time')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->eq('disable', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );

        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            );
        }

        return $queryBuilder->execute()->fetchAllAssociative();
    }
}
