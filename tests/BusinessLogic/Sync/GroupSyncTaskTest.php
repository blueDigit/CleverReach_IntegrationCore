<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Sync\GroupSyncTask;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Tests\Common\TestComponents\Logger\TestShopConfiguration;

class GroupSyncTaskTest extends BaseSyncTest
{
    /**
     * Testing execute method when integration name not exists
     */
    public function testExecuteMethodWhenIntegrationNameNotProvided()
    {
        $this->initTest('');

        $this->expectException('CleverReach\Infrastructure\Exceptions\InvalidConfigurationException');
        $this->syncTask->execute();
    }

    /**
     * Testing execute method when group exists
     */
    public function testExecuteMethodWhenGroupExists()
    {
        $this->syncTask->execute();

        $this->assertFalse(array_key_exists('createGroup', $this->proxy->callHistory));
    }

    /**
     * Testing execute method when group doesn't exist and creating new group is successful
     * Assert that created groupId is set in shopConfig
     */
    public function testExecuteMethodWhenGroupNotExistsAndCreationSuccessful()
    {
        $this->initTest('not exists');

        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertTrue(array_key_exists('createGroup', $this->proxy->callHistory));
        $this->assertEquals($this->proxy->fakeCreateGroupResponse['id'], $this->shopConfig->getIntegrationId());
        $this->assertEquals(10000, $lastReportProgress->getProgressBasePoints());
    }

    /**
     * Testing execute method when group doesn't exist and creating new group is failed
     */
    public function testExecuteMethodWhenGroupNotExistsAndCreationFailed()
    {
        $this->initTest('not exist');
        $this->proxy->throwExceptionCode = 400;

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->syncTask->execute();
    }

    private function initTest($integrationName)
    {
        $this->shopConfig = new TestShopConfiguration();
        $this->shopConfig->setIntegrationName($integrationName);
        $this->shopConfig->setIntegrationListName($integrationName);
        $this->shopConfig->setAccessToken('test_access_token');
    }

    /**
     * @inheritdoc
     */
    protected function initProxy()
    {
        parent::initProxy();
        $this->proxy->fakeAllGroups = array('test list1', 'test list2', 'test list3');
        $this->proxy->fakeCreateGroupResponse = array('id' => 1546, 'name' => 'test list2');
    }

    /**
     * @inheritdoc
     */
    protected function initShopConfiguration()
    {
        parent::initShopConfiguration();
        $this->shopConfig->setIntegrationName('test list2');
        $this->shopConfig->setIntegrationListName('test list2');
    }

    /**
     * @return GroupSyncTask
     */
    protected function createSyncTaskInstance()
    {
        return new GroupSyncTask();
    }
}
