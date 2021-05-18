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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\OperationResult;


class GetApplicationContext implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute($parameter = [])
    {
        if (version_compare(TYPO3_version, '11.0.0', '>=')) {
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
        $applicationContext = GeneralUtility::getApplicationContext();
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
