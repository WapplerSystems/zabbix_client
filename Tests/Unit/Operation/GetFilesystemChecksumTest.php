<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetFilesystemChecksum;

class GetFilesystemChecksumTest extends UnitTestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/zabbix_checksum_test_' . uniqid();
        mkdir($this->testDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
        parent::tearDown();
    }

    #[Test]
    public function executeReturnsChecksumForFile(): void
    {
        $file = $this->testDir . '/test.txt';
        file_put_contents($file, 'test content');

        $operation = new GetFilesystemChecksum();
        $result = $operation->execute(['path' => $file, 'getSingleChecksums' => false]);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertArrayHasKey('checksum', $value);
        self::assertSame(md5_file($file), $value['checksum']);
    }

    #[Test]
    public function executeReturnsChecksumForDirectory(): void
    {
        file_put_contents($this->testDir . '/a.txt', 'aaa');
        file_put_contents($this->testDir . '/b.txt', 'bbb');

        $operation = new GetFilesystemChecksum();
        $result = $operation->execute(['path' => $this->testDir, 'getSingleChecksums' => false]);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertArrayHasKey('checksum', $value);
        self::assertNotEmpty($value['checksum']);
    }

    #[Test]
    public function checksumChangesWhenFileChanges(): void
    {
        $file = $this->testDir . '/test.txt';
        file_put_contents($file, 'original');

        $operation = new GetFilesystemChecksum();
        $result1 = $operation->execute(['path' => $file, 'getSingleChecksums' => false]);

        file_put_contents($file, 'modified');
        $result2 = $operation->execute(['path' => $file, 'getSingleChecksums' => false]);

        self::assertNotSame($result1->getValue()['checksum'], $result2->getValue()['checksum']);
    }

    #[Test]
    public function executeReturnsSingleChecksumsWhenRequested(): void
    {
        file_put_contents($this->testDir . '/a.txt', 'aaa');
        file_put_contents($this->testDir . '/b.txt', 'bbb');

        $operation = new GetFilesystemChecksum();
        $result = $operation->execute(['path' => $this->testDir, 'getSingleChecksums' => '1']);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertArrayHasKey('singleChecksums', $value);
        self::assertIsArray($value['singleChecksums']);
    }

    #[Test]
    public function executeFailsForNonExistentPath(): void
    {
        $operation = new GetFilesystemChecksum();
        $result = $operation->execute(['path' => '/nonexistent_' . uniqid(), 'getSingleChecksums' => false]);

        self::assertFalse($result->isSuccessful());
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
