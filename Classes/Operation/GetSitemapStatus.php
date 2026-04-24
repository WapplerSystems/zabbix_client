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

#[MonitoringOperation('GetSitemapStatus')]
class GetSitemapStatus implements IOperation, SingletonInterface
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
                $url = $baseUrl . '/sitemap.xml';
            }

            $content = GeneralUtility::getUrl($url);

            if ($content === false) {
                return new OperationResult(true, [
                    'accessible' => false,
                    'urlCount' => 0,
                    'url' => $url,
                ]);
            }

            $urlCount = 0;
            if (preg_match_all('/<loc>/i', $content, $matches)) {
                $urlCount = count($matches[0]);
            }

            return new OperationResult(true, [
                'accessible' => true,
                'urlCount' => $urlCount,
                'url' => $url,
            ]);
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error checking sitemap: ' . $e->getMessage());
        }
    }
}
