<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetComposerAudit')]
class GetComposerAudit implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        if (!function_exists('exec')) {
            return new OperationResult(false, 'Cannot run composer audit');
        }

        $projectPath = Environment::getProjectPath();
        $command = 'cd ' . escapeshellarg($projectPath) . ' && composer audit --format=json --no-interaction 2>&1';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $jsonOutput = implode("\n", $output);
        $data = json_decode($jsonOutput, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            return new OperationResult(false, 'Cannot run composer audit');
        }

        $advisories = [];
        $vulnerabilityCount = 0;

        if (isset($data['advisories']) && is_array($data['advisories'])) {
            foreach ($data['advisories'] as $packageName => $packageAdvisories) {
                if (is_array($packageAdvisories)) {
                    foreach ($packageAdvisories as $advisory) {
                        $vulnerabilityCount++;
                        $advisories[] = [
                            'package' => $packageName,
                            'cve' => $advisory['cve'] ?? $advisory['advisoryId'] ?? '',
                            'title' => $advisory['title'] ?? '',
                        ];
                    }
                }
            }
        }

        return new OperationResult(true, [
            'vulnerabilities' => $vulnerabilityCount,
            'advisories' => $advisories,
        ]);
    }
}
