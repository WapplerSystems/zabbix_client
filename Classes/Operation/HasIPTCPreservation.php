<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 * Check if strict syntax is enabled
 *
 */
class HasIPTCPreservation implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {
        return new OperationResult(true, (bool)array_search('!iptc', $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters'] ?? []) !== false);
    }
}
