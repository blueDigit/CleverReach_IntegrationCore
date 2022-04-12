<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\UpdateTagsToNewSystemTask;
use CleverReach\Infrastructure\TaskExecution\Task;

class TestUpdateTagsToNewSystemTask extends UpdateTagsToNewSystemTask
{
    public function getNumberOfExecuteCalls()
    {
        return $this->getDeletePrefixedFilterTask()->getNumberOfExecuteCalls()
            + $this->getFilterSyncTask()->getNumberOfExecuteCalls()
            + $this->getRecipientSyncTask()->getNumberOfExecuteCalls();
    }

    public function getTaskProgressMap()
    {
        return $this->taskProgressMap;
    }

    /**
     * @return Task|TestDeletePrefixedFilterSyncTask
     */
    public function getDeletePrefixedFilterTask()
    {
        return $this->getSubTask($this->getDeletePrefixedFilterTaskName());
    }

    /**
     * @return Task|TestFilterSyncTask
     */
    public function getFilterSyncTask()
    {
        return $this->getSubTask($this->getFilterSyncTaskName());
    }

    /**
     * @return Task|TestRecipientSyncTask
     */
    public function getRecipientSyncTask()
    {
        return $this->getSubTask($this->getRecipientSyncTaskName());
    }

    /**
     * @inheritdoc
     */
    protected function getDeletePrefixedFilterTaskName()
    {
        return TestDeletePrefixedFilterSyncTask::getClassName();
    }

    /**
     * @inheritdoc
     */
    protected function getFilterSyncTaskName()
    {
        return TestFilterSyncTask::getClassName();
    }

    /**
     * @inheritdoc
     */
    protected function getRecipientSyncTaskName()
    {
        return TestRecipientSyncTask::getClassName();
    }

    /**
     * @inheritdoc
     */
    protected function makeDeletePrefixedFilterSyncTask()
    {
        return new TestDeletePrefixedFilterSyncTask(array());
    }

    /**
     * @inheritdoc
     */
    protected function makeFilterSyncTask()
    {
        return new TestFilterSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function makeRecipientSyncTask()
    {
        $allRecipientsIds = $this->getRecipientsIds();

        return new TestRecipientSyncTask($allRecipientsIds, array(), false);
    }

    private function getRecipientsIds()
    {
        $ids = array();
        $numberOfRecipientIdForTest = 300;

        for ($i = 0; $i < $numberOfRecipientIdForTest; $i++) {
            $ids[] = $i . 'test@test.com';
        }

        return $ids;
    }
}