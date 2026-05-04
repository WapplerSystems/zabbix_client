<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetPHPVersion;

class GetPHPVersionTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsSuccessfulResult(): void
    {
        $operation = new GetPHPVersion();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsCurrentPHPVersion(): void
    {
        $operation = new GetPHPVersion();
        $result = $operation->execute();

        $expected = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION;
        self::assertSame($expected, $result->getValue());
    }

    #[Test]
    public function executeReturnsValidVersionFormat(): void
    {
        $operation = new GetPHPVersion();
        $result = $operation->execute();

        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $result->getValue());
    }
}
