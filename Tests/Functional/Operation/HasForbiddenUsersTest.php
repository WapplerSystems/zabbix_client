<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\HasForbiddenUsers;

class HasForbiddenUsersTest extends FunctionalTestCase
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
    public function executeReturnsTrueWhenForbiddenUserExists(): void
    {
        $operation = new HasForbiddenUsers();
        $result = $operation->execute(['usernames' => 'admin']);

        self::assertTrue($result->isSuccessful());
        self::assertTrue($result->getValue());
    }

    #[Test]
    public function executeReturnsFalseWhenNoForbiddenUserExists(): void
    {
        $operation = new HasForbiddenUsers();
        $result = $operation->execute(['usernames' => 'nonexistent_user']);

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function executeChecksMultipleUsernames(): void
    {
        $operation = new HasForbiddenUsers();
        $result = $operation->execute(['usernames' => 'nonexistent,admin']);

        self::assertTrue($result->isSuccessful());
        self::assertTrue($result->getValue());
    }

    #[Test]
    public function executeThrowsExceptionWhenNoUsernamesSet(): void
    {
        $operation = new HasForbiddenUsers();

        $this->expectException(\WapplerSystems\ZabbixClient\Exception\InvalidArgumentException::class);
        $operation->execute([]);
    }
}
