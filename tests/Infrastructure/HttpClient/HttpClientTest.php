<?php

namespace CleverReach\Tests\Infrastructure\TaskExecution;

use CleverReach\BusinessLogic\DTO\OptionsDTO;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{

    /**
     * @var TestHttpClient
     */
    protected $httpClient;
    
    protected function setUp()
    {
        $this->httpClient = new TestHttpClient();
        $proxyInstance = $this;
        new ServiceRegister(array(
            HttpClient::CLASS_NAME => function() use ($proxyInstance) {
                return $proxyInstance->httpClient;
            }
        ));
    }

    /**
     * Test autoconfigure to be successful with default options
     */
    public function testAutoConfigureSuccessfullyWithDefaultOptions()
    {
        $response = new HttpResponse(200, array(), '{"status":"success"}');
        $this->httpClient->setMockResponses(array($response));
        
        $success = $this->httpClient->autoConfigure('POST', 'test.url.com');
        
        $this->assertTrue($success, 'Autoconfigure must be successful if default configuration request passed.');
        $this->assertEquals(0, count($this->httpClient->setAdditionalOptionsCallHistory), 'Set additional options should not be called');
        $this->assertEmpty($this->httpClient->additionalOptions, 'Additional options should remain empty');
    }

    /**
     * Test autoconfigure to be successful with some combination options set
     */
    public function testAutoConfigureSuccessfullyWithSomeCombination()
    {
        $responses = array(
            new HttpResponse(400, array(), '{"status":"failed"}'),
            new HttpResponse(200, array(), '{"status":"success"}'),
        );
        $this->httpClient->setMockResponses($responses);
        $additionalOptionsCombination = array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4));

        $success = $this->httpClient->autoConfigure('POST', 'test.url.com');

        $this->assertTrue($success, 'Autoconfigure must be successful if request passed with some combination.');
        $this->assertEquals(1, count($this->httpClient->setAdditionalOptionsCallHistory), 'Set additional options should be called once');
        $this->assertEquals($additionalOptionsCombination, $this->httpClient->additionalOptions, 'Additional options should be set to first combination');
    }

    /**
     * Test autoconfigure to be successful with some combination options set
     */
    public function testAutoConfigureFailed()
    {
        $responses = array(
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
            new HttpResponse(400, array(), '{}'),
        );
        $this->httpClient->setMockResponses($responses);

        $success = $this->httpClient->autoConfigure('POST', 'test.url.com');

        $this->assertFalse($success, 'Autoconfigure must failed if no combination resulted with request passed.');
        $this->assertEquals(2, count($this->httpClient->setAdditionalOptionsCallHistory), 'Set additional options should be called twice');
        $this->assertEmpty($this->httpClient->additionalOptions, 'Reset additional options method should be called and additional options should be empty.');
    }

    /**
     * Test autoconfigure to be successful with some combination options set
     */
    public function testAutoConfigureFailedWhenThereAreNoResponses()
    {
        $success = $this->httpClient->autoConfigure('POST', 'test.url.com');

        $this->assertFalse($success, 'Autoconfigure must failed if no combination resulted with request passed.');
        $this->assertEquals(2, count($this->httpClient->setAdditionalOptionsCallHistory), 'Set additional options should be called twice');
        $this->assertEmpty($this->httpClient->additionalOptions, 'Reset additional options method should be called and additional options should be empty.');
    }
}
