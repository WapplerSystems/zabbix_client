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


/**
 * Return total log files size in KB
 */
class GetTotalLogFilesSize implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {
        $totalSize = 0;

        if (version_compare(TYPO3_version, '9.0.0', '<')) {
            $files = GeneralUtility::getFilesInDir(PATH_site . 'typo3temp/var/log/', 'log');
            foreach ($files as $file) {
                $totalSize += filesize(PATH_site . 'typo3temp/var/log/' . $file);
            }
        } else {
            $files = GeneralUtility::getFilesInDir(Environment::getVarPath() . '/log/', 'log');
            foreach ($files as $file) {
                $totalSize += filesize(Environment::getVarPath() . '/log/' . $file);
            }
        }

        $totalSize /= 1024;

        return new OperationResult(true, (int)$totalSize);
    }
}
