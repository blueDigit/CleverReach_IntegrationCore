<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CleverReach\Infrastructure\TaskExecution\QueueItemStarter;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Tests\Common\TestComponents\TaskExecution\FooTask;
use CleverReach\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestQueue;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

class QueueItemStarterTest extends TestCase
{
    /** @var TestQueue */
    private $queue;

    /** @var InMemoryTestQueueStorage */
    private $queueStorage;

    /** @var TestTimeProvider */
    private $timeProvider;

    /** @var TestShopLogger */
    private $logger;

    /** @var Configuration */
    private $shopConfiguration;

    public function setUp()
    {
        $queueStorage = new InMemoryTestQueueStorage();
        $timeProvider = new TestTimeProvider();
        $queue = new TestQueue();
        $shopLogger = new TestShopLogger();
        $shopConfiguration = new TestShopConfiguration();
        $shopConfiguration->setIntegrationName('Shop1');
        $shopConfiguration->setUserAccountId('04596');

        new ServiceRegister(array(
            TaskQueueStorage::CLASS_NAME => function () use($queueStorage) {
                return $queueStorage;
            },
            TimeProvider::CLASS_NAME => function () use($timeProvider) {
                return $timeProvider;
            },
            TaskRunnerWakeup::CLASS_NAME => function () {
                return new TestTaskRunnerWakeup();
            },
            Queue::CLASS_NAME => function () use($queue) {
                return $queue;
            },
            DefaultLoggerAdapter::CLASS_NAME => function() {
                return new DefaultLogger();
            },
            ShopLoggerAdapter::CLASS_NAME => function() use ($shopLogger) {
                return $shopLogger;
            },
            Configuration::CLASS_NAME => function() use ($shopConfiguration) {
                return $shopConfiguration;
            },
            HttpClient::CLASS_NAME => function() {
                return new TestHttpClient();
            }
        ));

        // Initialize logger component with new set of log adapters
        new Logger();

        $this->queueStorage = $queueStorage;
        $this->timeProvider = $timeProvider;
        $this->queue = $queue;
        $this->logger = $shopLogger;
        $this->shopConfiguration = $shopConfiguration;
    }

    public function testRunningItemStarter()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('test', new FooTask());
        $itemStarter = new QueueItemStarter($queueItem->getId());

        // Act
        $itemStarter->run();

        // Assert
        $findCallHistory = $this->queue->getMethodCallHistory('find');
        $startCallHistory = $this->queue->getMethodCallHistory('start');
        $finishCallHistory = $this->queue->getMethodCallHistory('finish');
        $this->assertCount(1, $findCallHistory);
        $this->assertCount(1, $startCallHistory);
        $this->assertCount(1, $finishCallHistory);
        $this->assertEquals($queueItem->getId(), $findCallHistory[0]['id']);
        /** @var QueueItem $startedQueueItem */
        $startedQueueItem = $startCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $startedQueueItem->getId());
        /** @var QueueItem $finishedQueueItem */
        $finishedQueueItem = $finishCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $finishedQueueItem->getId());
    }

    public function testItemStarterMustBeRunnableAfterDeserialization()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('test', new FooTask());
        $itemStarter = new QueueItemStarter($queueItem->getId());
        /** @var QueueItemStarter $unserializedItemStarter */
        $unserializedItemStarter = unserialize(serialize($itemStarter));

        // Act
        $unserializedItemStarter->run();

        // Assert
        $findCallHistory = $this->queue->getMethodCallHistory('find');
        $startCallHistory = $this->queue->getMethodCallHistory('start');
        $this->assertCount(1, $findCallHistory);
        $this->assertCount(1, $startCallHistory);
        $this->assertEquals($queueItem->getId(), $findCallHistory[0]['id']);
        /** @var QueueItem $startedQueueItem */
        $startedQueueItem = $startCallHistory[0]['queueItem'];
        $this->assertEquals($queueItem->getId(), $startedQueueItem->getId());
    }

    public function testItemsStarterMustSetTaskExecutionContextInConfiguraion()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('test', new FooTask(), 'test');
        $itemStarter = new QueueItemStarter($queueItem->getId());

        // Act
        $itemStarter->run();

        // Assert
        $this->assertSame('test', $this->shopConfiguration->getContext(), 'Item starter must set task context before task execution.');
    }

    public function testItemsStarterExceptionHandling()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('test', new FooTask());
        $itemStarter = new QueueItemStarter($queueItem->getId());
        $this->queue->setExceptionResponse(
            'start',
            new QueueStorageUnavailableException('Simulate unavailable queue storage.')
        );

        // Act
        $itemStarter->run();

        // Assert
        $this->assertContains('Fail to start task execution.', $this->logger->data->getMessage(), 'Item starter must log exception messages.');
        $this->assertContains(strval($itemStarter->getQueueItemId()), $this->logger->data->getMessage(), 'Item starter must log failed item id in message.');
    }
}
