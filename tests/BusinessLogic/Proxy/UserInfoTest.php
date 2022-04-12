<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;

class UserInfoTest extends ProxyTestBase
{

    private $fakeResponseBodyGetUserInfo;

    public function setUp()
    {
        parent::setUp();
        $this->fakeResponseBodyGetUserInfo = $this->getFakeResponseBody('getUserInfo.json');
    }

    /**
     * Test get user info API call when response status is ok
     */
    public function testGetUserWithOkResponseStatus()
    {
        $response = new HttpResponse(200, array(), $this->fakeResponseBodyGetUserInfo);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $result = $proxy->getUserInfo('access_token');

        $this->assertEquals($proxy->method, 'GET', 'Method for this call must be GET.');
        $this->assertEquals($proxy->endpoint, 'debug/whoami.json', 'Endpoint for this call must be debug/whoami.json.');
        $this->assertEquals($proxy->body, array(), 'Body for this call must be empty.');

        // Assert response
        $this->assertEquals(json_decode($this->fakeResponseBodyGetUserInfo, true), $result, 'Result for this method must be formatted array.');
    }

    /**
     * Test get user info API call when response body is not valid
     */
    public function testGetUserWhenResponseBodyNotValid()
    {
        $response = new HttpResponse(200, array(), 'Some non valid body');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->getUserInfo('access_token');
    }

    /**
     * Test get user info API call when access token is not valid
     */
    public function testGetUserWhenAccessTokenNotValid()
    {
        $response = new HttpResponse(401, array(), $this->fakeResponseBodyGetUserInfo);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);

        $result = $proxy->getUserInfo('access_token');

        $this->assertEmpty($result, 'Result must be empty when access token is not valid.');
    }

}