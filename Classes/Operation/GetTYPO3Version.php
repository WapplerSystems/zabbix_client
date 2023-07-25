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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

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
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        return new OperationResult(true, $typo3Version->getVersion());
    }
}
