<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Infrastructure\Utility\TimeProvider;
use CleverReach\Tests\Common\TestComponents\Configuration\TestConfigRepositoryService;
use CleverReach\Tests\Common\TestComponents\Configuration\TestConfiguration;
use CleverReach\Tests\Common\TestComponents\TaskExecution\TestInitialSyncTask;
use CleverReach\Tests\Common\TestComponents\TestRecipients;
use CleverReach\Tests\Common\TestComponents\Utility\TestTimeProvider;

class InitialSyncTaskTest extends BaseSyncTest
{
    /** @var TestInitialSyncTask */
    protected $syncTask;
    private $taskProgresses;
    private $aliveTaskEvents;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->aliveTaskEvents = array();
        $self = $this;
        $this->syncTask->when(
            AliveAnnouncedTaskEvent::CLASS_NAME,
            function (AliveAnnouncedTaskEvent $event) use ($self) {
                $self->aliveTaskEvents[] = $event;
            }
        );

        $this->syncTask->when(
            ProgressedTaskEvent::CLASS_NAME,
            function (ProgressedTaskEvent $event) use ($self) {
                $self->taskProgresses[] = $event->getProgressFormatted();
            }
        );

        $configService = new TestConfiguration();
        $configRepository = new TestConfigRepositoryService();
        $timeProvider = new TestTimeProvider();
        new ServiceRegister(
            array(
                ConfigRepositoryInterface::CLASS_NAME =>
                    function() use($configRepository) {
                        return $configRepository;
                    },
                Configuration::CLASS_NAME =>
                    function() use ($configService) {
                        return $configService;
                    },
                TimeProvider::CLASS_NAME =>
                    function() use ($timeProvider) {
                        return $timeProvider;
                    }
            )
        );
    }

    public function testResumingTaskAfterDeserialize()
    {
        $this->syncTask->execute();

        /** @var TestInitialSyncTask $unserializedTask */
        $unserializedTask = unserialize(serialize($this->syncTask));

        $this->assertEquals($unserializedTask->getProgressByTask(), $this->syncTask->getProgressByTask());
    }

    public function testNumberOfExecuteCallsForSuccessfulProcess()
    {
        $numberOfSynchronizationTasks = 6;
        $this->syncTask->execute();

        $numberOfCalls = $this->syncTask->getNumberOfExecuteCalls();
        $this->assertEquals($numberOfSynchronizationTasks, $numberOfCalls);
    }

    public function testIfFactoryMethodsMakeProperObjects()
    {
        $initialSyncTask = new TestInitialSyncTask();

        $attributesSyncTask = $initialSyncTask->getAttributesSyncTask();
        $filterSyncTask = $initialSyncTask->getFilterSyncTask();
        $groupSyncTask = $initialSyncTask->getGroupSyncTask();
        $productSearchSyncTask = $initialSyncTask->getProductSearchSyncTask();
        $recipientSyncTask = $initialSyncTask->getRecipientSyncTask();

        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\AttributesSyncTask', $attributesSyncTask);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\FilterSyncTask', $filterSyncTask);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\GroupSyncTask', $groupSyncTask);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\ProductSearchSyncTask', $productSearchSyncTask);
        $this->assertInstanceOf('CleverReach\BusinessLogic\Sync\RecipientSyncTask', $recipientSyncTask);
    }

    public function testAliveEventEmitting()
    {
        $this->syncTask->execute();

        $this->assertNotEmpty($this->aliveTaskEvents);
    }

    public function testProgressWhenTaskSuccessful()
    {
        $this->syncTask->execute();

        $this->assertNotEmpty($this->taskProgresses);
        $this->assertEquals(100, end($this->taskProgresses));
    }

    /**
     * @return TestInitialSyncTask
     */
    protected function createSyncTaskInstance()
    {
        ServiceRegister::registerService(
            Recipients::CLASS_NAME,
            function () {
                return new TestRecipients();
            }
        );

        return new TestInitialSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function initShopConfiguration()
    {
        parent::initShopConfiguration();
        $this->shopConfig->setProductSearchParameters(
            array(
                'name' => 'My shop - Product search',
                'url' => 'http://myshop.com/endpoint',
                'password' => 's3Sdsdf34dfsWSW',
            )
        );
    }
}