<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'])) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'] = [];
}
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'],[
    'CheckPathExists' => \WapplerSystems\ZabbixClient\Operation\CheckPathExists::class,
    'GetDiskSpace' => \WapplerSystems\ZabbixClient\Operation\GetDiskSpace::class,
    'GetExtensionList' => \WapplerSystems\ZabbixClient\Operation\GetExtensionList::class,
    'GetExtensionVersion' => \WapplerSystems\ZabbixClient\Operation\GetExtensionVersion::class,
    'GetFilesystemChecksum' => \WapplerSystems\ZabbixClient\Operation\GetFilesystemChecksum::class,
    'GetPHPVersion' => \WapplerSystems\ZabbixClient\Operation\GetPHPVersion::class,
    'GetTYPO3Version' => \WapplerSystems\ZabbixClient\Operation\GetTYPO3Version::class,
    'GetLogResults' => \WapplerSystems\ZabbixClient\Operation\GetLogResults::class,
    'HasForbiddenUsers' => \WapplerSystems\ZabbixClient\Operation\HasForbiddenUsers::class,
    'HasUpdate' => \WapplerSystems\ZabbixClient\Operation\HasUpdate::class,
    'HasSecurityUpdate' => \WapplerSystems\ZabbixClient\Operation\HasSecurityUpdate::class,
    'GetLastSchedulerRun' => \WapplerSystems\ZabbixClient\Operation\GetLastSchedulerRun::class,
    'GetLastExtensionListUpdate' => \WapplerSystems\ZabbixClient\Operation\GetLastExtensionListUpdate::class,
    'GetDatabaseVersion' => \WapplerSystems\ZabbixClient\Operation\GetDatabaseVersion::class,
    'GetApplicationContext' => \WapplerSystems\ZabbixClient\Operation\GetApplicationContext::class,
    'GetInsecureExtensionList' => \WapplerSystems\ZabbixClient\Operation\GetInsecureExtensionList::class,
    'GetOutdatedExtensionList' => \WapplerSystems\ZabbixClient\Operation\GetOutdatedExtensionList::class,
    'GetTotalLogFilesSize' => \WapplerSystems\ZabbixClient\Operation\GetTotalLogFilesSize::class,
    'HasStrictSyntaxEnabled' => \WapplerSystems\ZabbixClient\Operation\HasStrictSyntaxEnabled::class,
    'HasRemainingUpdates' => \WapplerSystems\ZabbixClient\Operation\HasRemainingUpdates::class,
    'GetZabbixLogFileSize' => \WapplerSystems\ZabbixClient\Operation\GetZabbixLogFileSize::class,
    'HasExtensionUpdate' => \WapplerSystems\ZabbixClient\Operation\HasExtensionUpdate::class,
    'GetProgramVersion' => \WapplerSystems\ZabbixClient\Operation\GetProgramVersion::class,
    'GetFeatureValue' => \WapplerSystems\ZabbixClient\Operation\GetFeatureValue::class,
]);

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('page_speed_insights')) {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations']['PageSpeedInsights_GetDegradedPageUids'] = \WapplerSystems\ZabbixClient\Operation\Extension\PageSpeedInsights\GetDegradedPageUids::class;
}


$GLOBALS['TYPO3_CONF_VARS']['LOG']['WapplerSystems']['ZabbixClient']['Middleware']['ZabbixClient']['writerConfiguration'] = [
    \TYPO3\CMS\Core\Log\LogLevel::WARNING => [
        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
            'logFileInfix' => 'zabbixclient'
        ],
    ],
];