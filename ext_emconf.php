<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Zabbix Client',
    'description' => 'Client for zabbix monitoring system. Secure up your TYPO3 Systems and detect errors.',
    'category' => 'misc',
    'author' => 'Sven Wappler',
    'author_email' => 'typo3YYYY@wappler.systems',
    'shy' => '',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'author_company' => 'WapplerSystems',
    'version' => '0.2.1',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-10.0.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'autoload' => [
        'classmap' => [
            'Classes',
        ],
    ],
    '_md5_values_when_last_written' => '',
];
