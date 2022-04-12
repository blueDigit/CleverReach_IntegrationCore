<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\Infrastructure\Utility\HttpResponse;
use CleverReach\Tests\Common\TestComponents\TestHttpClient;

abstract class RecipientStatusUpdateSyncTaskTest extends BaseSyncTest
{
    protected $numberOfRecipientIdForTest = 20;

    public function testDeactivationSuccess()
    {
        try {
            $this->syncTask->execute();
        } catch (\Exception $ex) {
            $this->fail('Deactivation of recipients should pass successfully.' . $ex->getMessage());
        }
    }

    public function testDeactivationFailure()
    {
        $this->proxy->throwExceptionCode = 400;
        $this->expectException('CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException');
        $this->syncTask->execute();
    }

    protected function getRecipientsEmails()
    {
        $emails = array();

        for ($i = 0; $i < $this->numberOfRecipientIdForTest; $i++) {
            $emails[] = "test$i@test.com";
        }

        return $emails;
    }

    protected function initHttpClient()
    {
        $this->httpClient = new TestHttpClient();

        $responses = array();
        for ($i = 0; $i < $this->numberOfRecipientIdForTest; $i++) {
            // first call is getting recipient
            $responses[] = new HttpResponse(
                200,
                array(),
                json_encode(
                    array(
                        'email' => "test$i@test.com",
                        'active' => true,
                        'tags' => array(
                            $this->shopConfig->getIntegrationName() . '-Group.Customer',
                            $this->shopConfig->getIntegrationName() . '-Special.Customer',
                            $this->shopConfig->getIntegrationName() . '-Special.Subscriber',
                            'Dummy-Shop.Demo_shop',
                            'Dummy-Special.Subscriber',
                        ),
                    )
                )
            );
        }

        // last call is updating recipient
        $responses[] = new HttpResponse(200, array(), '{"success": true}');

        $this->httpClient->setMockResponses($responses);
    }
}
