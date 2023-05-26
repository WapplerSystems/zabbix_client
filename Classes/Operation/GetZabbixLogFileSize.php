<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Middleware\ZabbixClient;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * Return total log files size in KB
 */
#[MonitoringOperation('GetZabbixLogFileSize')]
class GetZabbixLogFileSize implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {
        $totalSize = 0;

        /** @var $logger Logger */
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(ZabbixClient::class);
        $writers = $logger->getWriters();

        $logFiles = [];

        foreach ($writers as $writers2) {
            foreach ($writers2 as $writer) {
                if ($writer instanceof \TYPO3\CMS\Core\Log\Writer\FileWriter) {
                    /** @var $write FileWriter */
                    $logFiles[$writer->getLogFile()] = $writer->getLogFile();
                }
            }
        }

        foreach ($logFiles as $logFile) {
            $totalSize += filesize($logFile);
        }

        $totalSize /= 1024;

        return new OperationResult(true, (int)$totalSize);
    }
}
