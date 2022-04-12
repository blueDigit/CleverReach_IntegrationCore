<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;

class GroupsTest extends ProxyTestBase
{
    /** @var  string $fakeAllGroups */
    private $fakeAllGroups;

    /** @var  string $fakeCreatedGroup */
    private $fakeCreatedGroup;

    /** @var  string */
    private $serviceName;

    public function setUp()
    {
        parent::setUp();
        $this->fakeAllGroups = $this->getFakeResponseBody('getAllGroups.json');
        $this->fakeCreatedGroup = $this->getFakeResponseBody('createGroup.json');
        $this->serviceName = 'Proxy Group Test';
    }

    /**
     * Test group searching and checking existence when response status is OK
     */
    public function testIfGroupExistsCallsAppropriateAPI()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeAllGroups);

        //Act - call method on proxy
        $proxy->getGroupId('test service');

        $this->assertEquals($proxy->method, 'GET', 'Method for this call must be GET.');
        $this->assertEquals($proxy->endpoint, 'groups.json', 'Endpoint for this call must be groups.json.');
        $this->assertEquals($proxy->body, array(), 'Body for this call must be empty.');
    }

    /**
     * Checks if return value is true when true is expected
     */
    public function testIfGroupExistsReturnsTrue()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeAllGroups);
        $return = $proxy->getGroupId('Test list2');

        $this->assertEquals(1221567, $return, 'Return value in this case must be true.');
    }

    public function testIfGroupExistsReturnsFalse()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeAllGroups);

        $return = $proxy->getGroupId('Test NoExists');

        $this->assertEquals(null, $return,'Return value in this case must be false.');
    }


    public function testIfCreateGroupCallsAppropriateAPI()
    {
        $proxy = $this->initTest(200, array(), $this->fakeCreatedGroup);

        $proxy->createGroup($this->serviceName);

        $this->assertEquals($proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($proxy->endpoint, 'groups.json', 'Endpoint for this call must be groups.json.');
        $this->assertEquals($proxy->body, array('name' => $this->serviceName), 'Body for this call must be set.');
    }

    /**
     * Test adding new group when serviceName is not provided
     *
     * @throws InvalidArgumentException
     **/
    public function testCreateGroupInvalidArgument()
    {
        $proxy = $this->initTest(400, array(), 'Bad request');
        $this->serviceName = "";

        $this->expectException('InvalidArgumentException');
        $proxy->createGroup($this->serviceName);
    }

    public function testCreateGroupInvalidArgumentDoesntCallApiAfterException()
    {
        $proxy = $this->initTest(400, array(), '');
        $this->serviceName = "";

        try {
            $proxy->createGroup($this->serviceName);
        } catch (\InvalidArgumentException $exception) {
            $this->assertFalse($proxy->isAPICalled);
        }
    }

    public function testCreateGroupBadResponse()
    {
        $proxy = $this->initTest(400, array(), '');
        $this->serviceName = "test11";

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->createGroup($this->serviceName);
    }

    public function testCreateGroupReturnsUnexpectedAPIResponseBody()
    {
        $proxy = $this->initTest(200, array(), '');
        $this->serviceName = "test11";

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->createGroup($this->serviceName);
    }

    private function initTest($status, $headers, $body)
    {
        $response = new HttpResponse($status, $headers, $body);
        $proxy = new TestProxy();
        $proxy->setResponse($response);

        return $proxy;
    }
}