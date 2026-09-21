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
        // No curl_close(): the handle is an object since PHP 8.0, freed when it goes out
        // of scope, and calling it is deprecated as of PHP 8.5.

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

        // Read through a handle so the response headers can be taken from the stream
        // metadata. The $http_response_header variable would be the shorter route, but it
        // is deprecated as of PHP 8.4 - and merely naming it already emits the notice -
        // while its replacement http_get_last_response_headers() would raise the
        // requirement to PHP 8.4.
        $startTime = microtime(true);
        $handle = @fopen($url, 'r', false, $context);
        $response = false;
        $headers = [];
        if ($handle !== false) {
            $metaData = stream_get_meta_data($handle);
            $headers = $metaData['wrapper_data'] ?? [];
            $response = stream_get_contents($handle);
            fclose($handle);
        }
        $endTime = microtime(true);

        $httpCode = 0;
        foreach ($headers as $header) {
            // Keep the last status line, so a redirect chain reports where it ended.
            if (preg_match('/^HTTP\/\d+\.?\d*\s+(\d+)/', (string)$header, $matches) === 1) {
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
