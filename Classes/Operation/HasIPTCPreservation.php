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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Imaging\GraphicalFunctions;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 * Check if strict syntax is enabled
 *
 */
#[MonitoringOperation('HasIPTCPreservation')]
class HasIPTCPreservation implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute(array $parameter = []): OperationResult
    {
        $imageBasePath = ExtensionManagementUtility::extPath('zabbix_client') . 'Resources/Private/TestInput/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestIPTC.jpg';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('iptc');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '200', '', '', '', [], true);
        if ($imResult !== null && file_exists($imResult[3])) {
            $metaData = $imageProcessor->imageMagickMetadata($imResult[3]);
            unlink($imResult[3]);
            foreach ($metaData as $value) {
                if (str_contains($value, 'Test-Image')) {
                    return new OperationResult(true, true);
                }
            }
        }
        return new OperationResult(true, false);
    }


    /**
     * Initialize image processor
     *
     * @return GraphicalFunctions Initialized image processor
     */
    protected function initializeImageProcessor(): GraphicalFunctions
    {
        $imageProcessor = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageProcessor->dontCheckForExistingTempFile = true;
        $imageProcessor->filenamePrefix = 'zabbixClient-';
        $imageProcessor->alternativeOutputKey = 'zabbixClienTest';
        return $imageProcessor;
    }

}
