<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\OperationManager;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 * Integration test that verifies all registered operations can be
 * instantiated via DI and executed without fatal errors.
 */
class AllOperationsIntegrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
        'typo3/cms-scheduler',
        'typo3/cms-install',
    ];

    /**
     * Operations that require no parameters or can run with safe defaults.
     */
    public static function parameterlessOperationProvider(): array
    {
        return [
            'GetPHPVersion' => ['GetPHPVersion', []],
            'GetTYPO3Version' => ['GetTYPO3Version', []],
            'GetTYPO3Version as integer' => ['GetTYPO3Version', ['asInteger' => '1']],
            'GetDiskSpace' => ['GetDiskSpace', []],
            'GetDiskSpace /tmp' => ['GetDiskSpace', ['path' => '/tmp']],
            'GetApplicationContext' => ['GetApplicationContext', []],
            'GetDatabaseVersion' => ['GetDatabaseVersion', []],
            'HasFailedSchedulerTask' => ['HasFailedSchedulerTask', []],
            'HasStuckSchedulerTask' => ['HasStuckSchedulerTask', []],
            'GetLastSchedulerRun' => ['GetLastSchedulerRun', []],
            'GetOpCacheStatus' => ['GetOpCacheStatus', []],
            'HasMissingDefaultMailSettings' => ['HasMissingDefaultMailSettings', []],
            'HasDeprecationLogEnabled' => ['HasDeprecationLogEnabled', []],
            'HasRemainingUpdates' => ['HasRemainingUpdates', []],
        ];
    }

    /**
     * Operations that require specific parameters to avoid exceptions.
     */
    public static function parameterizedOperationProvider(): array
    {
        return [
            'GetRecord by uid' => [
                'GetRecord',
                ['table' => 'be_users', 'field' => 'uid', 'value' => '1', 'checkEnableFields' => false],
            ],
            'GetRecords by username' => [
                'GetRecords',
                ['table' => 'be_users', 'field' => 'username', 'value' => 'admin', 'checkEnableFields' => false],
            ],
            'HasForbiddenUsers' => [
                'HasForbiddenUsers',
                ['usernames' => 'admin'],
            ],
            'GetExtensionVersion' => [
                'GetExtensionVersion',
                ['extensionKey' => 'zabbix_client'],
            ],
            'CheckPathExists file' => [
                'CheckPathExists',
                ['path' => '/tmp'],
            ],
            'GetLogResults' => [
                'GetLogResults',
                ['filter' => 'FailedLogins'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('parameterlessOperationProvider')]
    public function operationExecutesWithoutFatalError(string $operationName, array $params): void
    {
        $operationManager = $this->get(OperationManager::class);

        self::assertTrue($operationManager->hasOperation($operationName), 'Operation ' . $operationName . ' is not registered');

        $result = $operationManager->executeOperation($operationName, $params);

        self::assertInstanceOf(OperationResult::class, $result);
        self::assertTrue($result->isSuccessful(), 'Operation ' . $operationName . ' failed: ' . print_r($result->getValue(), true));
    }

    #[Test]
    #[DataProvider('parameterizedOperationProvider')]
    public function parameterizedOperationExecutesWithoutFatalError(string $operationName, array $params): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');

        $operationManager = $this->get(OperationManager::class);

        self::assertTrue($operationManager->hasOperation($operationName), 'Operation ' . $operationName . ' is not registered');

        $result = $operationManager->executeOperation($operationName, $params);

        self::assertInstanceOf(OperationResult::class, $result);
    }

    #[Test]
    public function allRegisteredOperationsAreDiscoverable(): void
    {
        $operationManager = $this->get(OperationManager::class);

        $expectedOperations = [
            'GetPHPVersion',
            'GetTYPO3Version',
            'GetDiskSpace',
            'GetApplicationContext',
            'GetDatabaseVersion',
            'HasFailedSchedulerTask',
            'HasStuckSchedulerTask',
            'GetLastSchedulerRun',
            'HasForbiddenUsers',
            'GetRecord',
            'GetRecords',
            'GetExtensionList',
            'GetExtensionVersion',
            'CheckPathExists',
            'GetFilesystemChecksum',
            'GetLogResults',
            'GetOpCacheStatus',
            'HasMissingDefaultMailSettings',
            'HasDeprecationLogEnabled',
            'HasRemainingUpdates',
            'GetTotalLogFilesSize',
        ];

        foreach ($expectedOperations as $operationName) {
            self::assertTrue(
                $operationManager->hasOperation($operationName),
                'Expected operation ' . $operationName . ' is not registered in OperationManager'
            );
        }
    }
}
