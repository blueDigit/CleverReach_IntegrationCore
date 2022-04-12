<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\ProductSearchSyncTask;

class TestProductSearchSyncTask extends ProductSearchSyncTask
{
    private $numberOfExecuteCalls = 0;

    public function execute()
    {
        $this->reportAlive();
        $this->reportProgress(100);

        $this->numberOfExecuteCalls++;
    }

    public function getNumberOfExecuteCalls()
    {
        return $this->numberOfExecuteCalls;
    }
}