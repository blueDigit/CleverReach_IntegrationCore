<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\BusinessLogic\DTO\RecipientDTO;
use CleverReach\BusinessLogic\Entity\OrderItem;
use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestAttributes;
use CleverReach\Tests\Common\TestComponents\TestProxy;

class RecipientsProxyTest extends ProxyTestBase
{
    const INTEGRATION_ID = 12332;

    const HTTP_SUCCESS_CODE = 200;

    const HTTP_BATCH_SIZE_TO_BIG_CODE = 413;

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

    /**
     * @var int
     */
    private $httpCodeForResponse;

    /**
     * @var string
     */
    private $instanceTagPrefix;

    public function setUp()
    {
        parent::setUp();
        ServiceRegister::registerService(Attributes::CLASS_NAME, function (){
            return new TestAttributes();
        });

        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $configService->setIntegrationId(self::INTEGRATION_ID);
        $this->integrationId = $configService->getIntegrationId();
        $this->fakeResponseBody = $this->getFakeResponseBody('recipientsMassUpdate.json');
        $this->httpCodeForResponse = self::HTTP_SUCCESS_CODE;
        $this->instanceTagPrefix = $configService->getIntegrationName();
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testFetchRecipientSuccessful()
    {
        $body = $this->getFakeResponseBody('fakeRecipientInfo.json');
        $this->prepareResponseForRecipientsEndpoint($body);

        $result = $this->proxy->getRecipientAsArray('123', '123');
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('email', $result);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testFetchRecipientsFail()
    {
        $errorBody = array('error' => array('code' => 404, 'message' => 'Not Found'));
        $this->prepareResponseForRecipientsEndpoint(json_encode($errorBody));
        $results = $this->proxy->getRecipientAsArray('123', '123');
        $this->assertEmpty($results);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRecipientDeleteSuccessful()
    {
        $this->prepareResponseForRecipientsEndpoint('true');

        $result = $this->proxy->deleteRecipient('123', '123');
        self::assertTrue($result);
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRecipientDeleteFail()
    {
        $errorBody = array('error' => array('code' => 404, 'message' => 'Not Found'));
        $this->prepareResponseForRecipientsEndpoint(json_encode($errorBody));
        $this->expectException('\CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->proxy->deleteRecipient('123', '123');
    }

    private function prepareResponseForRecipientsEndpoint($body)
    {
        $response = new HttpResponse($this->httpCodeForResponse, array(), $body);
        $this->httpClient->setMockResponses(array($response));
        $this->proxy = new TestProxy();
        $this->proxy->setResponse($response);
    }

    /**
     * When mass request is successful method must be 'POST', body must be set and endpoint should be as in documentation.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testRecipientsMassUpdateSuccessfulRequestFields()
    {
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithoutOrdersForRequest());

        $this->assertEquals($this->proxy->method, 'POST', 'Method for this call must be POST.');
        $this->assertEquals($this->proxy->endpoint,
            'groups.json/'. $this->integrationId . '/receivers/upsertplus',
            'Endpoint for this call must be upsertplus endpoint.'
        );
        $this->assertNotEmpty($this->proxy->body, 'Body for this call must not be empty.');
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestBodyNumberOfRecipientsSuccessful()
    {
        $recipientsForRequest = $this->getRecipientsWithoutOrdersForRequest();

        $this->prepareMockAndSendMassUpdateRequest($recipientsForRequest);

        $this->assertEquals(
            count($this->proxy->body),
            count($recipientsForRequest),
            'Body of POST method must contain the same number of recipients as sent'
        );
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestBodyContainsAllAttributesSuccessful()
    {
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithoutOrdersForRequest());

        $this->assertTrue(isset($this->proxy->body[0]['attributes']), 'Body must contain attributes');
        $this->assertTrue(isset($this->proxy->body[0]['global_attributes']), 'Body must contain global attributes');
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestBodyForRecipientsWithoutOrdersSuccessful()
    {
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithoutOrdersForRequest());

        $this->assertTrue(empty($this->proxy->body['orders']), 'Body must not contain orders');
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestBodyForRecipientsWithOrdersSuccessful()
    {
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithOrdersForRequest());

        $this->assertTrue(!empty($this->proxy->body[0]['orders']), 'Body must contain orders');
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestBatchSizeTooBig()
    {
        $this->httpCodeForResponse = self::HTTP_BATCH_SIZE_TO_BIG_CODE;

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException');
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithOrdersForRequest());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestFailedForAnyOtherReasonThanBatchSizeTooBig()
    {
        $this->httpCodeForResponse = 400;

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithOrdersForRequest());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testMassUpdateRequestEmptyBodyInResponse()
    {
        $this->fakeResponseBody = 'false';
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithOrdersForRequest());
    }

    /**
     * Tags in request body must be in format "$instanceName-Type.name"
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testTagsFormattingInRequestBody()
    {
        $prefix = $this->instanceTagPrefix ? $this->instanceTagPrefix . '-' : '';
        $expectedTags = array(
            "{$prefix}Group.G2",
            "{$prefix}Group.G3",
            "-{$prefix}Group.G1"
        );

        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithoutOrdersForRequest());

        $this->assertEquals($expectedTags, $this->proxy->body[0]['tags'], 'Format of tags must be proper');
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testSendingOfActivatedDeactivatedField()
    {
        $this->prepareMockAndSendMassUpdateRequest($this->getRecipientsWithoutOrdersForRequest(false, true));

        $this->assertTrue(!isset($this->proxy->body[0]['activated']));
        $this->assertTrue(isset($this->proxy->body[0]['deactivated']));
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testSendingActiveRecipientsViaActivatedField()
    {
        $this->prepareMockAndSendMassUpdateRequest(
            $this->getRecipientsWithoutOrdersForRequest(true, false, true)
        );

        $this->assertTrue(isset($this->proxy->body[0]['activated']), 'Active recipients should send activated timestamp.');
        $this->assertGreaterThan(0, $this->proxy->body[0]['activated'], 'Active recipients should send activated timestamp.');
        $this->assertFalse(isset($this->proxy->body[0]['deactivated']));
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testSendingInactiveRecipientsViaActivatedField()
    {
        $this->prepareMockAndSendMassUpdateRequest(
            $this->getRecipientsWithoutOrdersForRequest(true)
        );

        $this->assertTrue(
            isset($this->proxy->body[0]['activated']),
            'Inactive recipients should send activated timestamp with value 0.'
        );
        $this->assertEquals(
            0,
            $this->proxy->body[0]['activated'],
            'Inactive recipients should send activated timestamp with value 0.'
        );
        $this->assertFalse(isset($this->proxy->body[0]['deactivated']));
    }

    /**
     * @param array $recipients
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function prepareMockAndSendMassUpdateRequest(array $recipients)
    {
        $response = new HttpResponse($this->httpCodeForResponse, array(), $this->fakeResponseBody);
        $this->httpClient->setMockResponses(array($response));
        $this->proxy = new TestProxy();
        $this->proxy->setResponse($response);

        $this->proxy->recipientsMassUpdate($recipients);
    }

    private function getRecipientsWithoutOrdersForRequest(
        $shouldSendActivated = false,
        $shouldSendDeactivated = false,
        $shouldRecipientBeActive = false
    ) {
        $recipients = array();
        $numberOfRecipientsForTest = 100;

        for ($i = 0; $i < $numberOfRecipientsForTest; $i++) {
            $recipient = new Recipient($i . 'test@test.com');
            $recipient->setZip('11000');
            $recipient->setActivated($shouldRecipientBeActive ? date_create_from_format('m/d/Y', '1/10/2014') : null);
            $recipient->setAttributes(array('testAttribute'=> 'attr'));
            $recipient->setRegistered(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setBirthday(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setCity('City');
            $recipient->setCompany('Company');
            $recipient->setCustomerNumber('124');
            $recipient->setFirstName('First name');
            $recipient->setLanguage('SR');
            $recipient->setLastName('Last name');
            $recipient->setNewsletterSubscription(false);
            $recipient->setActivated(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setDeactivated(date_create_from_format('m/d/Y', '1/10/2014'));
            $recipient->setPhone('2222344');
            $recipient->setCountry('RS');
            $recipient->setSalutation('Mr');
            $recipient->setShop('Mage');
            $recipient->setSource('Mage');
            $recipient->setState('RS');
            $recipient->setStreet('NN');
            $recipient->setTags(new TagCollection(array(new Tag('G2', 'Group'), new Tag('G3', 'Group'))));
            $recipient->setTitle('Dr');
            $recipientDTO = new RecipientDTO(
                $recipient,
                new TagCollection(array(new Tag('G1', 'Group'))),
                false,
                $shouldSendActivated,
                $shouldSendDeactivated
            );
            $recipients[] = $recipientDTO;
        }

        return $recipients;
    }

    private function getRecipientsWithOrdersForRequest()
    {
        $recipients = $this->getRecipientsWithoutOrdersForRequest();
        $order1 = new OrderItem('123', '222', '1');
        $order1->setPrice(10);
        $order1->setProductId('222');
        $order1->setAmount(5);
        $order1->setAttributes(array('test' => 'test'));
        $order1->setBrand('Brand');
        $order1->setCurrency('EUR');
        $order1->setStamp(date_create_from_format('m/d/Y', '1/10/2014'));
        $order1->setProductCategory(array('Cat'));
        $order1->setProductSource('Source');
        $orders = array($order1, new OrderItem('223', '333', '2'));

        /** @var RecipientDTO $recipient */
        foreach ($recipients as &$recipient) {
            $recipient->setIncludeOrdersActivated(true);
            $recipient->getRecipientEntity()->setOrders($orders);
        }

        return $recipients;
    }
}
