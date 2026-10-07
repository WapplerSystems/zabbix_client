<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Authentication\KeyAuthenticationProvider;

class KeyAuthenticationProviderTest extends UnitTestCase
{
    public static function keyCombinationsProvider(): array
    {
        return [
            'empty configured key, empty request key' => ['', '', false],
            'empty configured key, any request key' => ['', 'foo', false],
            'whitespace configured key, empty request key' => ['   ', '', false],
            'configured key, empty request key' => ['secret123', '', false],
            'configured key, wrong request key' => ['secret123', 'secret124', false],
            'configured key, prefix of key' => ['secret123', 'secret', false],
            'configured key, array request key' => ['secret123', ['secret123'], false],
            'configured key, null request key' => ['secret123', null, false],
            'configured key, matching request key' => ['secret123', 'secret123', true],
            'configured key, matching request key with whitespace' => [' secret123 ', 'secret123 ', true],
        ];
    }

    #[Test]
    #[DataProvider('keyCombinationsProvider')]
    public function keysMatchDeniesByDefault(string $configuredKey, mixed $requestKey, bool $expected): void
    {
        self::assertSame($expected, KeyAuthenticationProvider::keysMatch($configuredKey, $requestKey));
    }
}
