<?php


use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;


$GLOBALS['TYPO3_CONF_VARS']['LOG']['WapplerSystems']['ZabbixClient']['Middleware']['ZabbixClient']['writerConfiguration'] = [
    LogLevel::WARNING => [
        FileWriter::class => [
            'logFileInfix' => 'zabbixclient'
        ],
    ],
];
