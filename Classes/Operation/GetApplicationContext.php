<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetApplicationContext')]
class GetApplicationContext implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute($parameter = [])
    {

        $applicationContext = Environment::getContext();
        if ($applicationContext->isDevelopment()) {
            return new OperationResult(true, 'Development');
        }
        if ($applicationContext->isTesting()) {
            return new OperationResult(true, 'Testing');
        }
        if ($applicationContext->isProduction()) {
            return new OperationResult(true, 'Production');
        }
        return new OperationResult(true, '');

    }
}
