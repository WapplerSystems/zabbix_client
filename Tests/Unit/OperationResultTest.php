<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\OperationResult;

class OperationResultTest extends UnitTestCase
{
    #[Test]
    public function successfulResultReturnsTrue(): void
    {
        $result = new OperationResult(true, 'someValue');
        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function failedResultReturnsFalse(): void
    {
        $result = new OperationResult(false, 'error');
        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function getValueReturnsSetValue(): void
    {
        $result = new OperationResult(true, ['key' => 'value']);
        self::assertSame(['key' => 'value'], $result->getValue());
    }

    #[Test]
    public function getValueReturnsNullWhenNotSet(): void
    {
        $result = new OperationResult(true);
        self::assertNull($result->getValue());
    }

    #[Test]
    public function toArrayReturnsCorrectStructure(): void
    {
        $result = new OperationResult(true, 'data');
        $array = $result->toArray();

        self::assertArrayHasKey('status', $array);
        self::assertArrayHasKey('value', $array);
        self::assertTrue($array['status']);
        self::assertSame('data', $array['value']);
    }

    #[Test]
    public function toArrayWithFailedResult(): void
    {
        $result = new OperationResult(false, 'error message');
        $array = $result->toArray();

        self::assertFalse($array['status']);
        self::assertSame('error message', $array['value']);
    }
}
