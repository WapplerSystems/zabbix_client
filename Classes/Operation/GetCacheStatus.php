<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetCacheStatus')]
class GetCacheStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] ?? [];

        if (empty($cacheConfigurations)) {
            return new OperationResult(true, []);
        }

        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $result = [];

        foreach ($cacheConfigurations as $cacheName => $config) {
            $backendClass = $config['backend'] ?? \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;

            $cacheInfo = [
                'name' => $cacheName,
                'backend' => $backendClass,
                'reachable' => null,
            ];

            // Check reachability for Redis and Memcached backends
            $isRedis = stripos($backendClass, 'Redis') !== false;
            $isMemcached = stripos($backendClass, 'Memcached') !== false || stripos($backendClass, 'Memcache') !== false;

            if ($isRedis || $isMemcached) {
                try {
                    $cache = $cacheManager->getCache($cacheName);
                    // If we can get the cache frontend without exception, the backend is reachable
                    $cacheInfo['reachable'] = true;
                } catch (\Exception $e) {
                    $cacheInfo['reachable'] = false;
                    $cacheInfo['error'] = $e->getMessage();
                }
            }

            // Add backend options if present (sanitized)
            if (isset($config['options'])) {
                $options = $config['options'];
                // Remove sensitive data like passwords
                unset($options['password']);
                if (!empty($options)) {
                    $cacheInfo['options'] = $options;
                }
            }

            $result[] = $cacheInfo;
        }

        return new OperationResult(true, $result);
    }
}
