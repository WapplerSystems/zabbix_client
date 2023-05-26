<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * An Operation that returns the version of an installed extension
 *
 */
#[MonitoringOperation('GetExtensionVersion')]
class GetExtensionVersion implements IOperation, SingletonInterface
{
    /**
     * Get the extension version of the given extension by extension key
     *
     * @param array $parameter None
     * @return OperationResult The extension version
     */
    public function execute($parameter = [])
    {
        if (!isset($parameter['extensionKey']) || $parameter['extensionKey'] === '') {
            throw new InvalidArgumentException('no extensionKey set');
        }

        $extensionKey = $parameter['extensionKey'];

        if (!ExtensionManagementUtility::isLoaded($extensionKey)) {
            return new OperationResult(false, 'Extension [' . $extensionKey . '] is not loaded');
        }

        $_EXTKEY = $extensionKey;
        @include(ExtensionManagementUtility::extPath($extensionKey, 'ext_emconf.php'));

        if (is_array($EM_CONF[$extensionKey])) {
            return new OperationResult(true, $EM_CONF[$extensionKey]['version']);
        }
        return new OperationResult(false, 'Cannot read EM_CONF for extension [' . $extensionKey . ']');
    }
}
