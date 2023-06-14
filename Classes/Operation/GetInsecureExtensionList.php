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
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * An Operation that returns a list of insecure extensions
 *
 */
#[MonitoringOperation('GetInsecureExtensionList')]
class GetInsecureExtensionList implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter Array of extension locations as string (loaded, existing)
     * @return OperationResult The extension list
     */
    public function execute($parameter = [])
    {
        $scope = $parameter['scope'] ?? '';

        /**
         * @var $listUtility ListUtility
         */
        $listUtility = GeneralUtility::makeInstance(ListUtility::class);
        $extensionInformation = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $loadedInsecure = [];
        $existingInsecure = [];

        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('terObject', $information)
                && $information['terObject'] instanceof Extension
            ) {
                /** @var Extension $terObject */
                $terObject = $information['terObject'];
                $insecureStatus = $terObject->getReviewState();
                if ($insecureStatus === -1) {
                    if (
                        array_key_exists('installed', $information)
                        && $information['installed'] === true
                    ) {
                        $loadedInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    } else {
                        $existingInsecure[] = [
                            'extensionKey' => $extensionKey,
                            'version' => $terObject->getVersion(),
                        ];
                    }
                }
            }

        }

        if ($scope === 'loaded') {
            $exts = $loadedInsecure;
        } else {
            if ($scope === 'existing') {
                $exts = $existingInsecure;
            } else {
                $exts = array_merge($loadedInsecure, $existingInsecure);
            }
        }

        if (count($exts) === 0) {
            return new OperationResult(true, false);
        }

        $out = '';
        foreach ($exts as $ext) {
            $out .= $ext['extensionKey'] . ',';
        }
        $out = substr($out, 0, -1);

        return new OperationResult(true, $out);
    }

}
