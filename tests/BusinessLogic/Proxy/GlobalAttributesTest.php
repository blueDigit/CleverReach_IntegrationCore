<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;

class GlobalAttributesTest extends ProxyTestBase
{

    private $fakeResponseBodyGetAttributes;
    private $fakeResponseBodyCreateUpdateAttribute;
    private $formattedFakeResponseBodyGetAttributes;
    private $attributeData;
    private $attributeId;

    public function setUp()
    {
        parent::setUp();
        $this->fakeResponseBodyGetAttributes = $this->getFakeResponseBody('getAllGlobalAttributes.json');
        $this->formattedFakeResponseBodyGetAttributes = $this->getFormattedFakeResponseBody('getAllGlobalAttributes.json');
        $this->fakeResponseBodyCreateUpdateAttribute = $this->getFakeResponseBody('createAndUpdateGlobalAttribute.json');
        $this->attributeId = 1111700;
        $this->attributeData = array(
            'name' => 'FirstName',
            'type' => 'text',
            'description' => 'Description',
            'preview_value' => 'real name',
            'default_value' => 'Bruce'
        );
    }

    /**
     * Test get all global attributes API call when response status is ok
     */
    public function testGetAllGlobalAttributesWithOkResponseStatus()
    {
        $response = new HttpResponse(200, array(), $this->fakeResponseBodyGetAttributes);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $result = $proxy->getAllGlobalAttributes();

        $this->assertEquals($proxy->method, 'GET', 'Method for this call must be GET.');
        $this->assertEquals($proxy->endpoint, 'attributes.json', 'Endpoint for this call must be attributes.json.');
        $this->assertEquals($proxy->body, array(), 'Body for this call must be empty.');

        $responseBody = json_decode($this->formattedFakeResponseBodyGetAttributes, true);

        // Assert response
        $this->assertEquals(
            array_change_key_case($responseBody), 
            array_change_key_case($result),
            'Result for this method must be formatted array.'
        );
    }

    /**
     * Test get all global attributes API call when response body is not valid
     */
    public function testGetAllGlobalAttributesWhenResponseBodyNotValid()
    {
        // Arrange test client and proxy
        $response = new HttpResponse(200, array(), 'Some non valid body');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        // Act - call method on proxy
        $result = $proxy->getAllGlobalAttributes();

        // Assert response
        $this->assertEmpty($result, 'Result must be empty when response body is not valid.');
    }

    /**
     * Test create attribute API call when response status is ok
     */
    public function testCreateAttributeWithOkResponseStatus()
    {
        $response = new HttpResponse(200, array(), $this->fakeResponseBodyCreateUpdateAttribute);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $proxy->createGlobalAttribute($this->attributeData);

        // Assert if call method is called with appropriate parameters
        $this->assertEquals($proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($proxy->endpoint, 'attributes.json', 'Endpoint for this call must be attributes.json.');
        $this->assertEquals($proxy->body, $this->attributeData, 'Body for this call must be set.');
    }

    /**
     * Test create attribute API call when attribute id is not set in response body
     */
    public function testCreateAttributeWWhenAttributeIdNotSetInResponse()
    {
        // Arrange test client and proxy
        $response = new HttpResponse(200, array(), 'Some non valid response.');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->createGlobalAttribute($this->attributeData);
    }

    /**
     * Test update attribute API call when response status is ok
     */
    public function testUpdateAttributeWithOkResponseStatus()
    {
        $response = new HttpResponse(200, array(), $this->fakeResponseBodyCreateUpdateAttribute);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $proxy->updateGlobalAttribute($this->attributeId, $this->attributeData);

        $this->assertEquals($proxy->method, 'PUT', 'Method for this call must be PUT.');
        $this->assertEquals($proxy->endpoint, 'attributes.json/' . $this->attributeId, 'Endpoint for this call must be attributes.json.');
        $this->assertEquals($proxy->body, $this->attributeData, 'Body for this call must be set.');
    }

    /**
     * Test update attribute API call when attribute id is not set in response body
     */
    public function testUpdateAttributeWhenAttributeIdNotSetInResponse()
    {
        $response = new HttpResponse(200, array(), 'Some non valid response.');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->updateGlobalAttribute($this->attributeId, $this->attributeData);
    }

}