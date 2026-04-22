<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\GetExtensionVersion;
use WapplerSystems\ZabbixClient\Operation\HasMissingDefaultMailSettings;

class ConfigOperationsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    #[Test]
    public function hasMissingMailSettingsDetectsMissingAddress(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = '';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Test';

        $operation = new HasMissingDefaultMailSettings();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertSame('defaultMailFromAddress', $result->getValue());
    }

    #[Test]
    public function hasMissingMailSettingsDetectsMissingName(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'test@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = '';

        $operation = new HasMissingDefaultMailSettings();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertSame('defaultMailFromName', $result->getValue());
    }

    #[Test]
    public function hasMissingMailSettingsReturnsFalseWhenConfigured(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'test@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Test Sender';

        $operation = new HasMissingDefaultMailSettings();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function getExtensionVersionReturnsVersionForLoadedExtension(): void
    {
        $operation = new GetExtensionVersion();
        $result = $operation->execute(['extensionKey' => 'zabbix_client']);

        self::assertTrue($result->isSuccessful());
        self::assertNotEmpty($result->getValue());
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $result->getValue());
    }

    #[Test]
    public function getExtensionVersionFailsForUnloadedExtension(): void
    {
        $operation = new GetExtensionVersion();
        $result = $operation->execute(['extensionKey' => 'nonexistent_extension_xyz']);

        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function getExtensionVersionThrowsExceptionWithoutKey(): void
    {
        $operation = new GetExtensionVersion();

        $this->expectException(\WapplerSystems\ZabbixClient\Exception\InvalidArgumentException::class);
        $operation->execute([]);
    }

    #[Test]
    public function hasDeprecationLogEnabledReturnsResult(): void
    {
        $operation = new \WapplerSystems\ZabbixClient\Operation\HasDeprecationLogEnabled();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertIsBool($result->getValue());
    }
}
