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
 * Returns a "fingerprint" of a given path, can be used to check if a file or folder has been changed
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 *
 */
#[MonitoringOperation('GetFilesystemChecksum')]
class GetFilesystemChecksum implements IOperation, SingletonInterface
{
    /**
     * Get the file / folder checksum of a given path
     *
     * @param array $parameter Path to a file or folder
     * @return OperationResult The checksum of the given folder or file
     */
    public function execute($parameter = [])
    {
        $path = $this->getPath($parameter['path']);
        $getSingleChecksums = $this->getPath($parameter['getSingleChecksums']);

        $checksum = '';
        $md5s = null;

        if ($path !== false) {
            if (is_dir($path)) {
                list($checksum, $md5s) = $this->getFolderChecksum($path);
            } else {
                $checksum = $this->getFileChecksum($path);
            }
        }
        if (!empty($checksum)) {
            $result = [
                'checksum' => $checksum,
            ];
            if ($getSingleChecksums) {
                $result['singleChecksums'] = $md5s;
            }

            return new OperationResult(true, $result);
        }
        return new OperationResult(false, 'Error: can\'t calculate checksum for file or folder');
    }

    /**
     * Prepare path, resolve relative path and resolve EXT: path
     * check if path is allowed
     *
     * @param string $path absolute or relative path or EXT:foobar/
     * @return string|bool FALSE if path is invalid, else the absolute path
     */
    protected function getPath($path)
    {
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        // FIXME remove this hacky part
        // skip path checks for CLI mode
        if (defined('TYPO3_cliMode')) {
            return $path;
        }

        // getFileAbsFileName can't handle directory path with trailing / correctly
        $path = GeneralUtility::getFileAbsFileName($path);
        if (GeneralUtility::isAllowedAbsPath($path)) {
            return $path;
        }
        return false;
    }

    /**
     * Get a md5 checksum of a given file
     *
     * @param string $path file path
     * @return string/bool FALSE if path is not a file or md5 checksum of given file
     */
    protected function getFileChecksum($path)
    {
        if (!is_file($path)) {
            return false;
        }
        return md5_file($path);
    }

    /**
     * Get a md5 checksum of a given folder recursivly
     *
     * @param string $path path of folder
     * @return string checksum
     */
    protected function getFolderChecksum($path)
    {
        if (!is_dir($path)) {
            return $this->getFileChecksum($path);
        }
        $md5s = [];
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if ($entry === '.' || $entry === '..' || $entry === '.svn' || $entry === '.git') {
                continue;
            }
            if (is_dir($path . '/' . $entry)) {
                list($checksum, $md5sOfSubfolder) = $this->getFolderChecksum($path . '/' . $entry);
                $md5s = array_merge($md5s, $md5sOfSubfolder);
            } else {
                $relPath = str_replace(PATH_site, '', $path . '/' . $entry);
                $md5s[$relPath] = $this->getFileChecksum($path . '/' . $entry);
            }
        }

        asort($md5s);

        return [
            md5(implode(',', $md5s)),
            $md5s,
        ];
    }
}
