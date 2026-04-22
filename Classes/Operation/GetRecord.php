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

/**
 * An Operation that returns the first record matched by a field name and value as an array (excluding protected record details like be_user password).
 * This operation should be SQL injection safe. The table has to be mapped in the TCA.
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 *
 */
#[MonitoringOperation('GetRecord')]
class GetRecord implements IOperation, SingletonInterface
{
    /**
     * An array of tables and table fields that should be cleared before sending.
     *
     * @var array
     */
    protected $protectedFieldsByTable = [
        'be_users' => ['password', 'uc'],
        'fe_users' => ['password'],
    ];

    protected $implicitFields = ['uid', 'pid', 'deleted', 'hidden'];

    /**
     * Get record data from the given table and uid
     *
     * @param array $parameter A table 'table', field name 'field' and the value 'value' to find the record
     * @return OperationResult The first found record as an array or FALSE if no record was found
     */
    public function execute(array $parameter = []): OperationResult
    {
        $table = $parameter['table'];
        $field = $parameter['field'];
        $value = $parameter['value'];
        $checkEnableFields = $parameter['checkEnableFields'] == true;
        if (!isset($GLOBALS['TCA'][$table])) {
            return new OperationResult(false, 'Table [' . $table . '] not found in the TCA');
        }
        if (!isset($GLOBALS['TCA'][$table]['columns'][$field]) && !in_array($field, $this->implicitFields)) {
            return new OperationResult(false, 'Field [' . $field . '] of table [' . $table . '] not found in the TCA');
        }

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            if (!$checkEnableFields) {
                $queryBuilder->getRestrictions()->removeAll();
            }
            $result = $queryBuilder
                ->select('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value))
                )
                ->setMaxResults(1)
                ->executeQuery();

            $record = $result->fetchAssociative();
            if ($record !== false) {
                if (isset($this->protectedFieldsByTable[$table])) {
                    $protectedFields = $this->protectedFieldsByTable[$table];
                    foreach ($protectedFields as $protectedField) {
                        unset($record[$protectedField]);
                    }
                }
                return new OperationResult(true, $record);
            }
            return new OperationResult(true, false);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error when executing SQL: [' . $e->getMessage() . ']');
        }
    }
}