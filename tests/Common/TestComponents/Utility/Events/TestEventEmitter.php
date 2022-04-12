<?php
namespace CleverReach\Tests\Common\TestComponents\Utility\Events;

use CleverReach\Infrastructure\Utility\Events\Event;
use CleverReach\Infrastructure\Utility\Events\EventEmitter;

class TestEventEmitter extends EventEmitter
{
    public function fire(Event $event)
    {
        parent::fire($event);
    }
}