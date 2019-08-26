<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * A Operation which returns the current TYPO3 version
 */
class GetTYPO3Version implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current PHP version
     */
    public function execute($parameter = [])
    {

        return new OperationResult(true, TYPO3_version);
    }
}
