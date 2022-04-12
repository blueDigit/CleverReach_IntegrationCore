<?php

namespace CleverReach\Tests\Common\TestComponents;

use CleverReach\BusinessLogic\Proxy;
use CleverReach\Infrastructure\Utility\HttpResponse;

class TestProxy extends Proxy
{
    public $method;
    public $endpoint;
    public $body;
    public $isAPICalled = false;
    /**
     * @var HttpResponse
     */
    private $response;

    /**
     * @inheritdoc
     */
    public function call($method, $endpoint, $body = array(), $accessToken = '')
    {
        $this->isAPICalled = true;
        $this->method = $method;
        $this->endpoint = $endpoint;
        $this->body = $body;

        $this->validateResponse($this->response);

        return $this->response;
    }

    /**
     * Set expected response
     *
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

}