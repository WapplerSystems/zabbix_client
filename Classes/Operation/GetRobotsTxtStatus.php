<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetRobotsTxtStatus')]
class GetRobotsTxtStatus implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        try {
            $url = $parameter['url'] ?? null;

            if ($url === null) {
                $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                $sites = $siteFinder->getAllSites();
                $site = reset($sites);

                if ($site === false) {
                    return new OperationResult(false, 'No site configuration found');
                }

                $baseUrl = rtrim((string)$site->getBase(), '/');
                $url = $baseUrl . '/robots.txt';
            }

            $content = GeneralUtility::getUrl($url);

            if ($content === false) {
                return new OperationResult(true, [
                    'accessible' => false,
                    'blockingAll' => false,
                    'content' => '',
                ]);
            }

            // Check if robots.txt blocks all crawlers
            $blockingAll = false;
            $lines = explode("\n", $content);
            $inUserAgentAll = false;

            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^User-agent:\s*\*\s*$/i', $line)) {
                    $inUserAgentAll = true;
                } elseif (preg_match('/^User-agent:/i', $line)) {
                    $inUserAgentAll = false;
                } elseif ($inUserAgentAll && preg_match('/^Disallow:\s*\/\s*$/i', $line)) {
                    $blockingAll = true;
                    break;
                }
            }

            return new OperationResult(true, [
                'accessible' => true,
                'blockingAll' => $blockingAll,
                'content' => substr($content, 0, 500),
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error checking robots.txt: ' . $e->getMessage());
        }
    }
}
