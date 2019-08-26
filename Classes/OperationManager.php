<?php

namespace WapplerSystems\ZabbixClient;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Operation\IOperation;


class OperationManager implements IOperationManager
{
    /**
     * @var array of IOperation
     */
    protected $operations;

    /**
     * Register a new operation
     *
     * @param string $operationKey The key of the operation (All lowercase, underscores)
     * @param string|object $operation Operation instance or class
     */
    public function registerOperation($operationKey, $operation)
    {
        $this->operations[strtolower($operationKey)] = $operation;
    }

    /**
     * Get a registered operation as instance
     *
     * @param string $operationKey
     * @return IOperation|bool The operation instance or FALSE if not registered
     */
    public function getOperation($operationKey)
    {
        $operationKey = strtolower($operationKey);
        if (is_string($this->operations[$operationKey])) {
            return GeneralUtility::makeInstance($this->operations[$operationKey]);
        }
        if (is_object($this->operations[$operationKey])) {
            return $this->operations[$operationKey];
        }
        return false;
    }

    /**
     * Execute an Operation by key with optional parameters
     *
     * @param string $operationKey
     * @param array|null $parameter
     * @return OperationResult
     */
    public function executeOperation($operationKey, $parameter = [])
    {
        $operation = $this->getOperation($operationKey);
        if ($operation) {
            return $operation->execute($parameter);
        }
        return new OperationResult(false, 'Operation [' . $operationKey . '] unknown');
    }


}
