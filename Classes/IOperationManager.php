<?php

namespace WapplerSystems\ZabbixClient;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use WapplerSystems\ZabbixClient\Operation\IOperation;

/**
 * The Operation manager is responsible for registering and
 * executing Operations. An Operation is registered with
 * a unique key either as a class name or instance.
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 *
 */
interface IOperationManager
{
    /**
     * Register a new operation with the given key.
     *
     * @param string $operationKey The key of the operation (All lowercase, underscores)
     * @param string|object $operation Operation instance or class
     */
    public function registerOperation($operationKey, $operation);

    /**
     * Get a registered operation as instance by key
     *
     * @param string $operationKey
     * @return IOperation|bool The Operation instance or FALSE if not registered
     */
    public function getOperation($operationKey);

    /**
     * Execute an Operation by key with optional parameters
     *
     * @param string $operationKey
     * @param array $parameter
     * @return OperationResult
     */
    public function executeOperation($operationKey, $parameter = []);
}
