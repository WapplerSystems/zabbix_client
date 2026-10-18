<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\ComposerRunException;
use WapplerSystems\ZabbixClient\OperationResult;
use WapplerSystems\ZabbixClient\Service\ComposerRunner;

/**
 * Reports the version of the composer binary that GetComposerAudit and GetComposerOutdated
 * use, together with the latest stable release from getcomposer.org.
 *
 * Old composer releases miss advisory sources and options of "composer audit", so an
 * outdated binary weakens the vulnerability check without any visible error.
 */
#[MonitoringOperation('GetComposerVersion')]
class GetComposerVersion implements IOperation, SingletonInterface
{
    protected string $versionsUrl = 'https://getcomposer.org/versions';

    public function execute(array $parameter = []): OperationResult
    {
        $runner = GeneralUtility::makeInstance(ComposerRunner::class);

        try {
            $result = $runner->run(['--version'], 20);
        } catch (ComposerRunException $e) {
            return new OperationResult(false, 'Cannot run composer: ' . $e->getMessage());
        }

        // "Composer version 2.5.5 2023-03-21 11:50:05"
        if (preg_match('/Composer (?:version )?(\d+\.\d+\.\d+[^\s]*)/', $result['stdout'], $matches) !== 1) {
            return new OperationResult(
                false,
                'Cannot determine composer version (exit code ' . $result['exitCode'] . ', ' . $result['command'] . ')'
            );
        }
        $version = $matches[1];
        $latest = $this->fetchLatestStableVersion();

        return new OperationResult(true, [
            'version' => $version,
            'latest' => $latest,
            // null if getcomposer.org could not be reached, so a failed lookup is no all-clear
            'updateAvailable' => $latest === '' ? null : version_compare($version, $latest, '<'),
            'binary' => $runner->findComposer(),
            'php' => $runner->findPhpCli(),
        ]);
    }

    protected function fetchLatestStableVersion(): string
    {
        try {
            $json = GeneralUtility::getUrl($this->versionsUrl);
        } catch (\Throwable) {
            return '';
        }
        if (!is_string($json) || $json === '') {
            return '';
        }
        $data = json_decode($json, true);

        return (string)($data['stable'][0]['version'] ?? '');
    }
}
