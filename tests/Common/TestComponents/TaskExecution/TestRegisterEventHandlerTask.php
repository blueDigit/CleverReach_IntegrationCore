<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\RegisterEventHandlerTask;

/**
 * Class TestRegisterEventHandlerTask.
 *
 * @package CleverReach\Tests\Common\TestComponents\TaskExecution
 */
class TestRegisterEventHandlerTask extends RegisterEventHandlerTask
{
    private $numberOfExecuteCalls = 0;

    /**
     * Executes task.
     */
    public function execute()
    {
        $this->numberOfExecuteCalls++;

        $this->reportAlive();
        $this->reportProgress(10);
        $this->reportProgress(30);
        $this->reportProgress(100);
    }

    /**
     * Gets number of call executions.
     *
     * @return int Number of call executions.
     */
    public function getNumberOfExecuteCalls()
    {
        return $this->numberOfExecuteCalls;
    }
}
