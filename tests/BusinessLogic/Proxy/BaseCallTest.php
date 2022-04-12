<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use CleverReach\BusinessLogic\Proxy;

class BaseCallTest extends ProxyTestBase
{

    /**
     * Test proxy call method when token not set in configuration
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     */
    public function testCallMethodWhenTokenNotSet()
    {
        // Arrange test client and proxy
        $this->shopConfig->setAccessToken(null);
        $this->httpClient->setMockResponses(array(
            new HttpResponse(200, array(), ''),
        ));

        $proxy = new Proxy($this->httpClient);

        $this->expectException('CleverReach\Infrastructure\Exceptions\InvalidConfigurationException');
        $proxy->call('GET', 'some/endpoint');
    }

    /**
     * Test proxy call method is using token from configuration for request header
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     */
    public function testCallMethodUsingAppropriateTokenForRequestHeader()
    {
        // Arrange test client and proxy
        $this->httpClient->setMockResponses(array(
            new HttpResponse(200, array(), ''),
        ));

        $proxy = new Proxy();
        $proxy->call('GET', 'some/endpoint');

        $requestHeader = $this->httpClient->getLastRequestHeaders();

        $this->assertArrayHasKey('token', $requestHeader);
        $this->assertEquals($requestHeader['token'], 'Authorization: Bearer test_access_token');
    }

    /**
     * Test proxy call method is passing appropriate parameters to request
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     */
    public function testCallMethodPassingAppropriateParametersToRequest()
    {
        // Arrange test client and proxy
        $this->httpClient->setMockResponses(array(
            new HttpResponse(200, array(), ''),
        ));

        $proxy = new Proxy($this->httpClient);

        // Act - call call method
        $proxy->call('POST', 'some/endpoint', array('filter' => 'name'));

        $request = $this->httpClient->getLastRequest();

        // Assert passed parameters
        $this->assertArrayHasKey('type', $request, 'Request type must be set');
        $this->assertEquals($request['type'], TestHttpClient::REQUEST_TYPE_SYNCHRONOUS, 'Appropriate synchronous request is not called.');

        $this->assertArrayHasKey('method', $request, 'Request must have method.');
        $this->assertEquals($request['method'], 'POST', 'Appropriate method is not set in request.');

        $this->assertArrayHasKey('url', $request, 'Request must have endpoint url.');
        $this->assertEquals($request['url'], 'https://rest.cleverreach.com/v3/some/endpoint', 'Appropriate endpoint url is not set in request.');

        $this->assertArrayHasKey('body', $request, 'Request must have body.');
        $this->assertEquals($request['body'], json_encode(array('filter' => 'name')), 'Appropriate body is not set in request.');
    }

    /**
     * Test proxy call method when response status is bad
     */
    public function testCallMethodWithErrorResponseStatus()
    {
        // Arrange test client and proxy
        $this->httpClient->setMockResponses(array(
            new HttpResponse(400, array(), ''),
        ));

        $proxy = new Proxy($this->httpClient);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->call('GET', 'some/endpoint');
    }

    /**
     * Test proxy call method when client is not authorized
     */
    public function testCallMethodWithNotAuthorizedResponse()
    {
        // Arrange test client and proxy
        $this->httpClient->setMockResponses(array(
            new HttpResponse(401, array(), ''),
        ));

        $proxy = new Proxy($this->httpClient);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException');
        $proxy->call('GET', 'some/endpoint');
    }

    /**
     * Test proxy call method when there is no response
     */
    public function testCallMethodWithNoResponse()
    {
        // Arrange test client and proxy
        $this->httpClient->setMockResponses(array());
        $proxy = new Proxy($this->httpClient);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException');
        $proxy->call('GET', 'some/endpoint');
    }

    /**
     * Test proxy call method when response should be formatted array
     */
    public function testCallMethodWhenResponseIsReturned()
    {
        // Arrange test client and proxy
        $response = new HttpResponse(200, array(), '{"id": "1"}');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new Proxy($this->httpClient);

        $result = $proxy->call('GET', 'some/endpoint');

        $this->assertInstanceOf(HttpResponse::CLASS_NAME, $result, 'Response must be HttpResponse type.');
        $this->assertEquals($response, $result, 'Response must be same.');
    }
}