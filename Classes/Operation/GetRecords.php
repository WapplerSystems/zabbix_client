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
 * An Operation that returns records matched by field name(s) and value(s) as an array (excluding protected record details like be_user password).
 * This operation should be SQL injection safe. The table has to be mapped in the TCA.
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 *
 */
#[MonitoringOperation('GetRecords')]
class GetRecords implements IOperation, SingletonInterface
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

    /**
     * @var array
     */
    protected $implicitFields = ['uid', 'pid', 'deleted', 'hidden'];

    /**
     * Get record data from the given table and uid
     *
     * @param array $parameter A table 'table', field name 'field' and the value 'value' to find the record
     * @return OperationResult A set of records as an array or FALSE if no record was found
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

        if (is_array($field) && is_array($value)) {
            foreach ($field as $val) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$val]) && (!in_array($val,
                            $this->implicitFields) || !in_array($val, $value))) {
                    return new OperationResult(false,
                        'Field [' . $val . '] of table [' . $table . '] not found in the TCA OR not found in value array.');
                }
            }

            foreach ($value as $key => $val) {
                if (!isset($GLOBALS['TCA'][$table]['columns'][$key]) && (!in_array($key,
                            $this->implicitFields) || !in_array($key, $field))) {
                    return new OperationResult(false,
                        'Field [' . $key . '] of table [' . $table . '] not found in the TCA OR not found in field array.');
                }
            }
        } else {
            if (!isset($GLOBALS['TCA'][$table]['columns'][$field]) && !in_array($field, $this->implicitFields)) {
                return new OperationResult(false,
                    'Field [' . $field . '] of table [' . $table . '] not found in the TCA');
            }
        }

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            if (!$checkEnableFields) {
                $queryBuilder->getRestrictions()->removeAll();
            }
            $queryBuilder->select('*')->from($table);

            if (!is_array($field)) {
                $queryBuilder->where(
                    $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value))
                );
            } else {
                foreach ($value as $key => $val) {
                    if (is_array($val)) {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->in($key, $val)
                        );
                    } else {
                        $queryBuilder->andWhere(
                            $queryBuilder->expr()->eq($key, $queryBuilder->createNamedParameter($val))
                        );
                    }
                }
            }

            $result = $queryBuilder->executeQuery();
            $records = [];
            while ($record = $result->fetchAssociative()) {
                if (isset($this->protectedFieldsByTable[$table])) {
                    $protectedFields = $this->protectedFieldsByTable[$table];
                    foreach ($protectedFields as $protectedField) {
                        unset($record[$protectedField]);
                    }
                }
                $records[] = $record;
            }

            return new OperationResult(true, $records);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error when executing SQL: [' . $e->getMessage() . ']');
        }
    }
}