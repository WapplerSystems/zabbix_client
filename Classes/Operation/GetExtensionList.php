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
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * An Operation that returns a list of installed extensions
 *
 * @author Martin Ficzel <martin@work.de>
 * @author Thomas Hempel <thomas@work.de>
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Tobias Liebig <liebig@networkteam.com>
 * @author Sven Wappler <typo3YYYY@wappler.systems>
 *
 */
class GetExtensionList implements IOperation, SingletonInterface
{
    /**
     * @var array Available extension scopes
     */
    protected $scopes = ['system', 'local'];

    /**
     *
     * @param array $parameter Array of extension locations as string (system, global, local)
     * @return OperationResult The extension list
     */
    public function execute($parameter = [])
    {
        $locations = explode(',', $parameter['scopes']);
        if (is_array($locations) && count($locations) > 0) {
            $extensionList = [];
            foreach ($locations as $scope) {
                if (in_array($scope, $this->scopes)) {
                    $extensionList = array_merge($extensionList, $this->getExtensionListForScope($scope));
                }
            }

            return new OperationResult(true, $extensionList);
        }
        return new OperationResult(false, 'No extension locations given');
    }

    /**
     * Get the path for the given scope
     *
     * @param string $scope
     * @return string
     */
    protected function getPathForScope($scope)
    {
        switch ($scope) {
            case 'system':
                if (version_compare(TYPO3_version, '9.0.0', '<')) {
                    $path = PATH_typo3 . 'sysext/';
                } else {
                    $path = Environment::getPublicPath() . '/typo3/sysext/';
                }
                break;
            case 'local':
            default:
                if (version_compare(TYPO3_version, '9.0.0', '<')) {
                    $path = PATH_typo3conf . 'ext/';
                } else {
                    $path = Environment::getPublicPath() . '/typo3conf/ext/';
                }
                break;
        }

        return $path;
    }

    /**
     * Get the list of extensions in the given scope
     *
     * @param string $scope
     * @return array
     */
    protected function getExtensionListForScope($scope)
    {
        $path = $this->getPathForScope($scope);
        $extensionInfo = [];
        if (@is_dir($path)) {
            $extensionFolders = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($path);
            if (is_array($extensionFolders)) {
                foreach ($extensionFolders as $extKey) {
                    $extensionInfo[$extKey]['ext_key'] = $extKey;
                    $extensionInfo[$extKey]['installed'] = (bool)\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extKey);

                    if (@is_file($path . $extKey . '/ext_emconf.php')) {
                        $_EXTKEY = $extKey;
                        @include($path . $extKey . '/ext_emconf.php');
                        $extensionVersion = $EM_CONF[$extKey]['version'];
                    } else {
                        $extensionVersion = false;
                    }

                    if ($extensionVersion) {
                        $extensionInfo[$extKey]['version'] = $extensionVersion;
                        $extensionInfo[$extKey]['scope'][$scope] = $extensionVersion;
                    }
                }
            }
        }

        return $extensionInfo;
    }
}
