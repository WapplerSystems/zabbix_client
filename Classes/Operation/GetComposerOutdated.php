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
 * Runs "composer outdated" on every call. The command queries every configured repository,
 * which takes 10-30 seconds; the Zabbix item needs a matching timeout.
 *
 * Optional parameters: direct (default 1, only packages required in composer.json),
 * timeout (seconds, default 50).
 */
#[MonitoringOperation('GetComposerOutdated')]
class GetComposerOutdated implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $direct = !isset($parameter['direct']) || (bool)$parameter['direct'];
        $timeout = max(5, (int)($parameter['timeout'] ?? ComposerRunner::DEFAULT_TIMEOUT));

        $arguments = ['outdated', '--format=json'];
        if ($direct) {
            $arguments[] = '--direct';
        }

        try {
            $data = GeneralUtility::makeInstance(ComposerRunner::class)->runJson($arguments, $timeout);
        } catch (ComposerRunException $e) {
            return new OperationResult(false, 'Cannot run composer outdated: ' . $e->getMessage());
        }

        $packages = [];
        $counts = ['major' => 0, 'minor' => 0, 'patch' => 0];

        if (isset($data['installed']) && is_array($data['installed'])) {
            foreach ($data['installed'] as $package) {
                if (!is_array($package) || !$this->hasNewerVersion($package)) {
                    continue;
                }
                $version = (string)($package['version'] ?? '');
                $latest = (string)($package['latest'] ?? '');
                $level = $this->getUpdateLevel($version, $latest);
                if ($level !== null) {
                    $counts[$level]++;
                }
                $packages[] = [
                    'package' => $package['name'] ?? '',
                    'version' => $version,
                    'latest' => $latest,
                    // "semver-safe-update" = within the constraint, "update-possible" = needs a constraint change
                    'status' => $package['latest-status'] ?? '',
                    'level' => $level ?? '',
                    'abandoned' => (bool)($package['abandoned'] ?? false),
                ];
            }
        }

        return new OperationResult(true, [
            'outdatedCount' => count($packages),
            'majorCount' => $counts['major'],
            'minorCount' => $counts['minor'],
            'patchCount' => $counts['patch'],
            'direct' => $direct,
            'packages' => $packages,
        ]);
    }

    /**
     * "composer outdated" also lists packages for which no candidate exists at all, e.g. path
     * repositories (packages/*) or branches no repository offers: latest "[none matched]",
     * latest-status "up-to-date". Those are not updates.
     */
    protected function hasNewerVersion(array $package): bool
    {
        $latest = (string)($package['latest'] ?? '');
        if ($latest === '' || str_starts_with($latest, '[')) {
            return false;
        }

        return ($package['latest-status'] ?? '') !== 'up-to-date';
    }

    /**
     * Classifies an update as major, minor or patch; null for branch installs.
     */
    protected function getUpdateLevel(string $version, string $latest): ?string
    {
        $current = $this->parseVersion($version);
        $target = $this->parseVersion($latest);
        if ($current === null || $target === null) {
            return null;
        }
        if ($target[0] !== $current[0]) {
            return 'major';
        }
        if ($target[1] !== $current[1]) {
            return 'minor';
        }

        return 'patch';
    }

    /**
     * @return int[]|null
     */
    protected function parseVersion(string $version): ?array
    {
        if (preg_match('/^v?(\d+)(?:\.(\d+))?(?:\.(\d+))?/', $version, $matches) !== 1) {
            return null;
        }

        return [(int)$matches[1], (int)($matches[2] ?? 0), (int)($matches[3] ?? 0)];
    }
}
