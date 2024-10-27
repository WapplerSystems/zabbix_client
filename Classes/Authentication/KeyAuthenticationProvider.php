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
     * @param $key
     * @return bool
     */
    public function hasValidKey($key): bool
    {
        $config = Configuration::getExtConfiguration();
        return trim($config['apiKey']) === trim($key);
    }


}
