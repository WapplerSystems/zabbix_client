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
        self::assertArrayHasKey('majorCount', $value);
        self::assertArrayHasKey('minorCount', $value);
        self::assertArrayHasKey('patchCount', $value);
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

    #[Test]
    public function updateLevelIsClassifiedBySemver(): void
    {
        $operation = new class () extends GetComposerOutdated {
            public function level(string $version, string $latest): ?string
            {
                return $this->getUpdateLevel($version, $latest);
            }
        };

        self::assertSame('major', $operation->level('v8.3.1', 'v9.0.1'));
        self::assertSame('minor', $operation->level('14.0.3', '14.1.2'));
        self::assertSame('patch', $operation->level('2.2.0', '2.2.4'));
        self::assertNull($operation->level('dev-main 365cf5c', 'dev-main d4461dd'));
    }
}
