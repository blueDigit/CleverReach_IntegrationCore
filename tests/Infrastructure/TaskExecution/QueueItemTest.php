<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\TaskExecution\FooTask;
use CleverReach\Tests\Common\TestComponents\TaskExecution\InMemoryTestQueueStorage;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

class QueueItemTest extends TestCase
{
    /** @var InMemoryTestQueueStorage */
    private $queueStorage;

    /** @var TestTimeProvider */
    private $timeProvider;

    protected function setUp()
    {
        $queueStorage = new InMemoryTestQueueStorage();
        $timeProvider = new TestTimeProvider();

        new ServiceRegister(array(
            TaskQueueStorage::CLASS_NAME => function () use($queueStorage) {
                return $queueStorage;
            },
            TimeProvider::CLASS_NAME => function () use($timeProvider) {
                return $timeProvider;
            },
        ));

        $this->timeProvider = $timeProvider;
        $this->queueStorage = $queueStorage;
    }

    public function testWhenQueueItemIsCreatedItShouldBeInCreatedStatus()
    {
        $task = new FooTask();
        $queueItem = new QueueItem($task);

        $this->assertEquals(QueueItem::CREATED, $queueItem->getStatus(), 'When created queue item must set status to "created".');
        $this->assertEquals($task->getType(), $queueItem->getTaskType(), 'When created queue item must set record task type.');
        $this->assertNull($queueItem->getId(), 'When created queue item should not be in storage. Id must be null.');
        $this->assertNull($queueItem->getQueueName(), 'When created queue should not be in storage. Queue name must be null.');
        $this->assertSame(0, $queueItem->getLastExecutionProgressBasePoints(), 'When created queue item must set last execution progress to 0.');
        $this->assertSame(0, $queueItem->getProgressBasePoints(), 'When created queue item must set progress to 0.');
        $this->assertSame(0, $queueItem->getRetries(), 'When created queue item must set retries to 0.');
        $this->assertSame('', $queueItem->getFailureDescription(), 'When created queue item must set failure description to empty string.');
        $this->assertEquals(serialize($task), $queueItem->getSerializedTask(), 'When created queue item must record given task.');
        $this->assertSame($this->timeProvider->getCurrentLocalTime()->getTimestamp(), $queueItem->getCreateTimestamp(), 'When created queue item must record create time.');
        $this->assertNull($queueItem->getQueueTimestamp(), 'When created queue item must set queue time to null.');
        $this->assertNull($queueItem->getStartTimestamp(), 'When created queue item must set start time to null.');
        $this->assertNull($queueItem->getFinishTimestamp(), 'When created queue item must set finish time to null.');
        $this->assertNull($queueItem->getFailTimestamp(), 'When created queue item must set fail time to null.');
        $this->assertNull($queueItem->getEarliestStartTimestamp(), 'When created queue item must set earliest start time to null.');
    }

    public function testItShouldBePossibleToCreateQueueItemWithSerializedTask()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem();

        $queueItem->setSerializedTask(serialize($task));

        /** @var FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame($task->getDependency1(), $actualTask->getDependency1());
        $this->assertSame($task->getDependency2(), $actualTask->getDependency2());
    }

    /**
     * @expectedException \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException
     */
    public function testQueueItemShouldThrowExceptionWhenSerializationFails()
    {
        $task = new FooTask('test task', 123);
        $queueItem = new QueueItem();

        $queueItem->setSerializedTask('invalid serialized task content');

        /** @var FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame($task->getDependency1(), $actualTask->getDependency1());
        $this->assertSame($task->getDependency2(), $actualTask->getDependency2());
    }

    public function testItShouldUpdateTaskWhenSettingSerializedTask()
    {
        $newTask = new FooTask('new task', 123);
        $queueItem = new QueueItem(new FooTask('initial task', 1));

        $queueItem->setSerializedTask(serialize($newTask));

        /** @var FooTask $actualTask */
        $actualTask = $queueItem->getTask();
        $this->assertSame('new task', $actualTask->getDependency1(), 'Setting serialized task must update task instance.');
        $this->assertSame(123, $actualTask->getDependency2(), 'Setting serialized task must update task instance.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid QueueItem status: "Not supported". Status must be one of "created", "queued", "in_progress", "completed" or "failed" values.
     */
    public function testItShouldNotBePossibleToSetNotSupportedStatus()
    {
        $queueItem = new QueueItem();

        $queueItem->setStatus('Not supported');

        $this->fail('Setting not supported status should fail.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Last execution progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetNegativeLastExecutionProgress()
    {
        $queueItem = new QueueItem();

         $queueItem->setLastExecutionProgressBasePoints(-1);

        $this->fail('QueueItem must refuse setting negative last execution progress with InvalidArgumentException.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Last execution progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetMoreThan10000ForLastExecutionProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setLastExecutionProgressBasePoints(10001);

        $this->fail('QueueItem must refuse setting greater than 100 last execution progress values with InvalidArgumentException.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetNegativeProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(-1);

        $this->fail('QueueItem must refuse setting negative progress with InvalidArgumentException.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToSetMoreThan100ForProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(10001);

        $this->fail('QueueItem must refuse setting greater than 100 progress values with InvalidArgumentException.');
    }

    public function testItShouldBePossibleToGetFormattedProgressValue()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints(2548);

        $this->assertSame(25.48, $queueItem->getProgressFormatted(), 'Formatted progress should be string representation of progress percentage rounded to two decimals.');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Progress percentage must be value between 0 and 100.
     */
    public function testItShouldNotBePossibleToReportNonIntegerValueForProgress()
    {
        $queueItem = new QueueItem();

        $queueItem->setProgressBasePoints('50%');

        $this->fail('QueueItem must refuse setting non integer progress values with InvalidArgumentException.');
    }

    public function testItShouldBePossibleToSetTaskExecutionContext()
    {
        $queueItem = new QueueItem();

        $queueItem->setContext('test');

        $this->assertSame('test', $queueItem->getContext(), 'Queue item must return proper task execution context.');
    }
}
