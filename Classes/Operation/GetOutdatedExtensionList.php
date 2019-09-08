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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * An Operation that returns a list of outdated extensions
 *
 */
class GetOutdatedExtensionList implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter Array of extension locations as string (system, global, local)
     * @return OperationResult The extension list
     */
    public function execute($parameter = [])
    {
        $scope = $parameter['scope'];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ListUtility $listUtility */
        $listUtility = $objectManager->get(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $loadedOutdated = [];
        $existingOutdated = [];

        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('terObject', $information)
                && $information['terObject'] instanceof Extension
            ) {
                /** @var Extension $terObject */
                $terObject = $information['terObject'];
                $insecureStatus = $terObject->getReviewState();
                if ($insecureStatus === -2) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingOutdated[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                }
            }

        }

        if ($scope === 'loaded') {
            $exts = $loadedOutdated;
        } else {
            if ($scope === 'existing') {
                $exts = $existingOutdated;
            } else {
                $exts = array_merge($loadedOutdated, $existingOutdated);
            }
        }

        $out = '';
        foreach ($exts as $ext) {
            $out .= $ext['extensionKey'] . ',';
        }
        $out = substr($out, 0, -1);

        return new OperationResult(true, $out);
    }

}
