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
 * A sample Operation which returns the installed PHP version
 */
#[MonitoringOperation('GetPHPVersion')]
class GetPHPVersion implements IOperation, SingletonInterface
{
    /**
     * Get the current PHP version
     *
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute($parameter = [])
    {
        return new OperationResult(true, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION);
    }
}
