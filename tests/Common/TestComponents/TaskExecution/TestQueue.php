<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\Infrastructure\TaskExecution\Queue;
use CleverReach\Infrastructure\TaskExecution\QueueItem;
use CleverReach\Infrastructure\TaskExecution\Task;

class TestQueue extends Queue
{

    private $callHistory = array();
    private $exceptionResponses = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function setExceptionResponse($methodName, $exceptionToThrow)
    {
        $this->exceptionResponses[$methodName] = $exceptionToThrow;
    }

    public function requeue(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['requeue'])) {
            throw $this->exceptionResponses['requeue'];
        }

        $this->callHistory['requeue'][] = array('queueItem' => $queueItem);

        parent::requeue($queueItem);
    }

    public function fail(QueueItem $queueItem, $failureDescription)
    {
        if (!empty($this->exceptionResponses['fail'])) {
            throw $this->exceptionResponses['fail'];
        }

        $this->callHistory['fail'][] = array('queueItem' => $queueItem, 'failureDescription' => $failureDescription);
        parent::fail($queueItem, $failureDescription);
    }

    public function find($id)
    {
        if (!empty($this->exceptionResponses['find'])) {
            throw $this->exceptionResponses['find'];
        }

        $this->callHistory['find'][] = array('id' => $id);
        return parent::find($id);
    }

    public function start(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['start'])) {
            throw $this->exceptionResponses['start'];
        }

        $this->callHistory['start'][] = array('queueItem' => $queueItem);
        parent::start($queueItem);
    }

    public function finish(QueueItem $queueItem)
    {
        if (!empty($this->exceptionResponses['finish'])) {
            throw $this->exceptionResponses['finish'];
        }

        $this->callHistory['finish'][] = array('queueItem' => $queueItem);
        parent::finish($queueItem);
    }

    /**
     * Creates queue item for given task, enqueues in queue with given name and starts it
     *
     * @param $queueName
     * @param Task $task
     *
     * @param int $progress
     * @param int $lastExecutionProgress
     *
     * @return QueueItem
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    public function generateRunningQueueItem($queueName, Task $task, $progress = 0, $lastExecutionProgress = 0)
    {
        $queueItem = $this->enqueue($queueName, $task);
        $queueItem->setProgressBasePoints($progress);
        $queueItem->setLastExecutionProgressBasePoints($lastExecutionProgress);
        $this->start($queueItem);

        return $queueItem;
    }
}