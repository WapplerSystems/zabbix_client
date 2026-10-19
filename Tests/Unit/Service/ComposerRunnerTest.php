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

    #[Test]
    public function isExecutableWrapperDetectsShellScripts(): void
    {
        $runner = new ComposerRunner();
        $directory = sys_get_temp_dir() . '/zabbix_client_' . uniqid();
        mkdir($directory);
        $files = [
            'shell' => ["#!/bin/bash\nexec php composer.phar \"$@\"\n", true],
            'env-php' => ["#!/usr/bin/env php\n<?php\n", false],
            'php-shebang' => ["#!/usr/local/bin/php\n<?php\n", false],
            'plain-php' => ["<?php\n", false],
        ];
        try {
            foreach ($files as $name => [$content, $expected]) {
                file_put_contents($directory . '/' . $name, $content);
                chmod($directory . '/' . $name, 0755);
                self::assertSame($expected, $runner->isExecutableWrapper($directory . '/' . $name), $name);
            }
            chmod($directory . '/shell', 0644);
            self::assertFalse($runner->isExecutableWrapper($directory . '/shell'), 'not executable');
        } finally {
            array_map('unlink', glob($directory . '/*'));
            rmdir($directory);
        }
    }
}
