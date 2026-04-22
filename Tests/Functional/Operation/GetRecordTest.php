<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\GetRecord;

class GetRecordTest extends FunctionalTestCase
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
    public function executeReturnsRecordByUid(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'uid',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $record = $result->getValue();
        self::assertIsArray($record);
        self::assertEquals(1, $record['uid']);
    }

    #[Test]
    public function executeStripsProtectedFields(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'uid',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        $record = $result->getValue();
        self::assertArrayNotHasKey('password', $record);
        self::assertArrayNotHasKey('uc', $record);
    }

    #[Test]
    public function executeReturnsFalseForNonExistentRecord(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'uid',
            'value' => '99999',
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function executeReturnsErrorForInvalidTable(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'nonexistent_table',
            'field' => 'uid',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function executeReturnsErrorForInvalidField(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'nonexistent_field',
            'value' => '1',
            'checkEnableFields' => false,
        ]);

        self::assertFalse($result->isSuccessful());
    }

    #[Test]
    public function executeByUsernameField(): void
    {
        $operation = new GetRecord();
        $result = $operation->execute([
            'table' => 'be_users',
            'field' => 'username',
            'value' => 'admin',
            'checkEnableFields' => false,
        ]);

        self::assertTrue($result->isSuccessful());
        $record = $result->getValue();
        self::assertSame('admin', $record['username']);
    }
}
