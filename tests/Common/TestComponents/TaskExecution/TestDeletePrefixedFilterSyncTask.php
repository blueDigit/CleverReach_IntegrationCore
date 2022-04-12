<?php
namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\BusinessLogic\Sync\DeletePrefixedFilterSyncTask;

class TestDeletePrefixedFilterSyncTask extends DeletePrefixedFilterSyncTask
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