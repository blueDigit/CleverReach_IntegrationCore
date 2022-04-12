<?php

namespace CleverReach\Tests\Infrastructure\logger;

use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use CleverReach\Infrastructure\Logger\Configuration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use PHPUnit\Framework\TestCase;
use CleverReach\Infrastructure\Interfaces\required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Infrastructure\Logger\Logger;

class LoggerTest extends TestCase
{

    /**
     * @var DefaultLogger
     */
    private $defaultLogger;

    /**
     * @var TestShopLogger
     */
    private $shopLogger;

    /**
     * @var TestShopConfiguration
     */
    private $shopConfiguration;

    /**
     * @var TestHttpClient
     */
    private $httpClient;

    protected function setUp()
    {
        Configuration::resetInstance();
        $this->defaultLogger = new DefaultLogger();
        $this->shopLogger = new TestShopLogger();
        $this->httpClient = new TestHttpClient();
        $this->shopConfiguration = new TestShopConfiguration();
        $this->shopConfiguration->setIntegrationName('Shop1');
        $this->shopConfiguration->setUserAccountId('04596');
        $this->shopConfiguration->setDefaultLoggerEnabled(true);

        $componentInstance = $this;

        new ServiceRegister(array(
            DefaultLoggerAdapter::CLASS_NAME => function() use ($componentInstance) {
                return $componentInstance->defaultLogger;
            },
            ShopLoggerAdapter::CLASS_NAME => function() use ($componentInstance) {
                return $componentInstance->shopLogger;
            },
            ConfigInterface::CLASS_NAME => function() use ($componentInstance) {
                return $componentInstance->shopConfiguration;
            },
            HttpClient::CLASS_NAME => function() use ($componentInstance) {
                return $componentInstance->httpClient;
            }
        ));

        new Logger();

    }

    /**
     * Test if error log level is passed to shop logger
     */
    public function testErrorLogLevelIsPassed()
    {
        Logger::logError('Some data');
        $this->assertEquals(0, $this->shopLogger->data->getLogLevel(), 'Log level for error call must be 0.');
    }

    /**
     * Test if warning log level is passed to shop logger
     */
    public function testWarningLogLevelIsPassed()
    {
        Logger::logWarning('Some data');
        $this->assertEquals(1, $this->shopLogger->data->getLogLevel(), 'Log level for warning call must be 1.');
    }

    /**
     * Test if info log level is passed to shop logger
     */
    public function testInfoLogLevelIsPassed()
    {
        Logger::logInfo('Some data');
        $this->assertEquals(2, $this->shopLogger->data->getLogLevel(), 'Log level for info call must be 2.');
    }

    /**
     * Test if debug log level is passed to shop logger
     */
    public function testDebugLogLevelIsPassed()
    { 
        Logger::logDebug('Some data');
        $this->assertEquals(3, $this->shopLogger->data->getLogLevel(), 'Log level for debug call must be 3.');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogMessageIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Some data', $this->shopLogger->data->getMessage(), 'Log message must be sent.');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogComponentIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Core', $this->shopLogger->data->getComponent(), 'Log component must be sent');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogIntegrationIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('Shop1', $this->shopLogger->data->getIntegration(), 'Log integration must be sent');
    }

    /**
     * Test if log data is sent to shop logger
     */
    public function testLogUserAccountIsSent()
    {
        Logger::logInfo('Some data');
        $this->assertEquals('04596', $this->shopLogger->data->getUserAccount(), 'Log user account must be sent');
    }

    /**
     * Test if message will not be logged to default logger when it is off
     */
    public function testNotLoggingToDefaultLoggerWhenItIsOff()
    {
        Configuration::setDefaultLoggerEnabled(false);
        Logger::logInfo('Some data');
        $this->assertFalse($this->httpClient->calledAsync, 'Default logger should not send log when it is off.');
    }

    /**
     * Test if message will be logged to default logger when it is on
     */
    public function testLoggingToDefaultLoggerWhenItIsOn()
    {
        Configuration::setDefaultLoggerEnabled(true);
        Logger::logInfo('Some data');
        $this->assertTrue($this->httpClient->calledAsync, 'Default logger should send log when it is on.');
    }

    /**
     * Test if message will be logged to default logger when log level is lower than set min log level
     */
    public function testLoggingToDefaultLoggerWhenLogLevelIsLowerThanMinLogLevel()
    {
        Configuration::getInstance()->setMinLogLevel(Logger::INFO);
        Logger::logWarning('Some data');
        $this->assertTrue($this->httpClient->calledAsync, 'Default logger should send log when log level is lower than set min log level.');
    }

    /**
     * Test if message will not be logged to default logger when log level is higher than set min log level
     */
    public function testNotLoggingToDefaultLoggerWhenLogLevelIsHigherThanMinLogLevel()
    {
        Configuration::getInstance()->setMinLogLevel(Logger::ERROR);
        Logger::logWarning('Some data');
        $this->assertFalse($this->httpClient->calledAsync, 'Default logger should not send log when log level is higher than set min log level.');
    }
}