<?php
/**
 * Created by PhpStorm.
 * User: ivanbojovic
 * Date: 7.8.18.
 * Time: 09.17
 */

namespace CleverReach\Tests\Common\TestComponents\Configuration;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;

class TestConfiguration extends Configuration
{
    public function getIntegrationName()
    {
        return 'TestShop';
    }

    public function getClientId()
    {
        return '857eyfehs';
    }

    public function getClientSecret()
    {
        return 'u8934rtvhvc7s8dfsd56fe4cf';
    }

    public function getCrEventHandlerURL()
    {
        return 'https://example.com/eventendpoint';
    }
}