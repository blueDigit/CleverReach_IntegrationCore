<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\RecipientSyncTask;

class TestRecipientSyncTask extends RecipientSyncTask
{
    private $numberOfExecuteCalls = 0;

    public function execute()
    {
        $this->numberOfExecuteCalls++;
        
        $this->reportAlive();
        $this->reportProgress(10);
        $this->reportProgress(30);
        $this->reportProgress(77);
        $this->reportProgress(100);
    }

    public function getNumberOfExecuteCalls()
    {
        return $this->numberOfExecuteCalls;
    }
}