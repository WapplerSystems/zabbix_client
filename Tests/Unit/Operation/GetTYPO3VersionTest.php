<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetTYPO3Version;

class GetTYPO3VersionTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsSuccessfulResult(): void
    {
        $operation = new GetTYPO3Version();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsVersionString(): void
    {
        $operation = new GetTYPO3Version();
        $result = $operation->execute();

        self::assertIsString($result->getValue());
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $result->getValue());
    }

    #[Test]
    public function executeAsIntegerReturnsInteger(): void
    {
        $operation = new GetTYPO3Version();
        $result = $operation->execute(['asInteger' => '1']);

        self::assertTrue($result->isSuccessful());
        self::assertIsInt($result->getValue());
        self::assertGreaterThan(0, $result->getValue());
    }

    #[Test]
    public function executeWithoutAsIntegerReturnsString(): void
    {
        $operation = new GetTYPO3Version();
        $result = $operation->execute(['asInteger' => '0']);

        self::assertIsString($result->getValue());
    }
}
