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
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetEnvironmentStatus')]
class GetEnvironmentStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $ok = 0;
        $warning = 0;
        $error = 0;
        $errors = [];

        if (class_exists(\TYPO3\CMS\Install\SystemEnvironment\Check::class)) {
            try {
                $check = GeneralUtility::makeInstance(\TYPO3\CMS\Install\SystemEnvironment\Check::class);
                $statusMessages = $check->getStatus();

                foreach ($statusMessages as $status) {
                    $severity = $status->getSeverity();
                    switch ($severity) {
                        case 'error':
                            $error++;
                            $errors[] = $status->getTitle() . ': ' . $status->getMessage();
                            break;
                        case 'warning':
                            $warning++;
                            break;
                        default:
                            $ok++;
                            break;
                    }
                }

                return new OperationResult(true, [
                    'ok' => $ok,
                    'warning' => $warning,
                    'error' => $error,
                    'errors' => $errors,
                ]);
            } catch (\Throwable $e) {
                // Fall through to basic checks
            }
        }

        // Fallback: basic environment checks
        // PHP version check
        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            $ok++;
        } else {
            $error++;
            $errors[] = 'PHP version ' . PHP_VERSION . ' is below the minimum required 8.1.0';
        }

        // Required extensions
        $requiredExtensions = ['pdo', 'json', 'pcre', 'session', 'xml', 'filter', 'hash', 'mbstring', 'intl'];
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $ok++;
            } else {
                $error++;
                $errors[] = 'Required PHP extension "' . $ext . '" is not loaded';
            }
        }

        // Recommended extensions
        $recommendedExtensions = ['apcu', 'opcache', 'gd', 'zip'];
        foreach ($recommendedExtensions as $ext) {
            if (extension_loaded($ext)) {
                $ok++;
            } else {
                $warning++;
            }
        }

        // Memory limit check
        $memoryLimit = ini_get('memory_limit');
        if ($memoryLimit !== '-1') {
            $memoryBytes = $this->parseMemoryLimit($memoryLimit);
            if ($memoryBytes < 256 * 1024 * 1024) {
                $warning++;
            } else {
                $ok++;
            }
        } else {
            $ok++;
        }

        // max_execution_time check
        $maxExecTime = (int)ini_get('max_execution_time');
        if ($maxExecTime > 0 && $maxExecTime < 240) {
            $warning++;
        } else {
            $ok++;
        }

        return new OperationResult(true, [
            'ok' => $ok,
            'warning' => $warning,
            'error' => $error,
            'errors' => $errors,
        ]);
    }

    private function parseMemoryLimit(string $memoryLimit): int
    {
        $value = (int)$memoryLimit;
        $unit = strtolower(substr(trim($memoryLimit), -1));

        switch ($unit) {
            case 'g':
                $value *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $value *= 1024 * 1024;
                break;
            case 'k':
                $value *= 1024;
                break;
        }

        return $value;
    }
}
