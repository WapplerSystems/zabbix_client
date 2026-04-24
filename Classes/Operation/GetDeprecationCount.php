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

#[MonitoringOperation('GetDeprecationCount')]
class GetDeprecationCount implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $days = isset($parameter['days']) ? (int)$parameter['days'] : 7;
        $cutoffTimestamp = time() - ($days * 86400);

        $logPath = Environment::getVarPath() . '/log/';
        $count = 0;
        $totalSizeKB = 0;

        if (!is_dir($logPath)) {
            return new OperationResult(true, [
                'count' => 0,
                'logFileSizeKB' => 0,
                'days' => $days,
            ]);
        }

        $files = glob($logPath . 'typo3_deprecations_*.log');
        if ($files === false || empty($files)) {
            return new OperationResult(true, [
                'count' => 0,
                'logFileSizeKB' => 0,
                'days' => $days,
            ]);
        }

        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $fileSize = filesize($file);
            $totalSizeKB += $fileSize / 1024;

            // For large files (> 10MB), use wc -l and estimate based on file modification time
            if ($fileSize > 10 * 1024 * 1024) {
                // If file was modified before cutoff, skip it entirely
                if (filemtime($file) < $cutoffTimestamp) {
                    continue;
                }

                if (function_exists('exec')) {
                    $lineCount = 0;
                    exec('wc -l ' . escapeshellarg($file), $output, $returnCode);
                    if ($returnCode === 0 && !empty($output[0])) {
                        $lineCount = (int)trim($output[0]);
                    }

                    // Estimate: if file was modified recently, assume lines are roughly
                    // distributed over the file age
                    $fileAge = time() - filectime($file);
                    if ($fileAge > 0 && $fileAge > $days * 86400) {
                        $ratio = ($days * 86400) / $fileAge;
                        $count += (int)ceil($lineCount * $ratio);
                    } else {
                        $count += $lineCount;
                    }
                }
                continue;
            }

            // For smaller files, read and check timestamps line by line
            $handle = fopen($file, 'r');
            if ($handle === false) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // TYPO3 deprecation log format typically starts with a date like
                // "Tue, 01 Jan 2025 12:00:00 +0000" or ISO format "2025-01-01T12:00:00+00:00"
                // or component-based format "- Component: ..." preceded by date
                $timestamp = $this->extractTimestamp($line);
                if ($timestamp !== null && $timestamp >= $cutoffTimestamp) {
                    $count++;
                } elseif ($timestamp === null) {
                    // If we can't parse a timestamp, count the line if the file
                    // was modified within the time window
                    if (filemtime($file) >= $cutoffTimestamp) {
                        $count++;
                    }
                }
            }

            fclose($handle);
        }

        return new OperationResult(true, [
            'count' => $count,
            'logFileSizeKB' => round($totalSizeKB, 2),
            'days' => $days,
        ]);
    }

    private function extractTimestamp(string $line): ?int
    {
        // Try ISO 8601 format: 2025-01-01T12:00:00+00:00
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+\-]\d{2}:\d{2})/', $line, $matches)) {
            $ts = strtotime($matches[1]);
            return $ts !== false ? $ts : null;
        }

        // Try RFC 2822 format: Tue, 01 Jan 2025 12:00:00 +0000
        if (preg_match('/^([A-Z][a-z]{2}, \d{2} [A-Z][a-z]{2} \d{4} \d{2}:\d{2}:\d{2} [+\-]\d{4})/', $line, $matches)) {
            $ts = strtotime($matches[1]);
            return $ts !== false ? $ts : null;
        }

        // Try common log format with brackets: [2025-01-01 12:00:00]
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $ts = strtotime($matches[1]);
            return $ts !== false ? $ts : null;
        }

        return null;
    }
}
