<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\GetLogResults;

class GetLogResultsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    public static function logFilterDataProvider(): array
    {
        return [
            'ServiceUnavailableException' => [
                'filter' => 'ServiceUnavailableException',
                'fixtureFile' => 'sys_log_errors.csv',
                'expectedCount' => 2,
            ],
            'PageNotFoundException' => [
                'filter' => 'PageNotFoundException',
                'fixtureFile' => 'sys_log_errors.csv',
                'expectedCount' => 1,
            ],
            'OtherExceptions' => [
                'filter' => 'OtherExceptions',
                'fixtureFile' => 'sys_log_errors.csv',
                'expectedCount' => 1,
            ],
            'FailedLogins' => [
                'filter' => 'FailedLogins',
                'fixtureFile' => 'sys_log_errors.csv',
                'expectedCount' => 2,
            ],
            'no errors in clean log' => [
                'filter' => 'ServiceUnavailableException',
                'fixtureFile' => 'sys_log_clean.csv',
                'expectedCount' => 0,
            ],
            'no failed logins in clean log' => [
                'filter' => 'FailedLogins',
                'fixtureFile' => 'sys_log_clean.csv',
                'expectedCount' => 0,
            ],
        ];
    }

    #[Test]
    #[DataProvider('logFilterDataProvider')]
    public function executeCountsLogEntriesByFilter(string $filter, string $fixtureFile, int $expectedCount): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/' . $fixtureFile);

        $operation = new GetLogResults();
        $result = $operation->execute(['filter' => $filter]);

        self::assertTrue($result->isSuccessful());
        self::assertSame($expectedCount, $result->getValue());
    }

    #[Test]
    public function returnsZeroWithEmptyDatabase(): void
    {
        $operation = new GetLogResults();
        $result = $operation->execute(['filter' => 'FailedLogins']);

        self::assertTrue($result->isSuccessful());
        self::assertSame(0, $result->getValue());
    }
}
