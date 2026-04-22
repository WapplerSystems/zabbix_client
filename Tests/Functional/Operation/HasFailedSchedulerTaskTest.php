<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\HasFailedSchedulerTask;

class HasFailedSchedulerTaskTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
        'typo3/cms-scheduler',
    ];

    #[Test]
    public function returnsFalseWhenNoTasksFailed(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/scheduler_tasks_ok.csv');

        $operation = new HasFailedSchedulerTask();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function returnsTrueWhenTaskHasFailed(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/scheduler_tasks_failed.csv');

        $operation = new HasFailedSchedulerTask();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertTrue($result->getValue());
    }

    #[Test]
    public function returnsFalseWhenNoTasksExist(): void
    {
        $operation = new HasFailedSchedulerTask();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }
}
