<?php

namespace CleverReach\Tests\Common\TestComponents\Logger;

use CleverReach\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\Infrastructure\Logger\LogData;

class TestShopLogger implements ShopLoggerAdapter
{

    /**
     * @var LogData
     */
    public $data;

    /** @var LogData[] */
    public $loggedMessages = array();

    public function logMessage($data)
    {
        $this->data = $data;
        $this->loggedMessages[] = $data;
    }

    public function isMessageContainedInLog($message)
    {
        foreach ($this->loggedMessages as $loggedMessage) {
            if (mb_strpos($loggedMessage->getMessage(), $message) !== false) {
                return true;
            }
        }

        return false;
    }
}