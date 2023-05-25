<?php
namespace WapplerSystems\ZabbixClient\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Information\Typo3Version;

class Configuration
{

    /**
     * @return array
     */
    public static function getExtConfiguration()
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
        if (version_compare($typo3Version->getVersion(), '9.0.0', '>=')) {

            return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('zabbix_client');
        }

        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['zabbix_client'])) {
            $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['zabbix_client']);

            return $extensionConfiguration;

        }
        return [];
    }
}