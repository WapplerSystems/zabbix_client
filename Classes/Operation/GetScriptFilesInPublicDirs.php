<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * Finds PHP files in public directories TYPO3 writes to (typo3temp, local file storages).
 * Nothing legitimate lives there, so every hit is a web shell candidate.
 */
#[MonitoringOperation('GetScriptFilesInPublicDirs')]
class GetScriptFilesInPublicDirs implements IOperation, SingletonInterface
{
    protected const SCRIPT_PATTERN = '/\.(php\d?|pht|phtml|phar)(\.|$)/i';
    protected const MAX_LISTED_FILES = 20;

    protected int $maxEntries = 200000;
    protected float $maxSeconds = 20.0;

    public function execute(array $parameter = []): OperationResult
    {
        try {
            return new OperationResult(true, $this->scan());
        } catch (\Throwable $e) {
            return new OperationResult(false, 'Error scanning public directories: ' . $e->getMessage());
        }
    }

    protected function scan(): array
    {
        $publicPath = $this->getPublicPath();
        $roots = $this->getScanRoots();
        $startTime = microtime(true);
        $entries = 0;
        $truncated = false;
        $count = 0;
        $files = [];

        foreach ($roots as $root) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                    fn(\SplFileInfo $file): bool => !$file->isDir() || !$this->isExcludedDirectory($file->getPathname())
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY,
                \RecursiveIteratorIterator::CATCH_GET_CHILD
            );
            foreach ($iterator as $file) {
                if (++$entries > $this->maxEntries || microtime(true) - $startTime > $this->maxSeconds) {
                    $truncated = true;
                    break 2;
                }
                if (!preg_match(self::SCRIPT_PATTERN, $file->getFilename())) {
                    continue;
                }
                $count++;
                if (count($files) < self::MAX_LISTED_FILES) {
                    $files[] = [
                        'path' => $this->makeRelative($file->getPathname(), $publicPath),
                        'modified' => date('c', (int)$file->getMTime()),
                    ];
                }
            }
        }

        return [
            'count' => $count,
            'files' => $files,
            'truncated' => $truncated,
            'roots' => array_map(fn(string $root): string => $this->makeRelative($root, $publicPath), $roots),
        ];
    }

    protected function getPublicPath(): string
    {
        return Environment::getPublicPath();
    }

    /**
     * typo3temp plus the root folder of every local file storage that lies inside the public path.
     * Storages outside of it cannot be requested over HTTP.
     *
     * @return string[] absolute paths
     */
    protected function getScanRoots(): array
    {
        $publicPath = $this->getPublicPath();
        $roots = [$publicPath . '/typo3temp'];

        // Read the storage records directly: ResourceStorage objects need a backend user for their permission checks
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_storage');
        $storageConfigurations = $queryBuilder->select('configuration')
            ->from('sys_file_storage')
            ->where($queryBuilder->expr()->eq('driver', $queryBuilder->createNamedParameter('Local')))
            ->executeQuery()
            ->fetchFirstColumn();
        foreach ($storageConfigurations as $flexForm) {
            $configuration = GeneralUtility::xml2array((string)$flexForm);
            $fields = is_array($configuration) ? ($configuration['data']['sDEF']['lDEF'] ?? []) : [];
            $basePath = rtrim((string)($fields['basePath']['vDEF'] ?? ''), '/');
            if ($basePath === '') {
                continue;
            }
            $roots[] = ($fields['pathType']['vDEF'] ?? 'relative') === 'relative'
                ? $publicPath . '/' . $basePath
                : $basePath;
        }
        if (is_dir($publicPath . '/uploads')) {
            $roots[] = $publicPath . '/uploads';
        }

        // fileadmin is often a symlink into a shared deployment folder: decide by the path as seen
        // from the web root, deduplicate by the resolved path
        $publicRealPath = realpath($publicPath) ?: $publicPath;
        $result = [];
        foreach ($roots as $root) {
            $realPath = realpath($root);
            if ($realPath === false || !is_dir($realPath)) {
                continue;
            }
            if (str_starts_with($root, $publicPath . '/') || str_starts_with($realPath, $publicRealPath . '/')) {
                $result[$realPath] ??= $root;
            }
        }
        return array_values($result);
    }

    /**
     * typo3temp/var holds the PHP code cache in non-composer installations (protected by a core .htaccess),
     * _zbx*.tmp are the short-lived probes of HasScriptExecutionInPublicDirs.
     */
    protected function isExcludedDirectory(string $path): bool
    {
        return str_ends_with(str_replace('\\', '/', $path), '/typo3temp/var')
            || preg_match('#/_zbx[0-9a-f]+\.tmp$#', $path) === 1;
    }

    protected function makeRelative(string $path, string $publicPath): string
    {
        $publicRealPath = realpath($publicPath) ?: $publicPath;
        foreach ([$publicRealPath, $publicPath] as $prefix) {
            if (str_starts_with($path, $prefix . '/')) {
                return substr($path, strlen($prefix) + 1);
            }
        }
        return $path;
    }
}
