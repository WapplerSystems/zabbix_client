<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Zabbix Client',
    'description' => 'Client for zabbix monitoring system. Secure up your TYPO3 Systems and detect errors and performance killers.',
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
    'version' => '0.2.14',
    'constraints' => [
        'depends' => [
            'typo3' => '8.7.0-11.5.99',
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
];
