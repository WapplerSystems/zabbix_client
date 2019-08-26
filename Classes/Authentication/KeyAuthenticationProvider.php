<?php

namespace WapplerSystems\ZabbixClient\Authentication;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;


class KeyAuthenticationProvider
{

    /**
     * @param $key
     * @return bool
     */
    public function hasValidKey($key)
    {
        $config = $this->getExtConfiguration();
        return $config['apiKey'] === $key;
    }


    /**
     * @return array
     */
    private function getExtConfiguration()
    {

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['zabbix_client'])) {
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['zabbix_client']);

            return $extensionConfiguration;

        }
        return [];
    }


    /**
     * Get the whole typoscript array
     * @return array
     */
    private function getTypoScriptConfiguration(): array
    {
        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)
            ->get(ConfigurationManagerInterface::class);

        return $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'zabbix_client'
        );
    }


}