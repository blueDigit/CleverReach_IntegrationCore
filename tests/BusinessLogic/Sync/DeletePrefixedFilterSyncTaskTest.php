<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Sync\DeletePrefixedFilterSyncTask;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\BusinessLogic\Utility\Rule;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;

class DeletePrefixedFilterSyncTaskTest extends BaseSyncTest
{
    private $prefixedTagsForDelete = array('PREF-G-tag1', 'PREF-G-tag2');
    /**
     * @var array $fakeAllFilters
     */
    private $fakeAllFilters;
    /**
     * @var DeletePrefixedFilterSyncTask $syncTask
     */
    protected $syncTask;

    /**
     * @return DeletePrefixedFilterSyncTask
     */
    protected function createSyncTaskInstance()
    {
        return new DeletePrefixedFilterSyncTask($this->prefixedTagsForDelete);
    }

    /**
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
            'First report progress must be set with 10%.'
        );
        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Last report progress must be set with 100%.'
        );
    }

    /**
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testIfThereAreTagsToDelete()
    {
        $this->initFakeFilters();
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);
        $this->assertTrue(array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testNoTagsToDelete()
    {
        $rule = new Rule('tags', 'contains', 'PREF-G-SomeTagName');
        $filter = new Filter('G-Tag1', $rule);
        $filter->setId(119);
        $this->proxy->fakeAllFilters[] = $filter;

        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);
        $this->assertFalse(array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    /**
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function testIfNoTagsForDelete()
    {
        $this->prefixedTagsForDelete = null;
        $this->setUp();
        $this->syncTask->execute();
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);
        $this->assertFalse(
            array_key_exists('deleteFilter', $this->proxy->callHistory));
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted());
    }

    private function initFakeFilters()
    {
        $rule = new Rule('tags', 'contains', 'PREF-G-tag1');
        $filter = new Filter('G-Tag1', $rule);
        $filter->setId(119);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', 'PREF-G-tag2');
        $filter = new Filter('G-Tag2', $rule);
        $filter->setId(110);
        $this->proxy->fakeAllFilters[] = $filter;

        $rule = new Rule('tags', 'contains', 'PREF-G-tag3');
        $filter = new Filter('G-Tag3', $rule);
        $filter->setId(111);
        $this->proxy->fakeAllFilters[] = $filter;
    }
}
