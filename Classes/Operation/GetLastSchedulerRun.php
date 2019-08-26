<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\OperationResult;


class GetLastSchedulerRun implements IOperation, SingletonInterface
{

    public function execute($parameter = [])
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);

        $lastRun = $registry->get('tx_scheduler', 'lastRun', []);
        if (isset($lastRun['end'])) {
            return new OperationResult(true, $lastRun['end']);
        }
        return new OperationResult(true, $lastRun);
    }

}