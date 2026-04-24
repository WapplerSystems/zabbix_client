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

#[MonitoringOperation('GetSSLCertificateExpiry')]
class GetSSLCertificateExpiry implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $singleDomain = $parameter['domain'] ?? null;

        if ($singleDomain !== null) {
            $days = $this->getCertificateExpiryDays($singleDomain);
            return new OperationResult(true, [$singleDomain => $days]);
        }

        $domains = [];

        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $sites = $siteFinder->getAllSites();

            foreach ($sites as $site) {
                $base = (string)$site->getBase();
                $parsed = parse_url($base);
                if (isset($parsed['host']) && !empty($parsed['host'])) {
                    $host = $parsed['host'];
                    if (!isset($domains[$host])) {
                        $domains[$host] = null;
                    }
                }
            }
        } catch (\Exception $e) {
            return new OperationResult(false, 'Could not retrieve site configurations: ' . $e->getMessage());
        }

        if (empty($domains)) {
            return new OperationResult(true, []);
        }

        $result = [];
        foreach (array_keys($domains) as $domain) {
            $result[$domain] = $this->getCertificateExpiryDays($domain);
        }

        return new OperationResult(true, $result);
    }

    private function getCertificateExpiryDays(string $domain): int|string
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            'ssl://' . $domain . ':443',
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client === false) {
            return 'Connection failed: ' . $errstr;
        }

        $params = stream_context_get_params($client);
        fclose($client);

        if (!isset($params['options']['ssl']['peer_certificate'])) {
            return 'No certificate found';
        }

        $certInfo = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
        if ($certInfo === false || !isset($certInfo['validTo_time_t'])) {
            return 'Could not parse certificate';
        }

        $expiryTimestamp = $certInfo['validTo_time_t'];
        $daysUntilExpiry = (int)floor(($expiryTimestamp - time()) / 86400);

        return $daysUntilExpiry;
    }
}
