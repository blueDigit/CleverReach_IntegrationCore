<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\SpecialTag;
use CleverReach\BusinessLogic\Sync\RecipientDeactivateSyncTask;

class RecipientDeactivateSyncTaskTest extends RecipientStatusUpdateSyncTaskTest
{
    public function testDeactivationSuccess()
    {
        parent::testDeactivationSuccess();

        foreach ($this->proxy->deactivatedRecipients as $data) {
            /** @var \CleverReach\BusinessLogic\Entity\TagCollection $tags */
            $tags = $data['tags'];
            self::assertEquals(false, $data['active'], 'Recipient should be deactivated!');
            self::assertEquals(false, $data['newsletter'], 'Recipient newsletter status should be set to false!');
            self::assertEquals(
                false,
                $tags->hasTag(SpecialTag::subscriber()),
                'Recipient Subscriber special tag should be removed!'
            );
            self::assertEquals(
                true,
                $tags->hasTag(SpecialTag::customer()),
                'Recipient Subscriber customer tag should not be removed!'
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function createSyncTaskInstance()
    {
        return new RecipientDeactivateSyncTask($this->getRecipientsEmails());
    }
}
