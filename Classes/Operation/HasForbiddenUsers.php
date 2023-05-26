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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 */
#[MonitoringOperation('HasForbiddenUsers')]
class HasForbiddenUsers implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        if (!isset($parameter['usernames'])) {
            throw new InvalidArgumentException('no usernames set');
        }

        $usernames = explode(',', $parameter['usernames']);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->select('uid')->from('be_users');

        foreach ($usernames as $username) {
            $queryBuilder->orWhere($queryBuilder->expr()->eq(
                'username',
                $queryBuilder->quote($username)
            ));
        }
        return new OperationResult(true, $queryBuilder->executeQuery()->rowCount() > 0);
    }
}
