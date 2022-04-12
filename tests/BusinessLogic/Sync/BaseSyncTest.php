<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Proxy;
use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Logger\DefaultLogger;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\Task;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Configuration\TestConfigRepositoryService;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopLogger;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;
use CleverReach\Tests\Common\TestComponents\TestProxyMethods;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;
use PHPUnit\Framework\TestCase;

abstract class BaseSyncTest extends TestCase
{
    /**
     * @var TestProxyMethods
     */
    protected $proxy;
    /**
     * @var TestShopConfiguration
     */
    protected $shopConfig;
    /**
     * @var HttpClient
     */
    protected $httpClient;
    /**
     * @var TestShopLogger
     */
    protected $shopLogger;
    /**
     * @var array
     */
    protected $eventHistory;
    /**
     * @var Task
     */
    protected $syncTask;

    public function setUp()
    {
        $taskInstance = $this;
        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime(new \DateTime());
        $this->initShopConfiguration();
        $this->initHttpClient();

        $this->shopLogger = new TestShopLogger();
        $configRepository = new TestConfigRepositoryService();

        new ServiceRegister(
            array(
                Configuration::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopConfig;
                },
                HttpClient::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->httpClient;
                },
                TimeProvider::CLASS_NAME => function () use ($timeProvider) {
                    return $timeProvider;
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new DefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () use ($taskInstance) {
                    return $taskInstance->shopLogger;
                },
                ConfigRepositoryInterface::CLASS_NAME => function() use ($configRepository) {
                    return $configRepository;
                }
            )
        );

        new Logger();

        $this->initProxy();
        ServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->proxy;
            }
        );

        $this->eventHistory = array();

        $this->syncTask = $this->createSyncTaskInstance();
        $this->syncTask->when(
            ProgressedTaskEvent::CLASS_NAME,
            function (ProgressedTaskEvent $event) use (&$taskInstance) {
                $taskInstance->eventHistory[] = $event;
            }
        );
    }

    /**
     * Test resuming task after it is unserialized
     */
    public function testResumingTaskAfterDeserialize()
    {
        // Exception is set for API call
        $this->proxy->throwExceptionCode = 400;

        try {
            $this->syncTask->execute();
            $syncTaskSerialized = '';
        } catch (\Exception $ex) {
            $syncTaskSerialized = serialize($this->syncTask);
        }

        // Simulating that second execution task try will succeed
        $this->syncTask = unserialize($syncTaskSerialized);

        // Reset all values to default, and attach event again
        $this->proxy->throwExceptionCode = null;
        $this->proxy->getAllGlobalAttributesCalled = false;
        $instance = $this;
        $this->syncTask->when(
            ProgressedTaskEvent::CLASS_NAME,
            function (ProgressedTaskEvent $event) use (&$instance) {
                $instance->eventHistory[] = $event;
            }
        );

        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Task must be successfully finished with 100% report progress.'
        );
    }

    /**
     * @return Task
     */
    abstract protected function createSyncTaskInstance();

    protected function initShopConfiguration()
    {
        $this->shopConfig = new TestShopConfiguration();
        $this->shopConfig->setIntegrationName('Fake:test');
        $this->shopConfig->setAccessToken('test_access_token');
        $this->shopConfig->setIntegrationName('Fake:test');
        $this->shopConfig->setIntegrationID(123);
    }

    protected function initHttpClient()
    {
        $this->httpClient = new TestHttpClient();
        $this->httpClient->setMockResponses(
            array(
                new HttpResponse(200, array(), '{"success": true}'),
            )
        );
    }

    protected function initProxy()
    {
        $this->proxy = new TestProxyMethods();
    }
}