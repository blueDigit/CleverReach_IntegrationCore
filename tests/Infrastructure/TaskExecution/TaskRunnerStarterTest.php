<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\TaskRunner;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStarter;
use CleverReach\Infrastructure\TaskExecution\TaskRunnerStatus;
use CleverReach\Infrastructure\Utility\GuidProvider;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestAsyncProcessStarter;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestRunnerStatusStorage;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestTaskRunner;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use CleverReach\Tests\Common\TestComponents\Utility\TestGuidProvider;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

class TaskRunnerStarterTest extends TestCase
{
    /** @var TestAsyncProcessStarter */
    private $asyncProcessStarter;

    /** @var TestTaskRunner */
    private $taskRunner;

    /** @var TestRunnerStatusStorage */
    private $runnerStatusStorage;

    /** @var TestTimeProvider */
    private $timeProvider;

    /** @var TestGuidProvider */
    private $guidProvider;

    /** @var TestShopLogger */
    private $logger;

    /** @var TaskRunnerStarter */
    private $runnerStarter;

    /** @var string */
    private $guid;

    protected function setUp()
    {
        $asyncProcessStarter = new TestAsyncProcessStarter();
        $runnerStatusStorage = new TestRunnerStatusStorage();
        $taskRunner = new TestTaskRunner();
        $timeProvider = new TestTimeProvider();
        $guidProvider = new TestGuidProvider();

        $shopLogger = new TestShopLogger();

        new ServiceRegister(array(
            AsyncProcessStarter::CLASS_NAME => function () use($asyncProcessStarter) {
                return $asyncProcessStarter;
            },
            TaskRunnerStatusStorage::CLASS_NAME => function () use($runnerStatusStorage) {
                return $runnerStatusStorage;
            },
            TaskRunner::CLASS_NAME => function() use($taskRunner) {
                return $taskRunner;
            },
            TimeProvider::CLASS_NAME              => function () use($timeProvider) {
                return $timeProvider;
            },
            GuidProvider::CLASS_NAME              => function () use($guidProvider) {
                return $guidProvider;
            },
            DefaultLoggerAdapter::CLASS_NAME      => function() {
                return new DefaultLogger();
            },
            ShopLoggerAdapter::CLASS_NAME         => function() use ($shopLogger) {
                return $shopLogger;
            },
            Configuration::CLASS_NAME             => function() {
                return new TestShopConfiguration();
            },
            HttpClient::CLASS_NAME                => function() {
                return new TestHttpClient();
            },
            TaskRunnerWakeupInterface::CLASS_NAME => function() {
                return new TestTaskRunnerWakeup();
            }
        ));

        new Logger();

        $this->asyncProcessStarter = $asyncProcessStarter;
        $this->runnerStatusStorage = $runnerStatusStorage;
        $this->taskRunner = $taskRunner;
        $this->timeProvider = $timeProvider;
        $this->guidProvider = $guidProvider;
        $this->logger = $shopLogger;

        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->guid = 'test_runner_guid';
        $this->runnerStarter = new TaskRunnerStarter($this->guid);
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $currentTimestamp));
    }

    public function testTaskRunnerIsStartedWithProperGuid()
    {
        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

    public function testRunningTaskRunnerWhenExpired()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $expiredTimestamp = $currentTimestamp - TaskRunnerStatus::MAX_ALIVE_TIME - 1;
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus($this->guid, $expiredTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner is expired.');
        $this->assertContains('Failed to run task runner', $this->logger->data->getMessage(),'Run call must throw TaskRunnerRunException when runner is expired');
        $this->assertContains('Runner is expired.', $this->logger->data->getMessage(), 'Debug message must be logged when trying to run expired task runner.');
    }

    public function testRunningTaskRunnerWithActiveGuidDoNotMatchGuidGeneratedWithWakeup()
    {
        // Arrange
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();
        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus('different_active_guid', $currentTimestamp));

        // Act
        $this->runnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $this->assertCount(0, $runCallHistory, 'Run call must fail when runner guid is not set as active runner guid.');
        $this->assertContains('Failed to run task runner.', $this->logger->data->getMessage(),'Run call must throw TaskRunnerRunException when runner guid is not set as active runner guid.');
        $this->assertContains('Runner guid is not set as active.', $this->logger->data->getMessage(), 'Debug message must be logged when trying to run task runner with guid that is not set as active runner guid.');
    }

    public function testRunWhenRunnerStatusServiceIsUnavailable()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new TaskRunnerStatusStorageUnavailableException('Simulation for unavailable storage exception.')
        );

        // Act
        $this->runnerStarter->run();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(0, $startCallHistory, 'Run call when tasks status storage is unavailable must fail.');
        $this->assertContains('Failed to run task runner.', $this->logger->data->getMessage(),'Run call must throw TaskRunnerRunException when runner status storage is unavailable.');
        $this->assertContains('Runner status storage unavailable.', $this->logger->data->getMessage());
    }

    public function testRunInCaseOfUnexpectedException()
    {
        $this->runnerStatusStorage->setExceptionResponse(
            'getStatus',
            new \Exception('Simulation for unexpected exception.')
        );

        // Act
        $this->runnerStarter->run();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(0, $startCallHistory, 'Run call in case of unexpected exception must fail.');
        $this->assertContains('Failed to run task runner.', $this->logger->data->getMessage(),'Run call must throw TaskRunnerRunException when unexpected exception occurs.');
        $this->assertContains('Unexpected error occurred.', $this->logger->data->getMessage());
    }

    public function testTaskStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        /** @var TaskRunnerStarter $unserializedRunnerStarter */
        $unserializedRunnerStarter = unserialize(serialize($this->runnerStarter));

        // Act
        $unserializedRunnerStarter->run();

        // Assert
        $runCallHistory = $this->taskRunner->getMethodCallHistory('run');
        $setGuidCallHistory = $this->taskRunner->getMethodCallHistory('setGuid');
        $this->assertCount(1, $runCallHistory, 'Run call must start runner.');
        $this->assertCount(1, $setGuidCallHistory, 'Run call must set runner guid.');
        $this->assertEquals($this->guid, $setGuidCallHistory[0]['guid'], 'Run call must set runner guid.');
    }

}
