<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CleverReach\Infrastructure\TaskExecution\Task;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\TaskExecution\BarTask;
use CleverReach\Tests\Common\TestComponents\TaskExecution\FooTask;
use CleverReach\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestTaskRunnerWakeup;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

class QueueTest extends TestCase
{
    /** @var Queue */
    private $queue;

    /** @var InMemoryTestQueueStorage */
    private $queueStorage;

    /** @var TestTimeProvider */
    private $timeProvider;

    /** @var TestTaskRunnerWakeup */
    private $taskRunnerStarter;

    public function setUp()
    {
        $queueStorage = new InMemoryTestQueueStorage();
        $timeProvider = new TestTimeProvider();
        $taskRunnerStarter = new TestTaskRunnerWakeup();


        new ServiceRegister(array(
            TaskQueueStorage::CLASS_NAME => function () use($queueStorage) {
                return $queueStorage;
            },
            TimeProvider::CLASS_NAME => function () use($timeProvider) {
                return $timeProvider;
            },
            TaskRunnerWakeup::CLASS_NAME => function () use($taskRunnerStarter) {
                return $taskRunnerStarter;
            },
            Configuration::CLASS_NAME => function() {
                return new TestShopConfiguration();
            }
        ));

        $this->queueStorage = $queueStorage;
        $this->timeProvider = $timeProvider;
        $this->taskRunnerStarter = $taskRunnerStarter;
        $this->queue = new Queue();
    }

    public function testItShouldBePossibleToFindQueueItemById()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        $foundQueueItem = $this->queue->find($queueItem->getId());

        $this->assertEquals($queueItem->getId(), $foundQueueItem->getId(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getStatus(), $foundQueueItem->getStatus(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getQueueName(), $foundQueueItem->getQueueName(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getLastExecutionProgressBasePoints(), $foundQueueItem->getLastExecutionProgressBasePoints(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getProgressBasePoints(), $foundQueueItem->getProgressBasePoints(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getRetries(), $foundQueueItem->getRetries(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getFailureDescription(), $foundQueueItem->getFailureDescription(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getCreateTimestamp(), $foundQueueItem->getCreateTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getQueueTimestamp(), $foundQueueItem->getQueueTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getLastUpdateTimestamp(), $foundQueueItem->getLastUpdateTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getStartTimestamp(), $foundQueueItem->getStartTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getFinishTimestamp(), $foundQueueItem->getFinishTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getFailTimestamp(), $foundQueueItem->getFailTimestamp(), 'Finding queue item by id must return queue item with given id.');
        $this->assertEquals($queueItem->getEarliestStartTimestamp(), $foundQueueItem->getEarliestStartTimestamp(), 'Finding queue item by id must return queue item with given id.');
    }

    public function testItShouldBePossibleToFindRunningQueueItems()
    {
        // Arrange
        $runningItem1 = $this->generateRunningQueueItem('testQueue', new FooTask());
        $runningItem2 = $this->generateRunningQueueItem('testQueue', new FooTask());
        $runningItem3 = $this->generateRunningQueueItem('otherQueue', new FooTask());
        $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->enqueue('otherQueue', new FooTask());
        $this->queue->enqueue('withoutRunningItemsQueue', new FooTask());
        $queue = new Queue();

        // Act
        $result = $queue->findRunningItems();

        // Assert
        $this->assertCount(3, $result);
        $this->assertTrue($this->inArrayQueueItem($runningItem1, $result),'Find running queue items should contain all running queue items in queue.');
        $this->assertTrue($this->inArrayQueueItem($runningItem2, $result),'Find running queue items should contain all running queue items in queue.');
        $this->assertTrue($this->inArrayQueueItem($runningItem3, $result),'Find running queue items should contain all running queue items in queue.');
    }

    public function testFinOldestQueuedItems()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $earliestQueue1Item = $this->queue->enqueue('queue1', new FooTask());
        $earliestQueue2Item = $this->queue->enqueue('queue2', new FooTask());

        $this->generateRunningQueueItem('queue3', new FooTask());

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->enqueue('queue1', new FooTask());
        $this->queue->enqueue('queue2', new FooTask());
        $this->queue->enqueue('queue3', new FooTask());

        // Act
        $result = $this->queue->findOldestQueuedItems();

        // Assert
        $this->assertCount(2, $result, 'Find earliest queued items should contain only earliest queued items from all queues.');
        $this->asserttrue($this->inArrayQueueItem($earliestQueue1Item, $result),'Find earliest queued items should contain only earliest queued items from all queues.');
        $this->asserttrue($this->inArrayQueueItem($earliestQueue2Item, $result),'Find earliest queued items should contain only earliest queued items from all queues.');
    }

    public function testFindLatestByType()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $this->queue->enqueue('queue1', new FooTask(), 'context');
        $this->queue->enqueue('queue2', new FooTask(), 'context');

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $latestQueueItem = $this->queue->enqueue('queue1', new FooTask(), 'context');

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -1 days'));
        $this->queue->enqueue('queue1', new BarTask(), 'context');
        $globallyLatestQueueItem = $this->queue->enqueue('queue1', new FooTask(), 'different context');

        // Act
        $result = $this->queue->findLatestByType('FooTask', 'context');
        $globalResult = $this->queue->findLatestByType('FooTask');

        // Assert
        $this->assertNotNull($result, 'Find latest by type should contain latest queued item from all queues with given type in given context.');
        $this->assertNotNull($globalResult, 'Find latest by type should contain latest queued item from all queues with given type.');
        $this->assertSame($latestQueueItem->getId(), $result->getId(), 'Find latest by type should return latest queued item with given type from all queues in given context.');
        $this->assertSame($globallyLatestQueueItem->getId(), $globalResult->getId(), 'Find latest by type should return latest queued item with given type from all queues.');
    }

    public function testLimitOfFinOldestQueuedItems()
    {
        // Arrange
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->enqueue('queue5', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -3 days'));
        $this->queue->enqueue('queue4', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -4 days'));
        $earliestQueue3Item = $this->queue->enqueue('queue3', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -5 days'));
        $earliestQueue2Item = $this->queue->enqueue('queue2', new FooTask());
        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -6 days'));
        $earliestQueue1Item = $this->queue->enqueue('queue1', new FooTask());
        $queue = new Queue();

        // Act
        $result = $queue->findOldestQueuedItems(3);

        // Assert
        $this->assertCount(3, $result, 'Find earliest queued items should be limited.');
        $this->asserttrue($this->inArrayQueueItem($earliestQueue1Item, $result),'Find earliest queued items should contain only earliest queued items from all queues.');
        $this->asserttrue($this->inArrayQueueItem($earliestQueue2Item, $result),'Find earliest queued items should contain only earliest queued items from all queues.');
        $this->asserttrue($this->inArrayQueueItem($earliestQueue3Item, $result),'Find earliest queued items should contain only earliest queued items from all queues.');
    }

    public function testItShouldBePossibleEnqueueTaskToQueue()
    {
        // Arrange
        $currentTime = new \DateTime();
        $this->timeProvider->setCurrentLocalTime($currentTime);

        // Act
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        // Assert
        $this->assertEquals(QueueItem::QUEUED, $queueItem->getStatus(), 'When queued queue item must set status to "queued".');
        $this->assertNotNull($queueItem->getId(), 'When queued queue item should be in storage. Id must not be null.');
        $this->assertArrayHasKey($queueItem->getId(), $this->queueStorage->getQueue(), 'When queued queue item should be in storage.');
        $this->assertEquals('testQueue', $queueItem->getQueueName(), 'When queued queue item should be in storage under given queue name.');
        $this->assertSame(0, $queueItem->getLastExecutionProgressBasePoints(), 'When queued queue item should NOT change last execution progress.');
        $this->assertSame(0, $queueItem->getProgressBasePoints(), 'When queued queue item should NOT change progress.');
        $this->assertSame(0, $queueItem->getRetries(), 'When queued queue item must NOT change retries.');
        $this->assertSame('', $queueItem->getFailureDescription(), 'When queued queue item must NOT change failure description.');
        $this->assertSame($currentTime->getTimestamp(), $queueItem->getCreateTimestamp(), 'When queued queue item must set create time.');
        $this->assertSame($currentTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When queued queue item must record queue time.');
        $this->assertNull($queueItem->getStartTimestamp(), 'When queued queue item must NOT change start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When queued queue item must NOT change finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When queued queue item must NOT change fail time.');
        $this->assertNull($queueItem->getEarliestStartTimestamp(), 'When queued queue item must NOT change earliest start time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testItShouldBePossibleToEnqueueTaskInSpecificContext()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask(), 'test');
        $this->assertSame('test', $queueItem->getContext(), 'When queued in specific context queue item context must match provided context.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testTaskEnqueueShouldWakeupTaskRunner()
    {
        // Act
        $this->queue->enqueue('testQueue', new FooTask());

        // Assert
        $wakeupCallHistory = $this->taskRunnerStarter->getMethodCallHistory('wakeup');
        $this->assertCount(1, $wakeupCallHistory, 'Task enqueue must wakeup task runner.');
    }

    public function testItShouldBePossibleToTransitToInProgressStateFromQueued()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($startTime);

        // Act
        $this->queue->start($queueItem);

        // Assert
        $this->assertSame(1, $task->getMethodCallCount('execute'), 'When started queue item must call task execute method.');
        $this->assertEquals(QueueItem::IN_PROGRESS, $queueItem->getStatus(), 'When started queue item must set status to "in_progress".');
        $this->assertSame(0, $queueItem->getRetries(), 'When started queue item must NOT change retries.');
        $this->assertSame('', $queueItem->getFailureDescription(), 'When started queue item must NOT change failure message.');
        $this->assertSame($queuedTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When started queue item must NOT change queue time.');
        $this->assertSame($startTime->getTimestamp(), $queueItem->getStartTimestamp(), 'When started queue item must record start time.');
        $this->assertSame($startTime->getTimestamp(), $queueItem->getLastUpdateTimestamp(), 'When started queue item must set last update time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When started queue item must NOT finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When started queue item must NOT change fail time.');
        $this->assertNull($queueItem->getEarliestStartTimestamp(), 'When started queue item must NOT change earliest start time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testWhenInProgressReportedProgressShouldBeStoredUsingQueue()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);

        // Act
        $task->reportProgress(10.12);

        // Assert
        $this->assertSame(1012, $queueItem->getProgressBasePoints(), 'When started queue item must update task progress.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Progress reported for not started queue item.
     */
    public function testWhenNotInProgressReportedProgressShouldFailJob()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);
        $this->queue->fail($queueItem, 'Test failure description');

        // Act
        $task->reportProgress(25.78);

        // Assert
        $this->fail('Reporting progress on not started queue item should fail.');
    }

    public function testWhenInProgressReportedAliveShouldBeStoredWithCurrentTimeAsLastUpdatedTimestamp()
    {
        // Arrange
        $task = new FooTask();
        $queueItem = $this->queue->enqueue('testQueue', $task);
        $this->queue->start($queueItem);

        $lastSaveTime = new \DateTime();
        $this->timeProvider->setCurrentLocalTime($lastSaveTime);

        // Act
        $task->reportAlive();

        // Assert
        $this->assertSame($lastSaveTime->getTimestamp(), $queueItem->getLastUpdateTimestamp(), 'When task alive reported queue item must be stored.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testItShouldBePossibleToTransitToCompletedStateFromInProgress()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $this->queue->start($queueItem);

        $finishTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($finishTime);

        // Act
        $this->queue->finish($queueItem);

        // Assert
        $this->assertEquals(QueueItem::COMPLETED, $queueItem->getStatus(), 'When finished queue item must set status to "completed".');
        $this->assertSame(0, $queueItem->getRetries(), 'When finished queue item must NOT change retries.');
        $this->assertSame(10000, $queueItem->getProgressBasePoints(), 'When finished queue item must ensure 100% progress value.');
        $this->assertSame('', $queueItem->getFailureDescription(), 'When finished queue item must NOT change failure message.');
        $this->assertSame($queuedTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When finished queue item must NOT change queue time.');
        $this->assertSame($startTime->getTimestamp(), $queueItem->getStartTimestamp(), 'When finished queue item must NOT change start time.');
        $this->assertSame($finishTime->getTimestamp(), $queueItem->getFinishTimestamp(), 'When finished queue item must record finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When finished queue item must NOT change fail time.');
        $this->assertNull($queueItem->getEarliestStartTimestamp(), 'When finished queue item must NOT change earliest start time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testRequeueStartedTaskShouldReturnQueueItemInQueuedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $this->queue->start($queueItem);

        $queueItem->setProgressBasePoints(3081);

        // Act
        $this->queue->requeue($queueItem);

        // Assert
        $this->assertEquals(QueueItem::QUEUED, $queueItem->getStatus(), 'When requeue queue item must set status to "queued".');
        $this->assertSame(0, $queueItem->getRetries(), 'When requeue queue item must not change retries count.');
        $this->assertSame(3081, $queueItem->getLastExecutionProgressBasePoints(), 'When requeue queue item must set last execution progress to current queue item progress value.');
        $this->assertSame($queuedTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When requeue queue item must NOT change queue time.');
        $this->assertNull($queueItem->getStartTimestamp(), 'When requeue queue item must reset start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When requeue queue item must NOT change finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When requeue queue item must NOT change fail time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testFailingLessThanMaxRetryTimesShouldReturnQueueItemInQueuedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $startTime = new \DateTime('now -2 days');
        $this->timeProvider->setCurrentLocalTime($startTime);
        $queueItem->setLastExecutionProgressBasePoints(3123);
        $this->queue->start($queueItem);


        $failTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($failTime);

        // Act
        for ($i = 0; $i < Queue::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem,'Test failure description');
            if ($i < Queue::MAX_RETRIES - 1) {
                $this->queue->start($queueItem);
            }
        }

        // Assert
        $this->assertEquals(QueueItem::QUEUED, $queueItem->getStatus(), 'When failed less than max retry times queue item must set status to "queued".');
        $this->assertSame(5, $queueItem->getRetries(), 'When failed queue item must increase retries by one up to max retries count.');
        $this->assertSame(3123, $queueItem->getLastExecutionProgressBasePoints(), 'When failed queue item must NOT reset last execution progress value.');
        $this->assertSame('Test failure description', $queueItem->getFailureDescription(), 'When failed queue item must set failure description.');
        $this->assertSame($queuedTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When failed queue item must NOT change queue time.');
        $this->assertNull($queueItem->getStartTimestamp(), 'When failed queue item must reset start time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When failed queue item NOT change finish time.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When failed less than max retry times queue item must NOT change fail time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    public function testFailingMoreThanMaxRetryTimesShouldTransitQueueItemInFailedState()
    {
        // Arrange
        $task = new FooTask();

        $queuedTime = new \DateTime('now -3 days');
        $this->timeProvider->setCurrentLocalTime($queuedTime);
        $queueItem = $this->queue->enqueue('testQueue', $task);

        $this->timeProvider->setCurrentLocalTime(new \DateTime('now -2 days'));
        $this->queue->start($queueItem);

        $failTime = new \DateTime('now -1 day');
        $this->timeProvider->setCurrentLocalTime($failTime);

        // Act
        for ($i = 0; $i <= Queue::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < Queue::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Assert
        $this->assertEquals(QueueItem::FAILED, $queueItem->getStatus(), 'When failed more than max retry times queue item must set status to "failed".');
        $this->assertSame(6, $queueItem->getRetries(), 'When failed queue item must increase retries by one up to max retries count.');
        $this->assertSame('Test failure description', $queueItem->getFailureDescription(), 'When failed queue item must set failure description.');
        $this->assertSame($queuedTime->getTimestamp(), $queueItem->getQueueTimestamp(), 'When failed queue item must NOT change queue time.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When failed queue item NOT change finish time.');
        $this->assertSame($failTime->getTimestamp(), $queueItem->getFailTimestamp(), 'When failed more than max retry times queue item must set fail time.');
        $this->assertQueueItemIsSaved($queueItem);
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "in_progress"
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToInProgressStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "created" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "failed"
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToFailedStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "created" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "created" to "completed"
     */
    public function testItShouldBeForbiddenToTransitionFromCreatedToCompletedStatus()
    {
        $queueItem = new QueueItem(new FooTask());

        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "created" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "queued" to "failed"
     */
    public function testItShouldBeForbiddenToTransitionFromQueuedToFailedStatus()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "queued" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "queued" to "completed"
     */
    public function testItShouldBeForbiddenToTransitionFromQueuedToCompletedStatus()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());

        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "queued" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "in_progress" to "in_progress"
     */
    public function testItShouldBeForbiddenToTransitionFromInProgressToInProgressStatus()
    {
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);

        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "in_progress" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "in_progress"
     */
    public function testItShouldBeForbiddenToTransitionFromFailedToInProgressStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        for ($i = 0; $i <= Queue::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < Queue::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "failed" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "failed"
     */
    public function testItShouldBeForbiddenToTransitionFromFailedFailedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        for ($i = 0; $i <= Queue::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < Queue::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "failed" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "failed" to "completed"
     */
    public function testItShouldBeForbiddenToTransitionFromFailedCompletedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        for ($i = 0; $i <= Queue::MAX_RETRIES; $i++) {
            $this->queue->fail($queueItem, 'Test failure description');
            if ($i < Queue::MAX_RETRIES) {
                $this->queue->start($queueItem);
            }
        }

        // Act
        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "failed" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "in_progress"
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToInProgressStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->start($queueItem);

        $this->fail('Queue item status transition from "completed" to "in_progress" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "failed"
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToFailedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->fail($queueItem, 'Test failure description');

        $this->fail('Queue item status transition from "completed" to "failed" should not be allowed.');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Illegal queue item state transition from "completed" to "completed"
     */
    public function testItShouldBeForbiddenToTransitionFromCompletedToCompletedStatus()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queue->finish($queueItem);

        // Act
        $this->queue->finish($queueItem);

        $this->fail('Queue item status transition from "completed" to "completed" should not be allowed.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to enqueue task. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemFailsEnqueueMethodMustFail()
    {
        // Arrange
        $this->queueStorage->disable();

        // Act
        $this->queue->enqueue('testQueue', new FooTask());

        $this->fail('Enqueue queue item must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to start task. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemFailsStartMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queueStorage->disable();

        // Act
        $this->queue->start($queueItem);

        $this->fail('Starting queue item must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to fail task. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemFailsFailMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queueStorage->disable();

        // Act
        $this->queue->fail($queueItem, 'Test failure description.');

        $this->fail('Failing queue item must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to update task progress. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemProgressFailsProgressMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queueStorage->disable();

        // Act
        $this->queue->updateProgress($queueItem, 2095);

        $this->fail('Queue item progress update must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to keep task alive. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemAliveFailsAliveMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queueStorage->disable();

        // Act
        $this->queue->keepAlive($queueItem);

        $this->fail('Queue item keep task alive signal must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @expectedExceptionMessage Unable to finish task. Queue storage failed to save item.
     */
    public function testWhenStoringQueueItemFailsFinishMethodMustFail()
    {
        // Arrange
        $queueItem = $this->queue->enqueue('testQueue', new FooTask());
        $this->queue->start($queueItem);
        $this->queueStorage->disable();

        // Act
        $this->queue->finish($queueItem);

        $this->fail('Finishing queue item must fail with QueueStorageUnavailableException when queue storage save fails.');
    }

    private function assertQueueItemIsSaved(QueueItem $queueItem)
    {
        $storageItem = $this->queueStorage->getQueueItem($queueItem->getId());
        unset($storageItem['serializedTask']); // Do not assert serialized task string

        $this->assertEquals(
            array(
                'id' => $queueItem->getId(),
                'status' => $queueItem->getStatus(),
                'type' => $queueItem->getTaskType(),
                'queueName' => $queueItem->getQueueName(),
                'context' => $queueItem->getContext(),
                'lastExecutionProgress' => $queueItem->getLastExecutionProgressBasePoints(),
                'progress' => $queueItem->getProgressBasePoints(),
                'retries' => $queueItem->getRetries(),
                'failureDescription' => $queueItem->getFailureDescription(),
                'createTimestamp' => $queueItem->getCreateTimestamp(),
                'queueTimestamp' => $queueItem->getQueueTimestamp(),
                'lastUpdateTimestamp' => $queueItem->getLastUpdateTimestamp(),
                'startTimestamp' => $queueItem->getStartTimestamp(),
                'finishTimestamp' => $queueItem->getFinishTimestamp(),
                'failTimestamp' => $queueItem->getFinishTimestamp(),
                'earliestStartTimestamp' => $queueItem->getEarliestStartTimestamp(),
            ),
            $storageItem,
            'Queue item storage data does not match queue item'
        );
    }

    private function generateRunningQueueItem($queueName, Task $task)
    {
        $queueItem = $this->queue->enqueue($queueName, $task);
        $this->queue->start($queueItem);

        return $queueItem;
    }

    private function inArrayQueueItem(QueueItem $needle, array $haystack)
    {
        /** @var QueueItem $queueItem */
        foreach ($haystack as $queueItem) {
            if ($queueItem->getId() === $needle->getId()) {
                return true;
            }
        }

        return false;
    }

}
