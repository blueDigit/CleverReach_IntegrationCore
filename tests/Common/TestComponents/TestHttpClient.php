<?php

namespace CleverReach\Tests\Common\TestComponents;

use CleverReach\BusinessLogic\DTO\OptionsDTO;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\HttpResponse;

class TestHttpClient extends HttpClient
{

    const REQUEST_TYPE_SYNCHRONOUS = 1;
    const REQUEST_TYPE_ASYNCHRONOUS = 2;
    
    public $calledAsync = false;
    
    public $additionalOptions;
    public $setAdditionalOptionsCallHistory = array();

    /**
     * @var array
     */
    private $responses;

    /**
     * @var array
     */
    private $history;

    /**
     * Create, log and send request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     * @return HttpResponse
     */
    public function request($method, $url, $headers = array(), $body = '')
    {
        return $this->sendHttpRequest($method, $url, $headers, $body);
    }

    /**
     * Create, log and send request asynchronously
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    public function requestAsync($method, $url, $headers = array(), $body = '')
    {
        $this->sendHttpRequestAsync($method, $url, $headers, $body);
    }

    /**
     * Create and send request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     * @return HttpResponse
     * @throws HttpCommunicationException
     */
    public function sendHttpRequest($method, $url, $headers = array(), $body = '')
    {
        $this->history[] = array(
            'type' => TestHttpClient::REQUEST_TYPE_SYNCHRONOUS,
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        );

        if (empty($this->responses)) {
            throw new HttpCommunicationException('No response');
        }
        
        return array_shift($this->responses);
    }

    /**
     * Create and send request asynchronously
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    public function sendHttpRequestAsync($method, $url, $headers = array(), $body = '')
    {
        $this->calledAsync = true;

        $this->history[] = array(
            'type' => TestHttpClient::REQUEST_TYPE_ASYNCHRONOUS,
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
        );
    }

    /**
     * Get additional options for request
     *
     * @return array
     */
    protected function getAdditionalOptions()
    {
        $combinations = array();
        $combinations[] = array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4));
        $combinations[] = array(new OptionsDTO(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6));

        return $combinations;
    }

    /**
     * Save additional options for request
     *
     * @param array OptionsDTO $options
     */
    protected function setAdditionalOptions($options)
    {
        $this->setAdditionalOptionsCallHistory[] = $options;
        $this->additionalOptions = $options;
    }

    /**
     * Reset additional options for request to default value
     */
    protected function resetAdditionalOptions()
    {
        $this->additionalOptions = array();
    }

    /**
     * Set all mock responses
     *
     * @param array $responses
     */
    public function setMockResponses($responses)
    {
        $this->responses = $responses;
    }

    /**
     * Return last request
     *
     * @return array
     */
    public function getLastRequest()
    {
        return reset($this->history);
    }

    /**
     * Return call count
     *
     * @return int
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * Return last request
     *
     * @return array
     */
    public function getLastRequestHeaders()
    {
        $lastRequest = $this->getLastRequest();
        return $lastRequest['headers'];
    }
}