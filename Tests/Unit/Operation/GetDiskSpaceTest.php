<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetDiskSpace;

class GetDiskSpaceTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsSuccessfulResult(): void
    {
        $operation = new GetDiskSpace();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsTotalAndFreeSpace(): void
    {
        $operation = new GetDiskSpace();
        $result = $operation->execute();
        $value = $result->getValue();

        self::assertIsArray($value);
        self::assertArrayHasKey('total', $value);
        self::assertArrayHasKey('free', $value);
    }

    #[Test]
    public function executeReturnsPositiveValues(): void
    {
        $operation = new GetDiskSpace();
        $result = $operation->execute();
        $value = $result->getValue();

        self::assertGreaterThan(0, $value['total']);
        self::assertGreaterThanOrEqual(0, $value['free']);
    }

    #[Test]
    public function freeSpaceIsLessThanOrEqualToTotal(): void
    {
        $operation = new GetDiskSpace();
        $result = $operation->execute();
        $value = $result->getValue();

        self::assertLessThanOrEqual($value['total'], $value['free']);
    }

    #[Test]
    public function executeWithCustomPath(): void
    {
        $operation = new GetDiskSpace();
        $result = $operation->execute(['path' => '/tmp']);
        $value = $result->getValue();

        self::assertTrue($result->isSuccessful());
        self::assertGreaterThan(0, $value['total']);
    }
}
