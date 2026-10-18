<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Service\ComposerRunner;

class ComposerRunnerTest extends UnitTestCase
{
    #[Test]
    public function decodeJsonSkipsLeadingNotices(): void
    {
        $output = "Deprecated: Composer\\Pcre\\Preg::replace(): Implicitly marking parameter \$count as nullable\n"
            . '{"advisories": {"vendor/package": []}}';

        self::assertSame(['advisories' => ['vendor/package' => []]], (new ComposerRunner())->decodeJson($output));
    }

    #[Test]
    public function decodeJsonReturnsNullWithoutJson(): void
    {
        $output = 'The HOME or COMPOSER_HOME environment variable must be set for composer to run correctly';

        self::assertNull((new ComposerRunner())->decodeJson($output));
    }

    #[Test]
    public function findPhpCliReturnsCurrentBinaryOnCli(): void
    {
        self::assertSame(PHP_BINARY, (new ComposerRunner())->findPhpCli());
    }

    #[Test]
    public function runReturnsComposerVersion(): void
    {
        $runner = new ComposerRunner();
        if ($runner->findComposer() === null) {
            self::markTestSkipped('No composer binary available');
        }

        $result = $runner->run(['--version'], 20);

        self::assertSame(0, $result['exitCode']);
        self::assertMatchesRegularExpression('/Composer (version )?\d+\.\d+\.\d+/', $result['stdout']);
    }
}
