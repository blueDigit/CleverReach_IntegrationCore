<?php

namespace CleverReach\Tests\BusinessLogic\Proxy;

use CleverReach\Infrastructure\Logger\Logger;
use PHPUnit\Framework\TestCase;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;

class ProxyTestBase extends TestCase
{

    /**
     * @var string
     */
    protected $fakeResponsesDir;
    
    /**
     * @var string
     */
    protected $formattedFakeResponsesDir;

    /**
     * @var TestShopConfiguration
     */
    protected $shopConfig;

    /**
     * @var TestShopLogger
     */
    protected $shopLogger;

    /**
     * @var TestHttpClient
     */
    protected $httpClient;

    public function setUp()
    {
        $this->fakeResponsesDir = realpath(dirname(__FILE__) . '/../..') . '/Common/fakeAPIResponses';
        $this->formattedFakeResponsesDir = $this->fakeResponsesDir . '/formatted';
        $this->httpClient = new TestHttpClient();
        $this->shopLogger = new TestShopLogger();
        $this->shopConfig = new TestShopConfiguration();
        $this->shopConfig->setAccessToken('test_access_token');
        $this->shopConfig->setAccessTokenExpirationTime(1000);
        $this->shopConfig->setRefreshToken('test_refresh_token');
        $proxyInstance = $this;

        new ServiceRegister(array(
            Configuration::CLASS_NAME => function() use ($proxyInstance) {
                return $proxyInstance->shopConfig;
            },
            DefaultLoggerAdapter::CLASS_NAME => function() {
                return new DefaultLogger();
            },
            ShopLoggerAdapter::CLASS_NAME => function() use ($proxyInstance) {
                return $proxyInstance->shopLogger;
            },
            HttpClient::CLASS_NAME => function() use ($proxyInstance) {
                return $proxyInstance->httpClient;
            },
        ));

        new Logger();
    }

    /**
     * Get fake response body
     *
     * @param $fakeFile
     * @return string
     */
    protected function getFakeResponseBody($fakeFile)
    {
        return file_get_contents("{$this->fakeResponsesDir}/$fakeFile");
    }

    /**
     * Get formatted fake response body
     *
     * @param $fakeFile
     * @return string
     */
    protected function getFormattedFakeResponseBody($fakeFile)
    {
        return file_get_contents("{$this->formattedFakeResponsesDir}/$fakeFile");
    }
}