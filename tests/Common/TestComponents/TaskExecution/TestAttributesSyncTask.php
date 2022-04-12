<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\AttributesSyncTask;

class TestAttributesSyncTask extends AttributesSyncTask
{
    private $numberOfExecuteCalls = 0;

    public function execute()
    {
        $this->reportAlive();
        $this->reportProgress(5);
        $this->reportProgress(100);
        $this->numberOfExecuteCalls++;
    }

    public function getNumberOfExecuteCalls()
    {
        return $this->numberOfExecuteCalls;
    }
    
}