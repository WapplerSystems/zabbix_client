<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WapplerSystems\ZabbixClient\Operation\GetPHPVersion;
use WapplerSystems\ZabbixClient\Operation\IOperation;
use WapplerSystems\ZabbixClient\OperationManager;
use WapplerSystems\ZabbixClient\OperationResult;

class OperationManagerTest extends UnitTestCase
{
    #[Test]
    public function hasOperationReturnsTrueForRegisteredOperation(): void
    {
        $locator = new ServiceLocator([
            'GetPHPVersion' => fn() => new GetPHPVersion(),
        ]);
        $manager = new OperationManager($locator);

        self::assertTrue($manager->hasOperation('GetPHPVersion'));
    }

    #[Test]
    public function hasOperationReturnsFalseForUnknownOperation(): void
    {
        $locator = new ServiceLocator([]);
        $manager = new OperationManager($locator);

        self::assertFalse($manager->hasOperation('NonExistent'));
    }

    #[Test]
    public function getOperationReturnsRegisteredInstance(): void
    {
        $operation = new GetPHPVersion();
        $locator = new ServiceLocator([
            'GetPHPVersion' => fn() => $operation,
        ]);
        $manager = new OperationManager($locator);

        self::assertSame($operation, $manager->getOperation('GetPHPVersion'));
    }

    #[Test]
    public function getOperationThrowsExceptionForUnknown(): void
    {
        $locator = new ServiceLocator([]);
        $manager = new OperationManager($locator);

        $this->expectException(\UnexpectedValueException::class);
        $manager->getOperation('NonExistent');
    }

    #[Test]
    public function executeOperationReturnsResult(): void
    {
        $locator = new ServiceLocator([
            'GetPHPVersion' => fn() => new GetPHPVersion(),
        ]);
        $manager = new OperationManager($locator);

        $result = $manager->executeOperation('GetPHPVersion');

        self::assertInstanceOf(OperationResult::class, $result);
        self::assertTrue($result->isSuccessful());
    }

    #[Test]
    public function executeOperationPassesParameters(): void
    {
        $mockOperation = new class implements IOperation {
            public array $receivedParams = [];
            public function execute(array $parameter = []): OperationResult
            {
                $this->receivedParams = $parameter;
                return new OperationResult(true, $parameter);
            }
        };

        $locator = new ServiceLocator([
            'TestOp' => fn() => $mockOperation,
        ]);
        $manager = new OperationManager($locator);

        $params = ['key' => 'value'];
        $manager->executeOperation('TestOp', $params);

        self::assertSame($params, $mockOperation->receivedParams);
    }
}
