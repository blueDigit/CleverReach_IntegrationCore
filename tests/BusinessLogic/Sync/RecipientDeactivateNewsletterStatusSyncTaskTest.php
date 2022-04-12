<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Sync\RecipientDeactivateNewsletterStatusSyncTask;

class RecipientDeactivateNewsletterStatusSyncTaskTest extends RecipientStatusUpdateSyncTaskTest
{
    /**
     * @inheritdoc
     */
    protected function createSyncTaskInstance()
    {
        return new RecipientDeactivateNewsletterStatusSyncTask($this->getRecipientsEmails());
    }
}
