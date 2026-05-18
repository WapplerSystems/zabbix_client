<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Zabbix Client',
    'description' => 'Client for zabbix monitoring system. Secure up your TYPO3 Systems and detect errors and performance killers.',
    'category' => 'misc',
    'author' => 'Sven Wappler',
    'author_email' => 'typo3YYYY@wappler.systems',
    'state' => 'stable',
    'author_company' => 'WapplerSystems',
    'version' => '14.2.1',
    'constraints' => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
        ],
    ],
];
