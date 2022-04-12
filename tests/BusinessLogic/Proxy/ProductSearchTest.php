<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\BusinessLogic\Proxy;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;

class ProductSearchTest extends ProxyTestBase
{
    /**
     * Test add product search API call when response status is ok.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testAddOrUpdateProductSearchOkResponseStatus()
    {
        $fakeJSONResponse = '{
            "id": "16818",
             "name": "My shop - Product search",
             "login": "My shop - Product search",
             "password": "s3Sdsdf34dfsWSW",
             "url": "",
             "interface": "my_products",
             "client_id": "129148",
             "group_id": "0",
             "active": 1,
             "stamp": 1512660984,
             "last_import": 0
        }';
        // Arrange test client and proxy
        $response = new HttpResponse(200, array(), $fakeJSONResponse);
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);
        $data = array(
            'name' => 'My shop - Product search',
            'url' => 'http://myshop.com/endpoint',
            'password' => 's3Sdsdf34dfsWSW',
        );

        // Act - call method on proxy
        $proxy->addOrUpdateProductSearch($data);

        // Assert if call method is called with appropriate parameters
        $this->assertEquals($proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($proxy->endpoint, 'mycontent.json', 'Endpoint for this call must be mycontent.json.');
        $this->assertEquals($proxy->body, $data, 'Body for this call must be set.');
    }

    /**
     * Test add product search API call when response body is not valid
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testAddOrUpdateProductSearchBadResponseBody()
    {
        $response = new HttpResponse(200, array(), 'false');
        $this->httpClient->setMockResponses(array($response));

        $proxy = new TestProxy();
        $proxy->setResponse($response);
        $data = array(
            'name' => 'My shop - Product search',
            'url' => 'http://myshop.com/endpoint',
            'password' => 's3Sdsdf34dfsWSW',
        );

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->addOrUpdateProductSearch($data);
    }

    /**
     * Test execute method when product search endpoint already registered
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testAddOrUpdateProductSearchWhenEndpointAlreadySet()
    {
        // test conflict by URL
        $this->addOrUpdateProductSearchWhenEndpointAlreadySet(
            '{
                "id": "19968",
                "name": "My shop",
                "password": "",
                "url": "http://myshop.com/endpoint",
                "cors": false,
                "last_import": 0
            }'
        );

        // test conflict by Name
        $this->addOrUpdateProductSearchWhenEndpointAlreadySet(
            '{
                "id": "19968",
                "name": "My shop - Product search",
                "password": "",
                "url": "http://myshop.com",
                "cors": false,
                "last_import": 0
            }'
        );
    }

    /**
     * Test execute method when product search endpoint already registered
     *
     * @param string $matchData Matching data
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function addOrUpdateProductSearchWhenEndpointAlreadySet($matchData)
    {
        // first call is conflict
        $response1 = new HttpResponse(409, array(), '');
        // second call gets current content
        $response2 = new HttpResponse(
            200,
            array(),
            '[
              {
                "id": "19289",
                "name": "Demo Shop Logeecom - Product Search",
                "password": "5b9907d6e262e8.12264386",
                "url": "https://demo.com/productSearch",
                "cors": false,
                "last_import": 0
              },' . $matchData . '
            ]'
        );
        // third call deletes content
        $response3 = new HttpResponse(200, array(), '');
        // forth call creates content
        $response4 = new HttpResponse(
            200,
            array(),
            '{
                  "id": "19970",
                  "name": "My shop - Product search",
                  "password": "s3Sdsdf34dfsWSW",
                  "url": "http://myshop.com/endpoint",
                  "cors": false,
                  "last_import": 0
             }'
        );
        $this->httpClient->setMockResponses(array($response1, $response2, $response3, $response4));

        $proxy = new Proxy();
        $data = array(
            'name' => 'My shop - Product search',
            'url' => 'http://myshop.com/endpoint',
            'password' => 's3Sdsdf34dfsWSW',
        );

        $id = $proxy->addOrUpdateProductSearch($data);
        $this->assertEquals(
            'Product search endpoint already exists on CR.',
            $this->shopLogger->data->getMessage(),
            'Log info message for already created product search endpoint must be set.'
        );

        $this->assertEquals(19970, $id, 'Log info message for already created product search endpoint must be set.');
    }

}