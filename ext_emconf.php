<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Zabbix Client',
    'description' => 'Client for zabbix monitoring system. Secure up your TYPO3 Systems and detect errors and performance killers.',
    'category' => 'misc',
    'author' => 'Sven Wappler',
    'author_email' => 'typo3YYYY@wappler.systems',
    'state' => 'stable',
    'author_company' => 'WapplerSystems',
    'version' => '13.2.2',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
        ],
    ],
];
