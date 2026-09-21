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

#[MonitoringOperation('GetComposerOutdated')]
class GetComposerOutdated implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        if (!function_exists('exec')) {
            return new OperationResult(false, 'Cannot run composer outdated');
        }

        $direct = !isset($parameter['direct']) || (bool)$parameter['direct'];

        $projectPath = Environment::getProjectPath();
        // Keep stderr out of stdout: composer writes warnings such as "could not detect
        // the root package version" to stderr, and merging them corrupts the JSON.
        $command = 'cd ' . escapeshellarg($projectPath) . ' && composer outdated --format=json --no-interaction'
            . ($direct ? ' --direct' : '') . ' 2>/dev/null';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $data = $this->decodeJson(implode("\n", $output));

        if (!is_array($data)) {
            return new OperationResult(false, 'Cannot run composer outdated');
        }

        $packages = [];

        if (isset($data['installed']) && is_array($data['installed'])) {
            foreach ($data['installed'] as $package) {
                $packages[] = [
                    'package' => $package['name'] ?? '',
                    'version' => $package['version'] ?? '',
                    'latest' => $package['latest'] ?? '',
                    'status' => $package['latest-status'] ?? '',
                ];
            }
        }

        return new OperationResult(true, [
            'outdatedCount' => count($packages),
            'direct' => $direct,
            'packages' => $packages,
        ]);
    }

    /**
     * Decodes the JSON document from a command output, tolerating leading noise.
     */
    protected function decodeJson(string $output): ?array
    {
        $start = strcspn($output, '{[');
        if ($start >= strlen($output)) {
            return null;
        }

        $data = json_decode(substr($output, $start), true);

        return is_array($data) ? $data : null;
    }
}
