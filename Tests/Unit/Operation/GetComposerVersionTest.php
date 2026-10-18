<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetComposerVersion;
use WapplerSystems\ZabbixClient\Service\ComposerRunner;

class GetComposerVersionTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsInstalledVersion(): void
    {
        if ((new ComposerRunner())->findComposer() === null) {
            self::markTestSkipped('No composer binary available');
        }

        $operation = new class () extends GetComposerVersion {
            protected function fetchLatestStableVersion(): string
            {
                return '99.0.0';
            }
        };
        $value = $operation->execute()->getValue();

        self::assertIsArray($value);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $value['version']);
        self::assertSame('99.0.0', $value['latest']);
        self::assertTrue($value['updateAvailable']);
    }
}
