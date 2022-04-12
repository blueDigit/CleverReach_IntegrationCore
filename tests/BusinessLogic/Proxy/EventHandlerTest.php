<?php

namespace BusinessLogic\Proxy;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\BusinessLogic\Proxy\ProxyTestBase;
use CleverReach\Tests\Common\TestComponents\TestProxy;

/**
 * Class EventHandlerTest
 *
 * @package BusinessLogic\Proxy
 */
class EventHandlerTest extends ProxyTestBase
{
    /**
     * @var array
     */
    private static $eventParameters = array(
        'url' => 'https://example.com/hookhandler?param1=paramvalue',
        'event' => 'receiver',
        'condition' => '12345',
        'verify' => 'myMegaFancyverifyToken',
    );

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRegisterEventHandlerMethodWhenCallIsSuccessful()
    {
        $proxy = $this->initTest(200, array(), '{"success":true,"call_token":"test_call_token"}');

        $result = $proxy->registerEventHandler(self::$eventParameters);
        self::assertEquals('test_call_token', $result);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRegisterEventHandlerMethodWhenCallIsUnsuccessful()
    {
        $proxy = $this->initTest(200, array(), '{"success":false,"call_token":"test_call_token"}');
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->registerEventHandler(self::$eventParameters);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRegisterEventMethodWhenNoCallToken()
    {
        $proxy = $this->initTest(200, array(), '{"success":true}');
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->registerEventHandler(self::$eventParameters);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetRecipientWhenCallIsSuccessful()
    {
        $proxy = $this->initTest(200, array(), $this->getFakeResponseBody('recipientInfo.json'));
        $recipient = $proxy->getRecipient(240803, 4285);
        self::assertInstanceOf('CleverReach\BusinessLogic\Entity\Recipient', $recipient);
        self::assertNotEmpty($recipient->getEmail());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetRecipientWhenCallIsUnsuccessful()
    {
        $proxy = $this->initTest(200, array(), '{}');
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $proxy->getRecipient(240803, 4285);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testDeleteEventHandlerWhenCallIsUnsuccessful()
    {
        $proxy = $this->initTest(400, array(), '');
        self::assertEquals(false, $proxy->deleteReceiverEvent());
    }

    /**
     * @param int $status
     * @param array $headers
     * @param string $body
     *
     * @return TestProxy
     */
    private function initTest($status, $headers, $body)
    {
        $response = new HttpResponse($status, $headers, $body);
        $proxy = new TestProxy();
        $proxy->setResponse($response);

        return $proxy;
    }
}
