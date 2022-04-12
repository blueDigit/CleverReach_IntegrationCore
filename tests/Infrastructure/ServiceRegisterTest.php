<?php

namespace CleverReach\Tests\Infrastructure;

use PHPUnit\Framework\TestCase;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\TestService;

class ServiceRegisterTest extends TestCase
{

    /**
     * Test simple registering the service and getting the instance back
     *
     * @throws \InvalidArgumentException
     */
    public function testSimpleRegisterAndGet()
    {
        new ServiceRegister(array(
            TestService::CLASS_NAME => function() {
                return new TestService('first');
            },
        ));

        $result = ServiceRegister::getService(TestService::CLASS_NAME);

        $this->assertInstanceOf(TestService::CLASS_NAME, $result, 'Failed to retrieve registered instance of interface.');
    }

    /**
     * Test throwing exception when service is not registered
     *
     * @throws \InvalidArgumentException
     */
    public function testGettingServiceWhenItIsNotRegistered()
    {
        $this->expectException('\InvalidArgumentException');
        ServiceRegister::getService('SomeService');
    }

    /**
     * Test registering service that is already registered
     *
     * @throws \InvalidArgumentException
     */
    public function testRegisteringServiceThatIsAlreadyRegistered()
    {
        new ServiceRegister(array(
            TestService::CLASS_NAME => function() {
                return new TestService('first');
            },
        ));

        $this->expectException('\InvalidArgumentException');
        ServiceRegister::registerService(TestService::CLASS_NAME, function() {
            return new TestService('second');
        });
    }

    /**
     * Test throwing exception when trying to register service with non callable delegate
     *
     * @throws \InvalidArgumentException
     */
    public function testRegisteringServiceWhenDelegateIsNotCallable()
    {
        $this->expectException('\InvalidArgumentException');
        new ServiceRegister(array(
            TestService::CLASS_NAME => 'Some non callable string',
        ));
    }
    
}
