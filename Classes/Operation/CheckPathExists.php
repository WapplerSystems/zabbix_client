<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * Checks wether the given path exists or not
 *
 * @author Felix Oertel <oertel@networkteam.com>
 *
 */
#[MonitoringOperation('CheckPathExists')]
class CheckPathExists implements IOperation, SingletonInterface
{
    /**
     * execute operation (checkPathExists)
     *
     * @param array $parameter a path 'path' to a file or folder
     * @return OperationResult 'file' if path is a file, 'directory' if it's a directory and false if it doesn't exist
     */
    public function execute($parameter = null)
    {
        $path = $this->getPath($parameter['path']);
        list($path) = glob($path);

        if (is_file($path)) {
            //if file exists, get the tstamp
            $time = filemtime($path);
            $size = filesize($path);

            return new OperationResult(true, [
                'type' => 'file',
                'path' => $parameter,
                'time' => $time,
                'size' => $size,
            ]);
        }

        if (is_dir($path)) {
            return new OperationResult(true, [
                'type' => 'folder',
                'path' => $parameter,
            ]);
        }
        return new OperationResult(false, ['path' => $parameter]);
    }

    /**
     * prepare path, resolve relative path and resolve EXT: path
     *
     * @param string $path absolute or relative path or EXT:foobar/
     * @return string/bool false if path is invalid, else the absolute path
     */
    protected function getPath($path)
    {
        // getFileAbsFileName can't handle directory path with trailing / correctly
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        // FIXME remove this hacky part
        // skip path checks for CLI mode
        if (defined('TYPO3_cliMode')) {
            return $path;
        }

        $path = GeneralUtility::getFileAbsFileName($path);
        if (GeneralUtility::isAllowedAbsPath($path)) {
            return $path;
        }
        return false;
    }
}
