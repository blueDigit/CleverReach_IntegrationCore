<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestProxy;

class RecipientsDeactivateProxyTest extends ProxyTestBase
{
    const INTEGRATION_ID = 12332;

    const HTTP_SUCCESS_CODE = 200;

    /**
     * @var string
     */
    private $fakeResponseBody;

    /**
     * @var int
     */
    private $integrationId;

    /**
     * @var TestProxy
     */
    private $proxy;

    public function setUp()
    {
        parent::setUp();

        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setIntegrationId(self::INTEGRATION_ID);
        $this->integrationId = $configService->getIntegrationId();
        $this->fakeResponseBody = $this->getFakeResponseBody('recipientsDeactivate.json');
    }

    public function testDeactivateNewsletterStatusSuccessfulRequestFields()
    {
        $response = new HttpResponse(200, array(), $this->fakeResponseBody);
        $this->httpClient->setMockResponses(array($response));
        $this->proxy = new TestProxy();
        $this->proxy->setResponse($response);

        $this->proxy->updateNewsletterStatus(array('test0@email.com', 'test1@email.com'));

        $this->assertEquals($this->proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($this->proxy->endpoint,
            'groups.json/'. $this->integrationId . '/receivers/upsertplus',
            'Endpoint for this call must be upsertplus endpoint.'
        );
        $this->assertNotEmpty($this->proxy->body, 'Body for this call must not be empty.');
    }

    public function testDeactivateNewsletterStatusFailureRequestFields()
    {
        $response = new HttpResponse(400, array(), $this->fakeResponseBody);
        $this->httpClient->setMockResponses(array($response));
        $this->proxy = new TestProxy();
        $this->proxy->setResponse($response);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->proxy->updateNewsletterStatus(array('test0@email.com', 'test1@email.com'));
    }

    public function testDeactivateNewsletterStatusInvalidBodyRequestFields()
    {
        $response = new HttpResponse(200, array(), '');
        $this->httpClient->setMockResponses(array($response));
        $this->proxy = new TestProxy();
        $this->proxy->setResponse($response);

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->proxy->updateNewsletterStatus(array('test0@email.com', 'test1@email.com'));
    }

}