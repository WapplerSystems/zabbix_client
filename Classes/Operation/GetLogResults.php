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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 */
class GetLogResults implements IOperation, SingletonInterface
{


    /**
     * @var array
     */
    protected $actions = [
        255 => 'actionLogin',
        -1 => 'actionErrors'
    ];

    protected $timeFrames = [
        0 => 'thisWeek',
        1 => 'lastWeek',
        2 => 'last7Days',
        10 => 'thisMonth',
        11 => 'lastMonth',
        12 => 'last31Days',
        20 => 'noLimit',
        30 => 'userDefined',
    ];


    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        $filter = $parameter['filter'];

        $type = -1;
        $error = -1;
        $detailsFilter = null;
        $detailsExcludeFilter = null;
        switch ($filter) {
            case 'ServiceUnavailableException':
                $type = 5;
                $error = 2;
                $detailsFilter = 'ServiceUnavailableException';
                break;
            case 'PageNotFoundException':
                $type = 5;
                $error = 2;
                $detailsFilter = 'PageNotFoundException';
                break;
            case 'OtherExceptions':
                $type = 5;
                $error = 2;
                $detailsExcludeFilter = ['ServiceUnavailableException', 'PageNotFoundException'];
                break;
            case 'FailedLogins':
                $type = 255;
                $error = 3;
                break;
        }


        $objectManager = new ObjectManager();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $objectManager->get(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $queryBuilder->resetRestrictions();
        $queryBuilder->select('uid')->from('sys_log')->where(
            $queryBuilder->expr()->eq(
                'error',
                $error
            )
        );
        if ($type !== -1) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq(
                'type',
                $type
            ));
        }
        if ($detailsFilter !== null) {
            $queryBuilder->andWhere($queryBuilder->expr()->like(
                'details',
                $queryBuilder->quote('%' . $detailsFilter . '%')
            ));
        }
        if ($detailsExcludeFilter !== null) {
            foreach ($detailsExcludeFilter as $ex) {
                $queryBuilder->andWhere($queryBuilder->expr()->notLike(
                    'details',
                    $queryBuilder->quote('%' . $ex . '%')
                ));
            }
        }

        $logCount = $queryBuilder->execute()->rowCount();

        return new OperationResult(true, $logCount);
    }
}
