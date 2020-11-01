<?php

namespace WapplerSystems\ZabbixClient\Authorization;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Utility\Configuration;


class IpAuthorizationProvider
{
    /**
     * @param string $ip
     * @return bool
     */
    public function isAuthorized($ip)
    {
        $config = Configuration::getExtConfiguration();
        $allowedIps = trim($config['allowedIps'] ?? '');
        return !$allowedIps || GeneralUtility::cmpIP($ip, $allowedIps);
    }


}