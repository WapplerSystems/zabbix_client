<?php

namespace WapplerSystems\ZabbixClient\Authentication;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use WapplerSystems\ZabbixClient\Utility\Configuration;


class KeyAuthenticationProvider
{

    /**
     * True if an API key is configured in the extension settings.
     */
    public function isKeyConfigured(): bool
    {
        return $this->getConfiguredKey() !== '';
    }

    /**
     * @param mixed $key key supplied with the request
     * @return bool
     */
    public function hasValidKey($key): bool
    {
        return self::keysMatch($this->getConfiguredKey(), $key);
    }

    /**
     * Deny by default: without a configured key no request authenticates,
     * otherwise an empty request key would match an empty configured key.
     */
    public static function keysMatch(string $configuredKey, mixed $key): bool
    {
        $configuredKey = trim($configuredKey);
        if ($configuredKey === '' || !is_string($key)) {
            return false;
        }
        return hash_equals($configuredKey, trim($key));
    }

    private function getConfiguredKey(): string
    {
        try {
            $config = Configuration::getExtConfiguration();
        } catch (\Exception) {
            return '';
        }
        return is_array($config) && is_scalar($config['apiKey'] ?? null) ? trim((string)$config['apiKey']) : '';
    }

}
