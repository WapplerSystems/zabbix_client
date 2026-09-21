<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetComposerOutdated;

class GetComposerOutdatedTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsSuccessfulResult(): void
    {
        $operation = new GetComposerOutdated();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsExpectedStructure(): void
    {
        $operation = new GetComposerOutdated();
        $result = $operation->execute();

        $value = $result->getValue();

        self::assertIsArray($value);
        self::assertArrayHasKey('outdatedCount', $value);
        self::assertArrayHasKey('direct', $value);
        self::assertArrayHasKey('packages', $value);
        self::assertIsInt($value['outdatedCount']);
        self::assertIsArray($value['packages']);
        self::assertCount($value['outdatedCount'], $value['packages']);
    }

    #[Test]
    public function executeDefaultsToDirectScope(): void
    {
        $operation = new GetComposerOutdated();
        $result = $operation->execute();

        self::assertTrue($result->getValue()['direct']);
    }

    #[Test]
    public function executeAcceptsDirectParameterOverride(): void
    {
        $operation = new GetComposerOutdated();
        $result = $operation->execute(['direct' => '0']);

        self::assertFalse($result->getValue()['direct']);
    }
}
