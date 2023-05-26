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
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\OperationResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 */
#[MonitoringOperation('HasExtensionUpdate')]
class HasExtensionUpdate implements IOperation, SingletonInterface
{
    /**
     *
     * @param array $parameter None
     * @return OperationResult
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

        /**
         * @var ListUtility
         */
        $listUtility = GeneralUtility::makeInstance(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();

        if (isset($extensionInformation[$extensionKey]['updateAvailable'])) {
            return new OperationResult(true, (boolean)$extensionInformation[$extensionKey]['updateAvailable']);
        }

        return new OperationResult(false, false);
    }
}
