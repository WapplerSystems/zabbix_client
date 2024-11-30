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
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 * Check if strict syntax is enabled
 *
 */
#[MonitoringOperation('HasIPTCPreservation')]
class HasIPTCPreservation implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters'])) {
            foreach($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileParameters'] as $index => $key) {
                if (strpos($key, '!iptc') !== false) return new OperationResult(true, true);
            }
        }
        return new OperationResult(true, strpos($GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_stripColorProfileCommand'] ?? '','!iptc') !== false);
    }
}
