<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;
use CleverReach\BusinessLogic\Utility\Rule;
use InvalidArgumentException;

class FiltersTest extends ProxyTestBase
{

    /** @var  string */
    private $fakeResponseBody;

    /** @var  string */
    private $fakeAllFilters;

    /** @var  int */
    private $integrationId;

    /** @var  string */
    private $groupName;

    /** @var Filter  */
    private $filter;

    public function setUp()
    {
        parent::setUp();
        $this->fakeResponseBody = json_encode(array('id' => 123, 'success' => true));
        $this->fakeAllFilters = $this->getFakeResponseBody('fakeAllFilters.json');
        $this->groupName = 'group1';
        $this->integrationId = 123;
    }

    public function testIfCreateFilterIsCalledWithAppropriateArgumentsAndAPICalled()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeResponseBody);

        $proxy->createFilter($this->filter, $this->integrationId);

        $type = gettype($this->filter->getAllRules());

        $this->assertTrue(($type == 'array') && is_numeric($this->integrationId));
        $this->assertTrue($proxy->isAPICalled);
    }

    public function testIfCreateFilterIsCalledWithWrongArguments()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(400, array(), $this->fakeResponseBody);

        //Set up wrong parameters
        $this->integrationId = 'TestIntegration';

        $this->expectException('InvalidArgumentException');
        $proxy->createFilter($this->filter, $this->integrationId);
    }

    public function testWhenCreateFilterIsCalledWithWrongArgumentsAndAPINotCalled()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(400, array(), $this->fakeResponseBody);

        $this->integrationId = 'TestIntegration';

        try {
            $proxy->createFilter($this->filter, $this->integrationId);
        } catch (InvalidArgumentException $exception) {
            $this->assertFalse($proxy->isAPICalled);
        }
    }

    public function testIfCreateFilterCallsAppropriateAPI()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeResponseBody);

        $proxy->createFilter($this->filter, $this->integrationId);

        $this->assertEquals($proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($proxy->endpoint, 'groups.json/' . $this->integrationId . '/filters',
            'Endpoint for this call must be groups.json/{groupID}/filters.');
        $this->assertEquals($proxy->body, $this->filter->toArray(), 'Body for this call must be set.');
    }

    public function testIfCreateFilterReturnsAppropriateAPIResponse()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeResponseBody);

        $results = $proxy->createFilter($this->filter, $this->integrationId);
        $expected = json_decode($this->fakeResponseBody, true);

        $this->assertEquals($expected['id'], $results['id']);
    }

    public function testIfCreateFilterReturnsWrongAPIResponse()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), '{"error: {code: 400}"}');

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->createFilter($this->filter, $this->integrationId);
    }

    public function testGetAllCRSegmentsCallsAppropriateAPIAndResponseIsExpected()
    {
        //Arrange test client and proxy
        $proxy = $this->initTest(200, array(), $this->fakeAllFilters);

        /** @var Filter[] $response */
        $response = $proxy->getAllFilters($this->integrationId);

        $allFilters = json_decode($this->fakeAllFilters, true);

        $this->assertEquals($proxy->method, 'GET', 'Method for this call must be GET.');
        $this->assertEquals($proxy->endpoint, 'groups.json/' . $this->integrationId . '/filters',
            'Endpoint for this call must be groups.json/{groupID}/filters.');
        $this->assertEquals($proxy->body, array(),'Body for this call must be empty');
        $this->assertEquals(count($allFilters), count($response),'Unexpected response');
        $this->assertEquals($allFilters[0]['id'], $response[0]->getId());
    }

    public function testIfDeleteFilterCallsAppropriateApiMethodAndResponseIsTrue()
    {
        $filterID = 111;
        $proxy = $this->initTest(200, array(), 'true');

        $response = $proxy->deleteFilter($filterID, $this->integrationId);


        $this->assertEquals($proxy->method, 'DELETE', 'Method for this call must be GET.');
        $this->assertEquals($proxy->endpoint, 'groups.json/' . $this->integrationId . '/filters/' . $filterID,
            'Endpoint for this call must be groups.json/{groupID}/filters/{filterID}.');
        $this->assertEquals($proxy->body, array(),'Body for this call must be empty');
        $this->assertTrue($response,'Unexpected response');
    }

    /**
     * @throws HttpRequestException
     */
    public function testIfDeleteFilterReturnsErrorFromCRApi()
    {

        $filterID = 111;

        //setting error response body
        $errorCode = 404;
        $errorMessage = 'Not Found: invalid filter';

        $fakeResponse = array(
            'error' => array('code' => $errorCode, 'message' => $errorMessage)
        );

        $fakeResponse = json_encode($fakeResponse);

        $proxy = $this->initTest(200, array(), $fakeResponse);

        try {
            $proxy->deleteFilter($filterID, $this->integrationId);
        } catch (HttpRequestException $exception) {
            $this->assertEquals($errorCode, $exception->getCode());
            $this->assertContains($errorMessage, $exception->getMessage());
        }
    }

    /**
     * @throws HttpRequestException
     */
    public function testIfDeleteFilterReturnsDefaultError()
    {

        $filterID = 111;

        //setting error response body
        $errorCode = 400;
        $errorMessage = '';

        $fakeResponse = array(
            'error' => array('code' => $errorCode, 'message' => $errorMessage)
        );

        $fakeResponse = json_encode($fakeResponse);

        $proxy = $this->initTest(200, array(), $fakeResponse);

        try {
            $proxy->deleteFilter($filterID, $this->integrationId);
        } catch (HttpRequestException $exception) {
            $this->assertEquals($errorCode, $exception->getCode());
            $this->assertEquals('Deleting filter failed. Invalid response body from CR.', $exception->getMessage());
        }
    }

    /**
     *
     *
     * @throws InvalidArgumentException
     */
    public function testIfDeleteMethodIsCalledWithWrongArguments()
    {
        //set up wrong argument
        $filterID = 'test123';

        $proxy = $this->initTest(200, array(), true);
        $this->expectException('InvalidArgumentException');
        $proxy->deleteFilter($filterID, $this->integrationId);
    }

    private function initTest($status, $headers, $body)
    {
        $response = new HttpResponse($status, $headers, $body);
        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $rule = new Rule('tags', 'contains', $this->groupName);
        $this->filter = new Filter($this->groupName, $rule);

        return $proxy;
    }
}