<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\HasScriptExecutionInPublicDirs;

class HasScriptExecutionInPublicDirsTest extends UnitTestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = sys_get_temp_dir() . '/zabbix_test_' . uniqid();
        mkdir($this->publicPath . '/typo3temp/assets', 0777, true);
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->publicPath, true);
        parent::tearDown();
    }

    /**
     * Simulates the web server: serves files from the probe directory, optionally executing the PHP probe.
     */
    private function createOperation(string $serverMode): HasScriptExecutionInPublicDirs
    {
        return new class ($this->publicPath, $serverMode) extends HasScriptExecutionInPublicDirs {
            public function __construct(private string $testPublicPath, private string $serverMode)
            {
                parent::__construct();
            }

            protected function getPublicPath(): string
            {
                return $this->testPublicPath;
            }

            protected function getProbeLocations(): array
            {
                return ['typo3temp' => 'typo3temp/assets', 'missing' => 'does-not-exist'];
            }

            protected function fetch(string $url): ?array
            {
                if ($this->serverMode === 'unreachable') {
                    return ['status' => 404, 'body' => 'Not found'];
                }
                $file = $this->testPublicPath . '/' . substr($url, strlen('https://example.org/'));
                $content = (string)file_get_contents($file);
                if (str_ends_with($file, '.php')) {
                    if ($this->serverMode === 'blocked') {
                        return ['status' => 403, 'body' => 'Forbidden'];
                    }
                    if ($this->serverMode === 'executing') {
                        preg_match("/strrev\\('([^']+)'\\)/", $content, $matches);
                        return ['status' => 200, 'body' => strrev($matches[1])];
                    }
                }
                return ['status' => 200, 'body' => $content];
            }
        };
    }

    public static function serverModeDataProvider(): array
    {
        return [
            'php is executed' => ['executing', HasScriptExecutionInPublicDirs::STATUS_EXECUTED, true],
            'php is denied' => ['blocked', HasScriptExecutionInPublicDirs::STATUS_BLOCKED, false],
            'php is served as source' => ['source', HasScriptExecutionInPublicDirs::STATUS_BLOCKED, false],
            'directory not reachable' => ['unreachable', HasScriptExecutionInPublicDirs::STATUS_UNREACHABLE, false],
        ];
    }

    #[Test]
    #[DataProvider('serverModeDataProvider')]
    public function executeDetectsServerBehaviour(string $serverMode, string $expectedStatus, bool $expectedExecuted): void
    {
        $result = $this->createOperation($serverMode)->execute(['url' => 'https://example.org']);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertSame($expectedStatus, $value['locations']['typo3temp']);
        self::assertSame(HasScriptExecutionInPublicDirs::STATUS_SKIPPED, $value['locations']['missing']);
        self::assertSame($expectedExecuted, $value['executed']);
        self::assertSame($expectedExecuted ? ['typo3temp'] : [], $value['executedIn']);
    }

    #[Test]
    public function executeRemovesProbeFiles(): void
    {
        $this->createOperation('executing')->execute(['url' => 'https://example.org/']);

        self::assertSame([], glob($this->publicPath . '/typo3temp/assets/_zbx*'));
    }
}
