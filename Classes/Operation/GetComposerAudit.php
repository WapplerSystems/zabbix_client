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
        // Keep stderr out of stdout: composer writes warnings such as "could not detect
        // the root package version" to stderr, and merging them corrupts the JSON.
        $command = 'cd ' . escapeshellarg($projectPath) . ' && composer audit --format=json --no-interaction 2>/dev/null';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        // A non-zero exit code is not an error here: composer audit signals found
        // vulnerabilities that way. Only unparsable output means the call failed.
        $data = $this->decodeJson(implode("\n", $output));

        if (!is_array($data)) {
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
