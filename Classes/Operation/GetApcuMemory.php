<?php

namespace WapplerSystems\ZabbixClient\Operation;

/*
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * Returns APCu shared memory and cache statistics of the PHP process that
 * serves the request (APCu is per SAPI, so the CLI sees a different, empty
 * cache - this has to be queried over HTTP).
 *
 * A full APCu is not a problem by itself; it becomes one when entries get
 * expunged. The counters (expunges, hits, misses, inserts) are cumulative
 * since start_time and are meant to be turned into rates in Zabbix.
 */
#[MonitoringOperation('GetApcuMemory')]
class GetApcuMemory implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        if (!function_exists('apcu_enabled') || !apcu_enabled()) {
            return new OperationResult(false, ['enabled' => false]);
        }

        $sma = @apcu_sma_info(true);
        $cache = @apcu_cache_info(true);
        if (!is_array($sma) || !is_array($cache)) {
            return new OperationResult(false, ['enabled' => false]);
        }

        $totalMemory = (int)($sma['num_seg'] ?? 0) * (int)($sma['seg_size'] ?? 0);
        $availMemory = (int)($sma['avail_mem'] ?? 0);
        $usedMemory = max(0, $totalMemory - $availMemory);
        $hits = (int)($cache['num_hits'] ?? 0);
        $misses = (int)($cache['num_misses'] ?? 0);
        $startTime = (int)($cache['start_time'] ?? 0);

        $value = [
            'enabled' => true,
            'memory' => [
                'total' => $totalMemory,
                'avail' => $availMemory,
                'used' => $usedMemory,
                'used_percent' => $totalMemory > 0 ? round($usedMemory * 100 / $totalMemory, 2) : 0.0,
                'free_percent' => $totalMemory > 0 ? round($availMemory * 100 / $totalMemory, 2) : 0.0,
            ],
            'cache' => [
                'num_entries' => (int)($cache['num_entries'] ?? 0),
                'num_slots' => (int)($cache['num_slots'] ?? 0),
                'ttl' => (int)($cache['ttl'] ?? 0),
                'hits' => $hits,
                'misses' => $misses,
                'inserts' => (int)($cache['num_inserts'] ?? 0),
                'expunges' => (int)($cache['expunges'] ?? 0),
                'hit_rate' => $hits + $misses > 0 ? round($hits * 100 / ($hits + $misses), 2) : 0.0,
                'start_time' => $startTime,
                'uptime' => $startTime > 0 ? max(0, time() - $startTime) : 0,
            ],
        ];

        return new OperationResult(true, $value);
    }
}
