<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\HasInsecureBackendUsers;

class HasInsecureBackendUsersTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_insecure.csv');
    }

    /**
     * Fixture contains four users:
     *  - secure_admin:   admin, has MFA, has email        -> not insecure
     *  - insecure_admin: admin, no MFA, has email         -> counted as adminWithoutMfa
     *  - _cli_:          admin, no MFA, no email, no login -> system user, must be ignored
     *  - stale_editor:   non-admin, no email, old login   -> counted as staleUser
     */
    #[Test]
    public function executeExcludesSystemCliUserFromCounts(): void
    {
        $operation = new HasInsecureBackendUsers();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        $value = $result->getValue();

        // _cli_ is admin/no-MFA/no-email but must not raise any counter.
        self::assertSame(2, $value['totalAdmins'], 'totalAdmins must exclude the _cli_ user');
        self::assertSame(1, $value['adminsWithoutMfa'], 'only the real admin without MFA is counted');
        self::assertSame(0, $value['noEmail'], '_cli_ is the only user without email and must be excluded');
        self::assertSame(1, $value['staleUsers']);
    }
}