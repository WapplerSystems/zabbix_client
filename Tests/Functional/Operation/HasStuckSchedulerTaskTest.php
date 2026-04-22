<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Operation;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Operation\HasStuckSchedulerTask;

class HasStuckSchedulerTaskTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    // scheduler loaded via composer require-dev

    #[Test]
    public function returnsFalseWhenNoTasksStuck(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/scheduler_tasks_ok.csv');

        $operation = new HasStuckSchedulerTask();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function returnsTrueWhenTaskIsStuck(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/scheduler_tasks_stuck.csv');

        $operation = new HasStuckSchedulerTask();
        $result = $operation->execute(['maxRunningHours' => 1]);

        self::assertTrue($result->isSuccessful());
        self::assertTrue($result->getValue());
    }

    #[Test]
    public function returnsFalseWhenNoTasksExist(): void
    {
        $operation = new HasStuckSchedulerTask();
        $result = $operation->execute();

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }

    #[Test]
    public function respectsMaxRunningHoursParameter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/scheduler_tasks_stuck.csv');

        // With very high maxRunningHours, no task should be considered stuck
        $operation = new HasStuckSchedulerTask();
        $result = $operation->execute(['maxRunningHours' => 999999]);

        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->getValue());
    }
}
