<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\ComposerRunException;
use WapplerSystems\ZabbixClient\OperationResult;
use WapplerSystems\ZabbixClient\Service\ComposerRunner;

/**
 * Runs "composer audit" on every call, so the result reflects the advisories known right now.
 *
 * Optional parameter: timeout (seconds, default 50).
 */
#[MonitoringOperation('GetComposerAudit')]
class GetComposerAudit implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $timeout = max(5, (int)($parameter['timeout'] ?? ComposerRunner::DEFAULT_TIMEOUT));

        try {
            // A non-zero exit code is not an error here: composer audit signals found
            // vulnerabilities that way. Only missing JSON means the call failed.
            $data = GeneralUtility::makeInstance(ComposerRunner::class)
                ->runJson(['audit', '--format=json'], $timeout);
        } catch (ComposerRunException $e) {
            return new OperationResult(false, 'Cannot run composer audit: ' . $e->getMessage());
        }

        $advisories = [];

        if (isset($data['advisories']) && is_array($data['advisories'])) {
            foreach ($data['advisories'] as $packageName => $packageAdvisories) {
                if (is_array($packageAdvisories)) {
                    foreach ($packageAdvisories as $advisory) {
                        $advisories[] = [
                            'package' => $advisory['packageName'] ?? $packageName,
                            'cve' => $advisory['cve'] ?? $advisory['advisoryId'] ?? '',
                            'title' => $advisory['title'] ?? '',
                            'affectedVersions' => $advisory['affectedVersions'] ?? '',
                            'link' => $advisory['link'] ?? '',
                        ];
                    }
                }
            }
        }

        $abandoned = [];
        if (isset($data['abandoned']) && is_array($data['abandoned'])) {
            foreach ($data['abandoned'] as $packageName => $replacement) {
                $abandoned[] = [
                    'package' => $packageName,
                    'replacement' => is_string($replacement) ? $replacement : '',
                ];
            }
        }

        return new OperationResult(true, [
            'vulnerabilities' => count($advisories),
            'advisories' => $advisories,
            'abandonedCount' => count($abandoned),
            'abandoned' => $abandoned,
        ]);
    }
}
