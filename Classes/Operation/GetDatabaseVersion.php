<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\OperationResult;


class GetDatabaseVersion implements IOperation, SingletonInterface
{
    /**
     * Get the current database version
     *
     * @param array $parameter None
     * @return OperationResult the current database version
     */
    public function execute($parameter = [])
    {
        $db = [];
        foreach (GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionNames() as $connectionName) {

            try {
                $db[] = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionByName($connectionName)
                    ->getServerVersion();
            } catch (DBALException $e) {
            }

        }

        return new OperationResult(true, $db);
    }
}
