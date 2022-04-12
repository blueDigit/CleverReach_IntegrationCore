<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\Infrastructure\Interfaces\Exposed\Runnable;

class TestAsyncProcessStarter implements AsyncProcessStarter
{
    private $callHistory = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function start(Runnable $runner)
    {
        $this->callHistory['start'][] = array('runner' => $runner);
    }
}