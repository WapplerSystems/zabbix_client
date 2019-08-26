<?php
return [
    'frontend' => [
        'wapplersystems/zabbix-client' => [
            'target' => \WapplerSystems\ZabbixClient\Middleware\ZabbixClient::class,
            'before' => [
                'typo3/cms-frontend/tsfe',
            ]
        ],
    ]
];
