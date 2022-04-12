<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Sync\UpdateTagsToNewSystemTask;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\BusinessLogic\Utility\Rule;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestUpdateTagsToNewSystemTask;
use CleverReach\Tests\Common\TestComponents\TestRecipients;

class UpdateTagsToNewSystemTaskTest extends BaseSyncTest
{
    /**
     * @var UpdateTagsToNewSystemTask $syncTask
     */
    protected $syncTask;

    /**
     * @var array $prefixedTagsForDelete
     */
    private $prefixedTagsForDelete = array('PREF-G-tag1', 'PREF-G-tag2');
    /**
     * @var Recipients $recipients
     */
    private $recipients;

    /**
     * @return UpdateTagsToNewSystemTask
     */
    protected function createSyncTaskInstance()
    {
        $this->recipients = new TestRecipients();

        $taskInstance = $this;
        ServiceRegister::registerService(
            Recipients::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->recipients;
            }
        );

        return new UpdateTagsToNewSystemTask($this->prefixedTagsForDelete);
    }

    public function testNumberOfExecuteCallsForSuccessfulProcess()
    {
        $numberOfSynchronizationTasks = 3;
        $updateTagsToNewSystemTask = new TestUpdateTagsToNewSystemTask(array());
        $updateTagsToNewSystemTask->execute();

        $numberOfCalls = $updateTagsToNewSystemTask->getNumberOfExecuteCalls();
        $this->assertEquals($numberOfCalls, $numberOfSynchronizationTasks);
    }

    public function testIfFactoryMethodsMakeProperObjects()
    {
        $updateTagsToNewSystemTask = new TestUpdateTagsToNewSystemTask(array());
        $deletePrefixedFilterSyncTask = $updateTagsToNewSystemTask->getDeletePrefixedFilterTask();
        $filterSyncTask = $updateTagsToNewSystemTask->getFilterSyncTask();
        $recipientSyncTask = $updateTagsToNewSystemTask->getRecipientSyncTask();

        $this->assertInstanceOf(
            'CleverReach\BusinessLogic\Sync\DeletePrefixedFilterSyncTask',
            $deletePrefixedFilterSyncTask
        );

        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\FilterSyncTask', $filterSyncTask);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\RecipientSyncTask', $recipientSyncTask);
    }

    public function testProgressMap()
    {
        $this->initFakeFilters();
        $this->syncTask->execute();
        $progressMap = $this->syncTask->getProgressByTask();
        $this->assertCount(3, $progressMap);
        $this->assertArrayHasKey('deletePrefixedFilters', $progressMap);
        $this->assertArrayHasKey('filters', $progressMap);
        $this->assertArrayHasKey('recipients', $progressMap);
    }

    public function testResumingTaskAfterDeserialize()
    {
        $this->initFakeFilters();

        parent::testResumingTaskAfterDeserialize();
    }

    public function testExecuteMethod()
    {
        $this->initFakeFilters();
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    private function initFakeFilters()
    {
        $this->proxy->fakeCreateFilterResponse = array('id' => 16);

        $rule = new Rule('tags', 'contains', 'PREF-G-tag1');
        $filter = new Filter('G-Tag1', $rule);
        $filter->setId(119);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', 'PREF-G-tag2');
        $filter = new Filter('G-Tag2', $rule);
        $filter->setId(110);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', 'PREF-G-tag3');
        $filter = new Filter('G-Tag3', $rule);
        $filter->setId(111);
        $this->proxy->fakeAllFilters[] = $filter;
    }
}
