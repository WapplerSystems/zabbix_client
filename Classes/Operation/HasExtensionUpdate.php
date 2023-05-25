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
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 */
class HasExtensionUpdate implements IOperation, SingletonInterface
{
    /**
     * @var ListUtility
     */
    private $listUtility;

    public function injectListUtility(ListUtility $listUtility)
    {
        $this->listUtility = $listUtility;
    }
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

        $extensionInformation = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();

        if (isset($extensionInformation[$extensionKey]['updateAvailable'])) {
            return new OperationResult(true, (boolean)$extensionInformation[$extensionKey]['updateAvailable']);
        }

        return new OperationResult(false, false);
    }
}
