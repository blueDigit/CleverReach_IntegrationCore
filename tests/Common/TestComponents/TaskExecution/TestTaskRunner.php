<?php

namespace CleverReach\Tests\Common\TestComponents\TaskExecution;

use CleverReach\Infrastructure\TaskExecution\TaskRunner;

class TestTaskRunner extends TaskRunner
{
    private $callHistory = array();

    public function getMethodCallHistory($methodName)
    {
        return !empty($this->callHistory[$methodName]) ? $this->callHistory[$methodName] : array();
    }

    public function run()
    {
        $this->callHistory['run'][] = array();
    }

    public function setGuid($guid)
    {
        $this->callHistory['setGuid'][] = array('guid' => $guid);
        parent::setGuid($guid);
    }

}