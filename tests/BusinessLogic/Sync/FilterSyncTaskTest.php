<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Sync\FilterSyncTask;
use CleverReach\BusinessLogic\Utility\Rule;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Tests\Common\TestComponents\TestRecipients;

class FilterSyncTaskTest extends BaseSyncTest
{
    /** @var TestRecipients */
    private $recipients;
    /** @var FilterSyncTask */
    protected $syncTask;
    /** @var string */
    private $integrationName = 'fake';

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $taskInstance = $this;
        $this->shopConfig->setIntegrationName($this->integrationName);
        $this->shopConfig->setIntegrationListName('Dummy List name');
        $this->initFakeFilters();
        $this->recipients = new TestRecipients();
        ServiceRegister::registerService(
            Recipients::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->recipients;
            }
        );
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testReportProgress()
    {
        $this->syncTask->execute();

        /** @var ProgressedTaskEvent $firstReportProgress */
        $firstReportProgress = reset($this->eventHistory);
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertNotEmpty($this->eventHistory, 'History of fired report progress events must not be empty');
        $this->assertEquals(
            10,
            $firstReportProgress->getProgressFormatted(),
            'First report progress must be set with 5%.'
        );
        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Last report progress must be set with 100%.'
        );
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testIfIntegrationIDNotConfigured()
    {
        $this->shopConfig->setIntegrationID(null);
        $this->expectException('CleverReach\Infrastructure\Exceptions\InvalidConfigurationException');
        $this->syncTask->execute();
    }

    /**
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testIfIntegrationIDNotConfiguredAndCustomerServiceMethodsNotCalled()
    {
        $this->shopConfig->setIntegrationID(null);
        try {
            $this->syncTask->execute();
        } catch (InvalidConfigurationException $exception) {
            $this->assertFalse($this->recipients->getAllTagsIsCalled);
            $this->assertEmpty($this->proxy->callHistory);
        }
    }

    /**
     * Initial sync - no segments on CR
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testInitialSynchronization()
    {
        $this->proxy->fakeAllFilters = array();
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertTrue(array_key_exists('createFilter', $this->proxy->callHistory));
        $this->assertCount(count($this->recipients->getAllTags()), $this->proxy->callHistory['createFilter']);
        $this->assertFalse(array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testWhenShopGroupIsUpdated()
    {
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertTrue(array_key_exists('createFilter', $this->proxy->callHistory));
        $this->assertTrue(array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testWhenAllSegmentsAreUpToDate()
    {
        // tags in integration and in CR are now the same (except user tags)
        $this->recipients->allTags = new TagCollection(
            array(
                new Tag('Group0', 'Group'),
                new Tag('Group1', 'Group'),
                new Tag('userGroup', 'Group'),
            )
        );

        $this->recipients->allSpecialTags = new SpecialTagCollection(
            array(
                SpecialTag::customer(),
                SpecialTag::buyer(),
            )
        );

        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        $this->assertFalse(array_key_exists('createFilter', $this->proxy->callHistory));
        $this->assertFalse(array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testIfDeleteMethodIsCalledForAppropriateSegment()
    {
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);

        /**
         * List of all IDs which are used as parameter for deleteFilter method
         * @var array $allDeletedFilters
         */
        $allDeletedFilters = array_column($this->proxy->callHistory['deleteFilter'], 'filterID');

        // Group.Group0 should be deleted
        $deletedTagGroupId = 110;

        // Special.Buyer should be deleted
        $deletedSpecialTagGroupId = 202;

        // Group.Group1 shouldn't be deleted
        $notDeletedId = 111;

        //assert that deleteFilter is called for appropriate segment
        $this->assertContains($deletedTagGroupId, $allDeletedFilters);
        $this->assertContains($deletedSpecialTagGroupId, $allDeletedFilters);
        $this->assertNotContains($notDeletedId, $allDeletedFilters);
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * Test serialize and unserialize method
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testSerialize()
    {
        /** @var FilterSyncTask $filterSyncTask */
        $filterSyncTask = unserialize(serialize($this->syncTask));
        $filterSyncTask->execute();
        $this->assertEquals(FilterSyncTask::getClassName(), $filterSyncTask->getType());
    }

    private function initFakeFilters()
    {
        $this->proxy->fakeCreateFilterResponse = array('id' => 16);

        $rule = new Rule('tags', 'contains', $this->shopConfig->getIntegrationName() . '-Group.userGroup');
        $filter = new Filter('Group: userGroup', $rule);
        $filter->setId(119);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', $this->shopConfig->getIntegrationName() . '-Group.Group0');
        $filter = new Filter('Group: Group0', $rule);
        $filter->setId(110);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', $this->shopConfig->getIntegrationName() . '-Group.Group1');
        $filter = new Filter('Group: Group1', $rule);
        $filter->setId(111);
        $this->proxy->fakeAllFilters[] = $filter;

        // Special tag filters
        $rule = new Rule('tags', 'contains', $this->shopConfig->getIntegrationName() . '-Special.Customer');
        $filter = new Filter('Special: Customer', $rule);
        $filter->setId(201);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', $this->shopConfig->getIntegrationName() . '-Special.Buyer');
        $filter = new Filter('Special: Subscriber', $rule);
        $filter->setId(202);
        $this->proxy->fakeAllFilters[] = $filter;
    }

    /**
     * @return FilterSyncTask
     */
    protected function createSyncTaskInstance()
    {
        return new FilterSyncTask();
    }
}