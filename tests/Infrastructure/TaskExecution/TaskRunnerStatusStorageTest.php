<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatusStorage;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

class TaskRunnerStatusStorageTest extends TestCase
{

    /** @var TestShopConfiguration */
    private $configuration;

    protected function setUp()
    {
        $configuration = new TestShopConfiguration();

        new ServiceRegister(array(
            TimeProvider::CLASS_NAME         => function () {
                return new TestTimeProvider();
            },
            DefaultLoggerAdapter::CLASS_NAME => function() {
                return new DefaultLogger();
            },
            ShopLoggerAdapter::CLASS_NAME    => function() {
                return new TestShopLogger();
            },
            Configuration::CLASS_NAME        => function() use($configuration) {
                return $configuration;
            },
        ));

        $this->configuration = $configuration;
    }

    public function testSetTaskRunnerWhenItNotExist()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        $this->expectException('CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException');
        $taskRunnerStatusStorage->setStatus($taskStatus);
    }

    public function testSetTaskRunnerWhenItExist()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        try {
            $taskRunnerStatusStorage->setStatus($taskStatus);
        } catch(\Exception $ex) {
            $this->fail('Set task runner status storage should not throw exception.');
        }
    }

    public function testSetTaskRunnerWhenItExistButItIsNotTheSame()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid2', 123456789);

        $this->expectException('CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException');
        $taskRunnerStatusStorage->setStatus($taskStatus);
    }

}