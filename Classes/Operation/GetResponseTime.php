<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

#[MonitoringOperation('GetResponseTime')]
class GetResponseTime implements IOperation, SingletonInterface
{
    public function execute(array $parameter = []): OperationResult
    {
        $url = $parameter['url'] ?? '';

        if (empty($url)) {
            return new OperationResult(false, 'Parameter "url" is required');
        }

        if (function_exists('curl_init')) {
            return $this->measureWithCurl($url);
        }

        return $this->measureWithFileGetContents($url);
    }

    private function measureWithCurl(string $url): OperationResult
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'TYPO3 ZabbixClient Monitor',
        ]);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return new OperationResult(false, 'cURL error: ' . $error);
        }

        $responseTimeMs = round(($endTime - $startTime) * 1000, 2);

        return new OperationResult(true, [
            'url' => $url,
            'responseTimeMs' => $responseTimeMs,
            'httpCode' => $httpCode,
        ]);
    }

    private function measureWithFileGetContents(string $url): OperationResult
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => true,
                'max_redirects' => 5,
                'user_agent' => 'TYPO3 ZabbixClient Monitor',
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $startTime = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $endTime = microtime(true);

        $httpCode = 0;
        if (isset($http_response_header) && is_array($http_response_header) && !empty($http_response_header)) {
            if (preg_match('/HTTP\/\d+\.?\d*\s+(\d+)/', $http_response_header[0], $matches)) {
                $httpCode = (int)$matches[1];
            }
        }

        if ($response === false) {
            return new OperationResult(false, 'Could not connect to URL: ' . $url);
        }

        $responseTimeMs = round(($endTime - $startTime) * 1000, 2);

        return new OperationResult(true, [
            'url' => $url,
            'responseTimeMs' => $responseTimeMs,
            'httpCode' => $httpCode,
        ]);
    }
}
