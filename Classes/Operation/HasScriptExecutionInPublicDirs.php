<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * Checks whether the web server executes PHP files in directories TYPO3 can write to
 * (typo3temp/assets, fileadmin). If it does, any file written through another vulnerability
 * becomes a web shell. The core ServerResponseCheck only covers double extensions like .php.wrong.
 *
 * A harmless probe (prints a random token) is placed next to a plain text control file, requested
 * over HTTP and removed again immediately.
 */
#[MonitoringOperation('HasScriptExecutionInPublicDirs')]
class HasScriptExecutionInPublicDirs implements IOperation, SingletonInterface
{
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_UNREACHABLE = 'unreachable';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        protected readonly ?RequestFactory $requestFactory = null,
    ) {}

    public function execute(array $parameter = []): OperationResult
    {
        $siteUrl = $parameter['url'] ?? $this->getSiteUrl();
        if ($siteUrl === null || $siteUrl === '') {
            return new OperationResult(false, 'Cannot determine site URL');
        }
        $siteUrl = rtrim($siteUrl, '/') . '/';

        $locations = [];
        foreach ($this->getProbeLocations() as $label => $relativePath) {
            $locations[$label] = $this->probeLocation($siteUrl, $relativePath);
        }

        $executed = array_keys(array_filter(
            $locations,
            static fn(string $status): bool => $status === self::STATUS_EXECUTED
        ));

        return new OperationResult(true, [
            'executed' => $executed !== [],
            'executedIn' => $executed,
            'locations' => $locations,
        ]);
    }

    /**
     * @return array<string, string> label => directory relative to the public path
     */
    protected function getProbeLocations(): array
    {
        return [
            'typo3temp' => 'typo3temp/assets',
            'fileadmin' => rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] ?? 'fileadmin', '/'),
        ];
    }

    protected function getPublicPath(): string
    {
        return Environment::getPublicPath();
    }

    protected function probeLocation(string $siteUrl, string $relativePath): string
    {
        $parentDir = $this->getPublicPath() . '/' . $relativePath;
        if (!is_dir($parentDir) || !is_writable($parentDir)) {
            return self::STATUS_SKIPPED;
        }

        $random = bin2hex(random_bytes(8));
        $token = 'zbx-probe-' . bin2hex(random_bytes(8));
        $folderName = '_zbx' . $random . '.tmp';
        $probeDir = $parentDir . '/' . $folderName;
        $urlPrefix = $siteUrl . $relativePath . '/' . $folderName . '/';

        try {
            if (!@mkdir($probeDir)
                || @file_put_contents($probeDir . '/' . $random . '.txt', $token) === false
                || @file_put_contents($probeDir . '/' . $random . '.php', '<?php echo strrev(\'' . strrev($token) . '\');') === false
            ) {
                return self::STATUS_SKIPPED;
            }

            // The control file proves that the directory is reachable under this URL at all
            $control = $this->fetch($urlPrefix . $random . '.txt');
            if ($control === null || $control['status'] !== 200 || !str_contains($control['body'], $token)) {
                return self::STATUS_UNREACHABLE;
            }

            $probe = $this->fetch($urlPrefix . $random . '.php');
            if ($probe !== null && str_contains($probe['body'], $token)) {
                return self::STATUS_EXECUTED;
            }
            return self::STATUS_BLOCKED;
        } finally {
            GeneralUtility::rmdir($probeDir, true);
        }
    }

    /**
     * @return array{status: int, body: string}|null
     */
    protected function fetch(string $url): ?array
    {
        try {
            $response = ($this->requestFactory ?? GeneralUtility::makeInstance(RequestFactory::class))->request($url, 'GET', [
                'timeout' => 5,
                'http_errors' => false,
                'allow_redirects' => false,
            ]);
            return [
                'status' => $response->getStatusCode(),
                'body' => substr((string)$response->getBody(), 0, 4096),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The host Zabbix called is the one whose web server configuration matters, so prefer it
     * over the base of the first site configuration.
     */
    protected function getSiteUrl(): ?string
    {
        $normalizedParams = ($GLOBALS['TYPO3_REQUEST'] ?? null)?->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams && $normalizedParams->getSiteUrl() !== '') {
            return $normalizedParams->getSiteUrl();
        }

        $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $site = reset($sites);
        if ($site === false) {
            return null;
        }
        // A site base may carry a language path (/de/), the public directories live at the host root
        $base = $site->getBase();
        if ($base->getHost() === '') {
            return null;
        }
        return ($base->getScheme() ?: 'https') . '://' . $base->getHost()
            . ($base->getPort() !== null ? ':' . $base->getPort() : '') . '/';
    }
}
