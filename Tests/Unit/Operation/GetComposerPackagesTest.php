<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Operation;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetComposerPackages;

class GetComposerPackagesTest extends UnitTestCase
{
    #[Test]
    public function executeReturnsSuccessfulResult(): void
    {
        $result = (new GetComposerPackages())->execute();

        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsExpectedStructure(): void
    {
        $value = (new GetComposerPackages())->execute()->getValue();

        self::assertIsArray($value);
        self::assertArrayHasKey('count', $value);
        self::assertArrayHasKey('packages', $value);
        self::assertArrayHasKey('unresolved', $value);
        self::assertArrayHasKey('root', $value);
        self::assertArrayHasKey('generated', $value);
        self::assertIsInt($value['count']);
        self::assertIsArray($value['packages']);
        self::assertIsArray($value['unresolved']);
        self::assertCount($value['count'], $value['packages']);
    }

    #[Test]
    public function executeReportsInstalledDependencies(): void
    {
        $value = (new GetComposerPackages())->execute()->getValue();
        $all = $value['packages'] + $value['unresolved'];

        // Deliberately not zabbix_client itself: when the extension is installed as the
        // root package, as it is in CI, it is filtered out on purpose.
        self::assertArrayHasKey('typo3/cms-core', $all);
    }

    #[Test]
    public function executeSkipsTheRootPackage(): void
    {
        $value = (new GetComposerPackages())->execute()->getValue();

        // The project itself is not a published package and has no usable version.
        self::assertArrayNotHasKey($value['root'], $value['packages']);
        self::assertArrayNotHasKey($value['root'], $value['unresolved']);
    }

    #[Test]
    public function executeSkipsReplacedAndProvidedPackages(): void
    {
        $replaced = [];
        foreach (InstalledVersions::getAllRawData() as $installed) {
            foreach ($installed['versions'] ?? [] as $name => $package) {
                if (!isset($package['pretty_version'])) {
                    $replaced[] = $name;
                }
            }
        }
        if ($replaced === []) {
            self::markTestSkipped('No replaced or provided packages in this installation.');
        }

        $value = (new GetComposerPackages())->execute()->getValue();
        $all = $value['packages'] + $value['unresolved'];

        foreach ($replaced as $name) {
            self::assertArrayNotHasKey($name, $all, $name . ' has no real version and must not be reported');
        }
    }

    #[Test]
    public function executeMovesBranchInstallsToUnresolved(): void
    {
        $value = (new GetComposerPackages())->execute()->getValue();

        foreach ($value['packages'] as $name => $version) {
            self::assertStringStartsNotWith('dev-', $version, $name);
            self::assertStringEndsNotWith('-dev', $version, $name);
        }
        foreach ($value['unresolved'] as $name => $version) {
            self::assertTrue(
                str_starts_with($version, 'dev-') || str_ends_with($version, '-dev'),
                $name . ' was reported as unresolved but has a comparable version: ' . $version
            );
        }
    }

    #[Test]
    public function executeStripsLeadingVersionPrefix(): void
    {
        $value = (new GetComposerPackages())->execute()->getValue();

        foreach ($value['packages'] as $name => $version) {
            self::assertDoesNotMatchRegularExpression('/^v\d/', $version, $name);
        }
    }

    #[Test]
    public function executeFiltersByPackageType(): void
    {
        $operation = new GetComposerPackages();
        $all = $operation->execute()->getValue();
        $filtered = $operation->execute(['types' => 'typo3-cms-extension'])->getValue();

        self::assertLessThanOrEqual($all['count'], $filtered['count']);
        foreach (array_keys($filtered['packages']) as $name) {
            self::assertArrayHasKey($name, $all['packages']);
        }
    }

    #[Test]
    public function executeExcludesDevRequirementsByDefault(): void
    {
        $operation = new GetComposerPackages();

        $default = $operation->execute()->getValue();
        $withDev = $operation->execute(['includeDev' => '1'])->getValue();

        self::assertGreaterThanOrEqual($default['count'], $withDev['count']);
    }
}
