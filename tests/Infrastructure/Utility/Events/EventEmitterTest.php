<?php

namespace CleverReach\Tests\Infrastructure\Utility\Events;

use CleverReach\Infrastructure\Utility\Events\Event;
use CleverReach\Tests\Common\TestComponents\Utility\Events\TestBarEvent;
use CleverReach\Tests\Common\TestComponents\Utility\Events\TestFooEvent;
use CleverReach\Tests\Common\TestComponents\Utility\Events\TestEventEmitter;
use PHPUnit\Framework\TestCase;

class EventEmitterTest extends TestCase
{

    public function testItShouldBePossibleToFireEventWithoutAnySubscribedHandlers()
    {
        $emitter = new TestEventEmitter();

        try {
            $emitter->fire(new TestFooEvent());
        } catch (\Exception $ex) {
            $this->fail('It should be possible to fire event without any subscribers.');
        }
    }

    public function testItShouldBePossibleToSubscribeMultipleHandlersToSameEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(TestFooEvent::CLASS_NAME, function (TestFooEvent $event) use (&$handler1Event) {
            $handler1Event = $event;
        });
        $emitter->when(TestFooEvent::CLASS_NAME, function (TestFooEvent $event) use (&$handler2Event) {
            $handler2Event = $event;
        });

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNotNull($handler2Event, 'Event emitter must call each subscribed handler.');
    }

    public function testItShouldBePossibleToNotifyOnlySubscribersOnSpecificEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(TestFooEvent::CLASS_NAME, function (TestFooEvent $event) use (&$handler1Event) {
            $handler1Event = $event;
        });
        $emitter->when(TestBarEvent::CLASS_NAME, function (Event $event) use (&$handler2Event) {
            $handler2Event = $event;
        });

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNull($handler2Event, 'Event emitter must call only handlers subscribed to fired event.');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Handler exception
     */
    public function testItShouldBePossibleToTriggerExceptionFromInsideHandlerMethod()
    {
        $emitter = new TestEventEmitter();
        $emitter->when(TestFooEvent::CLASS_NAME, function () {
            throw new \Exception('Handler exception');
        });

        $emitter->fire(new TestFooEvent());

        $this->fail('It should be possible to throw exception from event handler code.');
    }

}
