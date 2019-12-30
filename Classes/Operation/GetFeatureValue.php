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
use TYPO3\CMS\Install\Configuration\AbstractPreset;
use TYPO3\CMS\Install\Configuration\Context\ContextFeature;
use TYPO3\CMS\Install\Configuration\Exception;
use TYPO3\CMS\Install\Configuration\Image\ImageFeature;
use TYPO3\CMS\Install\Configuration\Mail\MailFeature;
use TYPO3\CMS\Install\Configuration\PasswordHashing\PasswordHashingFeature;
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 *
 */
class GetFeatureValue implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        if (!isset($parameter['feature']) || $parameter['feature'] === '') {
            throw new InvalidArgumentException('feature not set');
        }

        switch (strtolower($parameter['feature'])) {
            case 'context':
                /** @var ContextFeature $feature */
                $feature = GeneralUtility::makeInstance(ContextFeature::class);
                break;
            case 'image':
                /** @var ImageFeature $feature */
                $feature = GeneralUtility::makeInstance(ImageFeature::class);
                break;
            case 'mail':
                /** @var MailFeature $feature */
                $feature = GeneralUtility::makeInstance(MailFeature::class);
                break;
            case 'passwordhashing':
                /** @var PasswordHashingFeature $feature */
                $feature = GeneralUtility::makeInstance(PasswordHashingFeature::class);
                break;
        }

        try {
            $feature->initializePresets([]);
            $presets = $feature->getPresetsOrderedByPriority();
            /** @var AbstractPreset $preset */
            foreach ($presets as $preset) {
                if ($preset->isActive()) {
                    return new OperationResult(true, $preset->getName());
                }
            }
        } catch (Exception $e) {
        }

        return new OperationResult(true, '');
    }
}
