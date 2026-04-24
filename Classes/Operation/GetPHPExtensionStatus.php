<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetPHPExtensionStatus')]
class GetPHPExtensionStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $defaultExtensions = 'intl,gd,zip,json,mbstring,xml,pdo,fileinfo,openssl,curl,sodium';
        $required = $parameter['required'] ?? $defaultExtensions;

        $extensionNames = array_map('trim', explode(',', $required));

        $loaded = [];
        $missing = [];

        foreach ($extensionNames as $ext) {
            if ($ext === '') {
                continue;
            }
            if (extension_loaded($ext)) {
                $loaded[] = $ext;
            } else {
                $missing[] = $ext;
            }
        }

        return new OperationResult(true, [
            'loaded' => $loaded,
            'missing' => $missing,
        ]);
    }
}
