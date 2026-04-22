<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\GetRecords;

class GetRecordsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
    }

    #[Test]
    public function executeReturnsSingleRecordByField(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'username',
            'value' => 'admin',
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $records = $result->getValue();
        self::assertIsArray($records);
        self::assertCount(1, $records);
        self::assertSame('admin', $records[0]['username']);
    }

    #[Test]
    public function executeReturnsMultipleRecords(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'deleted',
            'value' => '0',
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $records = $result->getValue();
        self::assertIsArray($records);
        self::assertGreaterThanOrEqual(2, count($records));
    }

    #[Test]
    public function executeWithArrayFieldsAndValues(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => ['username', 'deleted'],
            'value' => ['username' => 'admin', 'deleted' => '0'],
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $records = $result->getValue();
        self::assertIsArray($records);
        self::assertCount(1, $records);
    }

    #[Test]
    public function executeStripsProtectedFields(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'uid',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        $records = $result->getValue();
        self::assertArrayNotHasKey('password', $records[0]);
        self::assertArrayNotHasKey('uc', $records[0]);
    }

    #[Test]
    public function executeReturnsErrorForInvalidTable(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'nonexistent_table',
            'field' => 'uid',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsEmptyArrayForNoMatches(): void
    {
        $operation = new GetRecords();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'username',
            'value' => 'nonexistent_user_' . uniqid(),
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $records = $result->getValue();
        self::assertIsArray($records);
        self::assertEmpty($records);
    }
}
