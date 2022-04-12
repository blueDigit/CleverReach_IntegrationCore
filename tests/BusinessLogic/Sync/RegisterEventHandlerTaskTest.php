<?php

namespace BusinessLogic\Sync;

use CleverReach\BusinessLogic\Sync\RegisterEventHandlerTask;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Tests\BusinessLogic\Sync\BaseSyncTest;

/**
 * Class RegisterEventHandlerTaskTest
 *
 * @package BusinessLogic\Sync
 */
class RegisterEventHandlerTaskTest extends BaseSyncTest
{
    public function testProgressReport()
    {
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $firstReportProgress */
        $firstReportProgress = reset($this->eventHistory);
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertNotEmpty($this->eventHistory, 'History of fired report progress events must not be empty');
        $this->assertEquals(
            5,
            $firstReportProgress->getProgressFormatted(),
            'First report progress must be set with 5%.'
        );
        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Last report progress must be set with 100%.'
        );
    }

    /**
     * Test execute method when proxy API throws exception
     */
    public function testExecuteMethodWhenExceptionOccurred()
    {
        $this->proxy->throwExceptionCode = 400;
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->syncTask->execute();
    }

    /**
     * @return RegisterEventHandlerTask
     */
    protected function createSyncTaskInstance()
    {
        return new RegisterEventHandlerTask();
    }
}
