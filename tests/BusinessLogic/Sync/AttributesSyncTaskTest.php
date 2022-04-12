<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Interfaces\Attributes;
use CleverReach\BusinessLogic\Sync\AttributesSyncTask;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Tests\Common\TestComponents\TestAttributes;

class AttributesSyncTaskTest extends BaseSyncTest
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        ServiceRegister::registerService(
            Attributes::CLASS_NAME,
            function () {
                return new TestAttributes();
            }
        );
    }

    /**
     * Test report progress
     */
    public function testExecuteMethodReportProgress()
    {
        $this->syncTask->execute();

        /** @var ProgressedTaskEvent $firstReportProgress */
        $firstReportProgress = reset($this->eventHistory);
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertNotEmpty($this->eventHistory, 'History of fired report progress events must not be empty');
        $this->assertEquals(5, $firstReportProgress->getProgressFormatted(), 'First report progress must be set with 5%.');
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted(), 'Last report progress must be set with 100%.');
    }

    /**
     * Test if get all global attributes API call is called
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetAllGlobalAttributesIsCalled()
    {
        $attributesTask = new AttributesSyncTask();
        $attributesTask->execute();

        $this->assertTrue(
            $this->proxy->getAllGlobalAttributesCalled,
            'GetAllGlobalAttributes must be called in execute process.'
        );
    }

    /**
     * Test create attributes which are not in global attributes list
     */
    public function testListOfCreatedAttributes()
    {
        // This list represents attributes which are in attributes list from integration and are not in global attributes list from CR
        $listOfExpectedCreateAttributes = array(
            'zip',
            'state',
            'birthday',
            'phone',
            'customernumber',
            'language',
            'newsletter',
        );
        $this->syncTask->execute();

        $this->assertEquals(
            $listOfExpectedCreateAttributes,
            $this->proxy->createGlobalAttributes,
            'List of created attributes must be set.'
        );
    }

    /**
     * Test update attributes which are in global attributes list
     */
    public function testListOfUpdatedAttributes()
    {
        // This list represents attributes which are in attributes list from integration and also in global attributes list from CR
        $listOfExpectedUpdateAttributes = array(
            'salutation',
            'title',
            'firstname',
            'lastname',
            'street',
            'city',
            'company',
            'country',
            'shop',
        );
        $this->syncTask->execute();

        $this->assertEquals(
            $listOfExpectedUpdateAttributes,
            $this->proxy->updateGlobalAttributes,
            'List of updated attributes must be set.'
        );
    }

    /**
     * @inheritdoc
     */
    public function testResumingTaskAfterDeserialize()
    {
        parent::testResumingTaskAfterDeserialize();
        $this->assertFalse(
            $this->proxy->getAllGlobalAttributesCalled,
            'GetAllGlobalAttributes should not be called in second execute process,'
            . ' because in first try we have stored response.'
        );
    }

    /**
     * @return AttributesSyncTask
     */
    protected function createSyncTaskInstance()
    {
        return new AttributesSyncTask();
    }

    /**
     * @inheritdoc
     */
    protected function initProxy()
    {
        parent::initProxy();

        $fakeDir = realpath(dirname(__FILE__) . '/../..') . '/Common/fakeAPIResponses/formatted';
        $response = file_get_contents("$fakeDir/getAllGlobalAttributes.json");
        $this->proxy->setResponse($response);
    }
}
