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

#[MonitoringOperation('HasInsecureConfiguration')]
class HasInsecureConfiguration implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $issues = [];
        $isProduction = Environment::getContext()->isProduction();

        // Check trustedHostsPattern
        $trustedHostsPattern = $GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] ?? '';
        if ($trustedHostsPattern === '.*') {
            $issues[] = 'SYS/trustedHostsPattern is set to ".*" (accepts any host)';
        }

        // Check BE/lockSSL
        $lockSSL = $GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] ?? 0;
        if ((int)$lockSSL !== 1) {
            $issues[] = 'BE/lockSSL is not enabled';
        }

        // Check SYS/displayErrors (Production only)
        if ($isProduction) {
            $displayErrors = $GLOBALS['TYPO3_CONF_VARS']['SYS']['displayErrors'] ?? 0;
            if ((int)$displayErrors === 1) {
                $issues[] = 'SYS/displayErrors is enabled in Production context';
            }
        }

        // Check SYS/devIPmask (Production only)
        if ($isProduction) {
            $devIPmask = $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] ?? '';
            if (!empty($devIPmask)) {
                $issues[] = 'SYS/devIPmask is set in Production context: ' . $devIPmask;
            }
        }

        // Check FE/debug
        $feDebug = $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] ?? false;
        if ($feDebug) {
            $issues[] = 'FE/debug is enabled';
        }

        // Check BE/debug
        $beDebug = $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] ?? false;
        if ($beDebug) {
            $issues[] = 'BE/debug is enabled';
        }

        return new OperationResult(true, $issues);
    }
}
