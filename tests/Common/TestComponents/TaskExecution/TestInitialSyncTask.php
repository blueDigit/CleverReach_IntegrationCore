<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\Infrastructure\TaskExecution\Task;

/**
 * Class TestInitialSyncTask
 *
 * @package CleverReach\Tests\Common\TestComponents\TaskExecution
 */
class TestInitialSyncTask extends InitialSyncTask
{
    public function getNumberOfExecuteCalls()
    {
        return $this->getAttributesSyncTask()->getNumberOfExecuteCalls()
            + $this->getFilterSyncTask()->getNumberOfExecuteCalls()
            + $this->getGroupSyncTask()->getNumberOfExecuteCalls()
            + $this->getProductSearchSyncTask()->getNumberOfExecuteCalls()
            + $this->getRecipientSyncTask()->getNumberOfExecuteCalls()
            + $this->getRegisterEventHandlerSyncTask()->getNumberOfExecuteCalls();
    }

    public function getTaskProgressMap()
    {
        return $this->taskProgressMap;
    }

    /**
     * @return Task|TestAttributesSyncTask
     */
    public function getAttributesSyncTask()
    {
        return $this->getSubTask($this->getAttributesSyncTaskName());
    }

    /**
     * @return Task|TestFilterSyncTask
     */
    public function getFilterSyncTask()
    {
        return $this->getSubTask($this->getFilterSyncTaskName());
    }

    /**
     * @return Task|TestGroupSyncTask
     */
    public function getGroupSyncTask()
    {
        return $this->getSubTask($this->getGroupSyncTaskName());
    }

    /**
     * @return Task|TestProductSearchSyncTask
     */
    public function getProductSearchSyncTask()
    {
        return $this->getSubTask($this->getProductSearchSyncTaskName());
    }

    /**
     * @return Task|TestRecipientSyncTask
     */
    public function getRecipientSyncTask()
    {
        return $this->getSubTask($this->getRecipientSyncTaskName());
    }

    /**
     * @return Task|TestRegisterEventHandlerTask
     */
    public function getRegisterEventHandlerSyncTask()
    {
        return $this->getSubTask($this->getRegisterEventHandlerTaskName());
    }

    /**
     * @inheritdoc
     */
    protected function getAttributesSyncTaskName()
    {
        return TestAttributesSyncTask::getClassName();
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
    protected function getGroupSyncTaskName()
    {
        return TestGroupSyncTask::getClassName();
    }

    /**
     * @inheritdoc
     */
    protected function getProductSearchSyncTaskName()
    {
        return TestProductSearchSyncTask::getClassName();
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
    protected function getRegisterEventHandlerTaskName()
    {
        return TestRegisterEventHandlerTask::getClassName();
    }

    /**
     * @inheritdoc
     */
    protected function makeAttributesSyncTask()
    {
        return new TestAttributesSyncTask();
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
    protected function makeGroupSyncTask()
    {
        return new TestGroupSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function makeProductSearchSyncTask()
    {
        return new TestProductSearchSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function makeRegisterEventHandlerTask()
    {
        return new TestRegisterEventHandlerTask();
    }

    /**
     * @inheritdoc
     */
    protected function makeRecipientSyncTask()
    {
        $allRecipientsIds = $this->getRecipientsIds();

        return new TestRecipientSyncTask($allRecipientsIds, array(), true);
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
