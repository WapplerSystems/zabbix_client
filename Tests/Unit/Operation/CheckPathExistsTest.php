<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\CheckPathExists;

class CheckPathExistsTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsFalseForNonExistentPath(): void
    {
        $operation = new CheckPathExists();
        $result = $operation->execute(['path' => '/tmp/nonexistent_path_' . uniqid()]);

        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsFileForExistingFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'zabbix_test_');
        file_put_contents($tmpFile, 'test');

        $operation = new CheckPathExists();
        $result = $operation->execute(['path' => $tmpFile]);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertSame('file', $value['type']);
        self::assertArrayHasKey('time', $value);
        self::assertArrayHasKey('size', $value);

        unlink($tmpFile);
    }

    #[Test]
    public function executeReturnsFolderForExistingDirectory(): void
    {
        $operation = new CheckPathExists();
        $result = $operation->execute(['path' => sys_get_temp_dir()]);

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();
        self::assertSame('folder', $value['type']);
    }

    #[Test]
    public function executeReturnsCorrectFileSize(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'zabbix_test_');
        $content = 'Hello Zabbix';
        file_put_contents($tmpFile, $content);

        $operation = new CheckPathExists();
        $result = $operation->execute(['path' => $tmpFile]);

        $value = $result->getValue();
        self::assertSame(strlen($content), $value['size']);

        unlink($tmpFile);
    }
}
