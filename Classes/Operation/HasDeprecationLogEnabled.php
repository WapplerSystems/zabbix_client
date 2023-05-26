<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 * Detecting if deprecation messages are logged
 *
 * E_DEPRECATED
 * E_USER_DEPRECATED
 *
 */
#[MonitoringOperation('HasDeprecationLogEnabled')]
class HasDeprecationLogEnabled implements IOperation, SingletonInterface
{

    protected static $levelNames = [
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        $errorHandlerErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['errorHandlerErrors'];

        $levels = [];
        foreach (self::$levelNames as $level => $name) {
            if (($errorHandlerErrors & $level) === $level) {
                $levels[] = $name;
            }
        }

        return new OperationResult(true, count($levels) > 0);
    }
}
