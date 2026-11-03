<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetScriptFilesInPublicDirs;

class GetScriptFilesInPublicDirsTest extends UnitTestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = sys_get_temp_dir() . '/zabbix_test_' . uniqid();
        foreach ([
            'typo3temp/assets/css/main.css',
            'typo3temp/assets/tmp-addeba.php',
            'typo3temp/var/cache/code/core/cached.php',
            'fileadmin/user_upload/report.pdf',
            'fileadmin/user_upload/shell.php.jpg',
            'fileadmin/user_upload/Upper.PHTML',
            'fileadmin/user_upload/phpinfo.txt',
            'fileadmin/_zbx0123abcd.tmp/probe.php',
        ] as $file) {
            GeneralUtility::mkdir_deep(dirname($this->publicPath . '/' . $file));
            file_put_contents($this->publicPath . '/' . $file, 'x');
        }
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->publicPath, true);
        parent::tearDown();
    }

    private function createOperation(int $maxEntries = 200000): GetScriptFilesInPublicDirs
    {
        return new class ($this->publicPath, $maxEntries) extends GetScriptFilesInPublicDirs {
            public function __construct(private string $testPublicPath, int $maxEntries)
            {
                $this->maxEntries = $maxEntries;
            }

            protected function getPublicPath(): string
            {
                return $this->testPublicPath;
            }

            protected function getScanRoots(): array
            {
                return [$this->testPublicPath . '/typo3temp', $this->testPublicPath . '/fileadmin'];
            }
        };
    }

    #[Test]
    public function executeFindsScriptFilesAndSkipsExcludedDirectories(): void
    {
        $result = $this->createOperation()->execute();

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertSame(3, $value['count']);
        self::assertFalse($value['truncated']);
        $paths = array_column($value['files'], 'path');
        sort($paths);
        self::assertSame([
            'fileadmin/user_upload/Upper.PHTML',
            'fileadmin/user_upload/shell.php.jpg',
            'typo3temp/assets/tmp-addeba.php',
        ], $paths);
        self::assertSame(['typo3temp', 'fileadmin'], $value['roots']);
    }

    #[Test]
    public function executeStopsAfterMaximumEntries(): void
    {
        $value = $this->createOperation(1)->execute()->getValue();

        self::assertTrue($value['truncated']);
    }
}
