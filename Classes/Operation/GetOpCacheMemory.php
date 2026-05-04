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
 * Returns detailed OPcache memory and runtime statistics so individual
 * values (e.g. memory_free_percent, oom_restarts) can be driven into
 * separate Zabbix items via JSONPath preprocessing.
 */
#[MonitoringOperation('GetOpCacheMemory')]
class GetOpCacheMemory implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        if (!function_exists('opcache_get_status')) {
            return new OperationResult(false, ['enabled' => false]);
        }

        $status = @opcache_get_status(false);
        if (!is_array($status)) {
            return new OperationResult(false, ['enabled' => false]);
        }

        $memory = $status['memory_usage'] ?? [];
        $strings = $status['interned_strings_usage'] ?? [];
        $statistics = $status['opcache_statistics'] ?? [];

        $usedMemory = (int)($memory['used_memory'] ?? 0);
        $freeMemory = (int)($memory['free_memory'] ?? 0);
        $wastedMemory = (int)($memory['wasted_memory'] ?? 0);
        $totalMemory = $usedMemory + $freeMemory + $wastedMemory;
        $memoryFreePercent = $totalMemory > 0
            ? round($freeMemory * 100 / $totalMemory, 2)
            : 0.0;
        $memoryWastedPercent = isset($memory['current_wasted_percentage'])
            ? round((float)$memory['current_wasted_percentage'], 2)
            : ($totalMemory > 0 ? round($wastedMemory * 100 / $totalMemory, 2) : 0.0);

        $stringsUsed = (int)($strings['used_memory'] ?? 0);
        $stringsFree = (int)($strings['free_memory'] ?? 0);
        $stringsBuffer = (int)($strings['buffer_size'] ?? ($stringsUsed + $stringsFree));
        $stringsFreePercent = $stringsBuffer > 0
            ? round($stringsFree * 100 / $stringsBuffer, 2)
            : 0.0;

        $value = [
            'enabled' => (bool)($status['opcache_enabled'] ?? false),
            'cache_full' => (bool)($status['cache_full'] ?? false),
            'restart_pending' => (bool)($status['restart_pending'] ?? false),
            'restart_in_progress' => (bool)($status['restart_in_progress'] ?? false),
            'memory' => [
                'used' => $usedMemory,
                'free' => $freeMemory,
                'wasted' => $wastedMemory,
                'total' => $totalMemory,
                'free_percent' => $memoryFreePercent,
                'wasted_percent' => $memoryWastedPercent,
            ],
            'interned_strings' => [
                'used' => $stringsUsed,
                'free' => $stringsFree,
                'buffer' => $stringsBuffer,
                'free_percent' => $stringsFreePercent,
            ],
            'statistics' => [
                'num_cached_scripts' => (int)($statistics['num_cached_scripts'] ?? 0),
                'num_cached_keys' => (int)($statistics['num_cached_keys'] ?? 0),
                'max_cached_keys' => (int)($statistics['max_cached_keys'] ?? 0),
                'hits' => (int)($statistics['hits'] ?? 0),
                'misses' => (int)($statistics['misses'] ?? 0),
                'hit_rate' => isset($statistics['opcache_hit_rate'])
                    ? round((float)$statistics['opcache_hit_rate'], 2)
                    : 0.0,
                'oom_restarts' => (int)($statistics['oom_restarts'] ?? 0),
                'hash_restarts' => (int)($statistics['hash_restarts'] ?? 0),
                'manual_restarts' => (int)($statistics['manual_restarts'] ?? 0),
            ],
        ];

        return new OperationResult(true, $value);
    }
}
