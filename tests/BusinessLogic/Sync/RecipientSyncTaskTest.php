<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\DTO\RecipientDTO;
use CleverReach\BusinessLogic\Entity\Tag;
use CleverReach\BusinessLogic\Entity\TagCollection;
use CleverReach\BusinessLogic\Interfaces\Recipients;
use CleverReach\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\TestRecipients;

class RecipientSyncTaskTest extends BaseSyncTest
{
    const HTTP_ERROR_BATCH_TOO_BIG = 413;
    /** @var RecipientSyncTask */
    protected $syncTask;
    /** @var  TestRecipients */
    private $recipients;

    /**
     * State of task before and after serialization should be the same.
     */
    public function testSerializeUnserializeOfRecipientSyncTask()
    {
        /** @var RecipientSyncTask $unserializedRecipientSyncTask */
        $unserializedRecipientSyncTask = unserialize(serialize($this->syncTask));

        self::assertEquals(
            $this->syncTask->getRecipientsIdsForSync(),
            $unserializedRecipientSyncTask->getRecipientsIdsForSync(),
            'Recipient ids passed to constructor must match unserialized recipient ids.'
        );
        self::assertCount(
            0,
            $this->syncTask->getTagsToDelete()
                ->diff($unserializedRecipientSyncTask->getTagsToDelete()),
            'Additional tags to delete to constructor must match unserialized additional tags to delete.'
        );
        self::assertEquals(
            $this->syncTask->getIncludeOrders(),
            $unserializedRecipientSyncTask->getIncludeOrders(),
            'Include orders flag passed to constructor must match unserialized include orders flag.'
        );
        self::assertEquals(
            $this->syncTask->getCurrentSyncProgress(),
            $unserializedRecipientSyncTask->getCurrentSyncProgress(),
            'Current sync progress passed to constructor must match unserialized sync progress.'
        );
        self::assertEquals(
            $this->syncTask->getNumberOfRecipientsForSync(),
            $unserializedRecipientSyncTask->getNumberOfRecipientsForSync(),
            'Number of recipients passed to constructor must match unserialized number of recipients.'
        );
        self::assertEquals(
            $this->syncTask->getBatchSize(),
            $unserializedRecipientSyncTask->getBatchSize(),
            'Batch size passed to constructor must match unserialized batch size.'
        );
    }

    /**
     * When there are no recipients recipientsMassUpdate should not be called, sync progress must be 100% and
     * there should be 0 recipients in task state.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testExecuteMethodRequestSuccessWhenNoRecipients()
    {
        $this->syncTask = new RecipientSyncTask(array());

        $this->syncTask->execute();

        self::assertCount(
            0,
            $this->syncTask->getRecipientsIdsForSync(),
            'Number of recipients in task state must be 0.'
        );
        self::assertNotTrue(
            isset($this->proxy->callHistory['recipientsMassUpdate']),
            'Request for sending recipients should not be triggered'
        );
        self::assertEquals(
            100,
            $this->syncTask->getCurrentSyncProgress(),
            'Progress must be set to 100 when there are no recipients.'
        );
    }

    /**
     * When request is successful it should have no more recipients for adding. Also all recipient IDs should match
     * batch by batch with ones that are sent. Number of all recipients passed for sending must match the number of sent
     * ones.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testExecuteMethodSuccessfulRequest()
    {
        $recipients = $this->getRecipientsIds();
        $initialBatchSize = 250;
        $lastPositionInFirstBatch = $initialBatchSize - 1;

        $this->syncTask->execute();

        self::assertCount(
            0,
            $this->syncTask->getRecipientsIdsForSync(),
            'Number of recipients to send must be 0.'
        );
        self::assertEquals(
            $recipients[0],
            $this->getSentRecipientInBatch(1, 0),
            'First recipients must be the same as sent one.'
        );
        self::assertEquals(
            count($recipients),
            $this->getCountOfSentRecipients(),
            'Count of sent recipients must match.'
        );
        self::assertEquals(
            $recipients[$lastPositionInFirstBatch],
            $this->getSentRecipientInBatch(1, $lastPositionInFirstBatch),
            'Last recipient in first batch must be the same as sent.'
        );
        self::assertEquals(
            $recipients[$initialBatchSize],
            $this->getSentRecipientInBatch(2, 0),
            'First recipient in second batch must match with second requests first recipient.'
        );
    }

    private function getCountOfSentRecipients()
    {
        $countOfAllSentRecipients = 0;

        foreach ($this->proxy->callHistory['recipientsMassUpdate'] as $recipientsPerBatch) {
            $countOfAllSentRecipients += count($recipientsPerBatch['recipients']);
        }

        return $countOfAllSentRecipients;
    }

    private function getSentRecipientInBatch($batchNumber, $position)
    {
        /** @var RecipientDTO $recipientDTO */
        $recipientDTO = $this->proxy->callHistory['recipientsMassUpdate'][$batchNumber - 1]['recipients'][$position];

        return $recipientDTO->getRecipientEntity()->getEmail();
    }

    /**
     * When any other error HTTP code, except 413 is thrown, it should not be handled.
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testExecuteMethodWhenRequestExceptionOccurred()
    {
        $this->proxy->throwExceptionCode = 400;

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');

        $this->syncTask->execute();
    }

    /**
     * When exception happens on some batch, task should save its state and continue from where it stopped (batch that
     * crashed should be the first one for sending)
     *
     * @param array $expectedResult
     * @param int $corruptedRecipientsBatch
     * @param int $batchSize
     *
     * @dataProvider testExecuteMethodWhenRequestExceptionOccurredOnCorruptedBatchDataProvider
     */
    public function testExecuteMethodWhenRequestExceptionOccurredOnCorruptedBatch(
        $expectedResult,
        $corruptedRecipientsBatch,
        $batchSize
    ) {
        $this->proxy->throwExceptionCode = 400;
        $this->proxy->corruptedRecipientsBatch = $corruptedRecipientsBatch;
        $recipients = $this->getRecipientsIds();
        $this->shopConfig->setRecipientsSynchronizationBatchSize($batchSize);

        $recipientSyncTask = new RecipientSyncTask($recipients);

        try {
            $recipientSyncTask->execute();
        } catch (\Exception $e) {
        }

        self::assertEquals(
            $expectedResult,
            $recipientSyncTask->getRecipientsIdsForSync(),
            'Recipients for sending must start from last failed batch'
        );
    }

    /**
     * Provider for parametrized test testExecuteMethodWhenRequestExceptionOccurredOnSomeBatch
     *
     * @return array
     */
    public function testExecuteMethodWhenRequestExceptionOccurredOnCorruptedBatchDataProvider()
    {
        $allRecipients = $this->getRecipientsIds();
        $recipientsBatchForFirstTest = array_slice($allRecipients, 0);
        $recipientsBatchForSecondTest = array_slice($allRecipients, 100);

        return array(
            // Parameters for testExecuteMethodWhenRequestExceptionOccurredOnCorruptedBatch.
            // When one batch is finished it will be removed from all recipient ids. For example if process
            // breaks on second batch and batch size is 100, rest of elements for sending should be from 100 until the
            // end because the first 100 are sent successfully.
            array($recipientsBatchForFirstTest, 1, 50),
            array($recipientsBatchForSecondTest, 2, 100),
        );
    }

    /**
     * @param int $batchSize
     * @param int $expectedResult
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @dataProvider testExecuteMethodWhenBatchTooBigExceptionOccurredDataProvider
     */
    public function testExecuteMethodWhenBatchTooBigExceptionOccurred2Times($batchSize, $expectedResult)
    {
        $this->shopConfig->setRecipientsSynchronizationBatchSize($batchSize);
        $this->proxy->throwExceptionCode = self::HTTP_ERROR_BATCH_TOO_BIG;
        // After batch size is set to config, sync task must be created again.
        $recipientSyncTask = new RecipientSyncTask($this->getRecipientsIds());

        $recipientSyncTask->execute();

        self::assertEquals(
            $expectedResult,
            $recipientSyncTask->getBatchSize(),
            'Batch size must be set properly to task state.'
        );
        self::assertEquals(
            $expectedResult,
            $this->shopConfig->getRecipientsSynchronizationBatchSize(),
            'Batch size must be set properly to configuration.'
        );
    }

    /**
     * Provider for parametrized test testExecuteMethodWhenBatchTooBigExceptionOccurred2Times. Values in each array will
     * be passed to test method.
     *
     * @return array
     */
    public function testExecuteMethodWhenBatchTooBigExceptionOccurredDataProvider()
    {
        // Ranges which will be used for batch size and expected result is calculated based on:
        // RecipientSyncTask->calculateNewBatchSize method
        return array(
            // Parameters for testExecuteMethodWhenBatchTooBigExceptionOccurred2Times
            array(250, 150),
            array(60, 40),
            array(10, 8),
            array(120, 60),
            array(500, 400),
        );
    }

    /**
     * When batch size is 1 and CleverReach reports that batch size is too big, unhandled exceptions should be thrown.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testExecuteMethodWhenBatchTooBigExceptionOccurredForInitialBatchValue1()
    {
        $this->shopConfig->setRecipientsSynchronizationBatchSize(1);
        $this->proxy->throwExceptionCode = self::HTTP_ERROR_BATCH_TOO_BIG;
        $recipientSyncTask = new RecipientSyncTask($this->getRecipientsIds());

        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException');

        $recipientSyncTask->execute();
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testSendingOfActivatedDeactivatedWhenTimeStampIsSetAsValueForThatFields()
    {
        /** @var TestRecipients */
        $this->recipients->shouldGenerateTimeStampForDeactivated = true;

        $recipientSyncTask = new RecipientSyncTask($this->getRecipientsIds());

        $recipientSyncTask->execute();

        /** @var RecipientDTO $recipientDTO */
        $recipientDTO = $this->proxy->callHistory['recipientsMassUpdate'][0]['recipients'][0];
        // We should never send deactivated field
        self::assertFalse($recipientDTO->shouldDeactivatedFieldBeSent());
        self::assertTrue($recipientDTO->shouldActivatedFieldBeSent());
    }

    /**
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testSendingOfActivatedDeactivatedWhenTimeStampIsNOTSetAsValueForThatFields()
    {
        $this->syncTask->execute();

        /** @var RecipientDTO $recipientDTO */
        $recipientDTO = $this->proxy->callHistory['recipientsMassUpdate'][0]['recipients'][0];
        self::assertFalse($recipientDTO->shouldDeactivatedFieldBeSent());
        self::assertTrue($recipientDTO->shouldActivatedFieldBeSent());
    }

    private function getRecipientsIds()
    {
        $ids = array();
        $numberOfRecipientIdForTest = 300;

        for ($i = 0; $i < $numberOfRecipientIdForTest; $i++) {
            $ids[] = $i . 'test@test.com';
        }

        return $ids;
    }

    /**
     * @return RecipientSyncTask
     */
    protected function createSyncTaskInstance()
    {
        // RecipientSyncTask requires Recipients service to be registered
        $this->recipients = new TestRecipients();

        $taskInstance = $this;
        ServiceRegister::registerService(
            Recipients::CLASS_NAME,
            function () use ($taskInstance) {
                return $taskInstance->recipients;
            }
        );

        return new RecipientSyncTask($this->getRecipientsIds(), new TagCollection(array(new Tag('Group1', 'Group'))));
    }
}
