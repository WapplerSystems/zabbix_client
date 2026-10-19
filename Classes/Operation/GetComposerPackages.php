<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Composer\InstalledVersions;
use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * Reports the installed composer packages with their versions so that an external
 * monitoring system can match them against a vulnerability database.
 *
 * Uses the composer runtime API instead of shelling out to the composer binary,
 * so it also works on hosting without exec() or a composer executable.
 */
#[MonitoringOperation('GetComposerPackages')]
class GetComposerPackages implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        if (!class_exists(InstalledVersions::class)) {
            return new OperationResult(false, 'Composer runtime API not available');
        }

        $includeDev = (bool)($parameter['includeDev'] ?? false);
        $types = $this->parseList($parameter['types'] ?? '');

        $raw = InstalledVersions::getAllRawData();
        $rootName = '';
        $packages = [];
        $unresolved = [];

        foreach ($raw as $installed) {
            $rootName = $installed['root']['name'] ?? $rootName;

            foreach ($installed['versions'] ?? [] as $name => $package) {
                if ($name === ($installed['root']['name'] ?? null)) {
                    // The project itself is not a published package.
                    continue;
                }
                if (!isset($package['pretty_version'])) {
                    // Replaced or provided virtual package - no real code, no version.
                    continue;
                }
                if (!$includeDev && ($package['dev_requirement'] ?? false)) {
                    continue;
                }
                if ($types !== [] && !in_array($package['type'] ?? '', $types, true)) {
                    continue;
                }

                $version = $this->normalizeVersion((string)$package['pretty_version']);

                if ($this->isUnresolvable($version)) {
                    // Branch installs have no comparable version. Reporting them as a
                    // release would make every vulnerability match, so they are listed
                    // separately as "not checkable".
                    $unresolved[$name] = $version;
                    continue;
                }

                $packages[$name] = $version;
            }
        }

        ksort($packages);
        ksort($unresolved);

        return new OperationResult(true, [
            'count' => count($packages),
            'packages' => $packages,
            'unresolved' => $unresolved,
            'root' => $rootName,
            'generated' => time(),
        ]);
    }

    /**
     * @return string[]
     */
    protected function parseList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    protected function normalizeVersion(string $version): string
    {
        $version = trim($version);

        // Composer keeps the tag as written, so "v13.4.16" and "13.4.16" both occur.
        if (preg_match('/^v\d/', $version) === 1) {
            $version = substr($version, 1);
        }

        return $version;
    }

    protected function isUnresolvable(string $version): bool
    {
        if ($version === '') {
            return true;
        }
        if (str_starts_with($version, 'dev-') || str_ends_with($version, '-dev')) {
            return true;
        }

        // e.g. the root package placeholder "1.0.0+no-version-set"
        return str_contains($version, '+no-version-set');
    }
}
