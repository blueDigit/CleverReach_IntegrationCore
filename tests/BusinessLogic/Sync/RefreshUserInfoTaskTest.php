<?php

namespace CleverReach\Tests\BusinessLogic\Sync;

use CleverReach\BusinessLogic\Entity\AuthInfo;
use CleverReach\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;

class RefreshUserInfoTaskTest extends BaseSyncTest
{
    /**
     * @var AuthInfo
     */
    protected $authInfo;
    /**
     * @var RefreshUserInfoTask
     */
    protected $syncTask;

    /**
     * Run test when GetUserInfo returns valid info.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetUserInfoReturnsValidInfo()
    {
        $oldInfo = $this->getOldAuthInfo();
        $this->shopConfig->setAuthInfo($oldInfo);
        $this->setFakeUserInfoResponse();

        $this->syncTask->execute();

        $newInfo = $this->shopConfig->getAuthInfo();

        $this->assertChangedAuthInfo($oldInfo, $newInfo);
        $this->assertNotChangedAuthInfo($this->authInfo, $newInfo);

        $this->assertNotEmpty($this->eventHistory, 'History of fired report progress events must not be empty.');
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);
        $this->assertEquals(
            100,
            $lastReportProgress->getProgressFormatted(),
            'Last report progress must be set with 100%.'
        );
    }

    /**
     * Run test when GetUserInfo returns empty array.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetUserInfoReturnsEmptyArray()
    {
        $oldInfo = $this->getOldAuthInfo();
        $this->shopConfig->setAuthInfo($oldInfo);

        $this->proxy->setResponse('{}');
        $this->syncTask->execute();

        $newInfo = $this->shopConfig->getAuthInfo();

        $this->assertNotChangedAuthInfo($oldInfo, $newInfo);

        $this->assertNotEmpty($this->eventHistory, 'History of fired report progress events must not be empty.');
        /** @var ProgressedTaskEvent $lastReportProgress */
        $lastReportProgress = end($this->eventHistory);
        $this->assertEquals(100, $lastReportProgress->getProgressFormatted(), 'Last report progress must be set with 100%.');
    }

    /**
     * Run test when GetUserInfo throws exception.
     *
     * @throws \CleverReach\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function testGetUserInfoThrowsException()
    {
        $oldInfo = $this->getOldAuthInfo();
        $this->shopConfig->setAuthInfo($oldInfo);

        $this->proxy->throwExceptionCode = 400;
        try {
            $this->syncTask->execute();
            $this->fail('Method should throw exception.');
        } catch (HttpRequestException $ex) {
            $newInfo = $this->shopConfig->getAuthInfo();
            $this->assertNotChangedAuthInfo($oldInfo, $newInfo);
        }
    }

    /**
     * @return RefreshUserInfoTask
     */
    protected function createSyncTaskInstance()
    {
        $this->authInfo = new AuthInfo('test', 10, 'test_refresh');

        return new RefreshUserInfoTask($this->authInfo);
    }

    /**
     * Asserts that two auth info objects are different.
     *
     * @param AuthInfo $oldInfo
     * @param AuthInfo $newInfo
     */
    protected function assertChangedAuthInfo($oldInfo, $newInfo)
    {
        $this->assertNotEquals(
            $oldInfo->getAccessToken(),
            $newInfo->getAccessToken(),
            'Method should set access token in configuration service.'
        );
        $this->assertNotEquals(
            $oldInfo->getRefreshToken(),
            $newInfo->getRefreshToken(),
            'Method should set refresh token in configuration service.'
        );
        $this->assertNotEquals(
            $oldInfo->getAccessTokenDuration(),
            $newInfo->getAccessTokenDuration(),
            'Method should set access token duration time in configuration service.'
        );
    }

    /**
     * Asserts that two auth info objects are the same.
     *
     * @param AuthInfo $oldInfo
     * @param AuthInfo $newInfo
     */
    protected function assertNotChangedAuthInfo($oldInfo, $newInfo)
    {
        $this->assertEquals(
            $oldInfo->getAccessToken(),
            $newInfo->getAccessToken(),
            'Method should not set access token in configuration service.'
        );
        $this->assertEquals(
            $oldInfo->getRefreshToken(),
            $newInfo->getRefreshToken(),
            'Method should not set refresh token in configuration service.'
        );
        $this->assertEquals(
            $oldInfo->getAccessTokenDuration(),
            $newInfo->getAccessTokenDuration(),
            'Method should not set access token duration time in configuration service.'
        );
    }

    /**
     * Gets dummy auth info.
     *
     * @return \CleverReach\BusinessLogic\Entity\AuthInfo Auth info.
     */
    private function getOldAuthInfo()
    {
        return new AuthInfo('old_access_token', 123, 'old_refresh_token');
    }

    /**
     * Sets fake user info response.
     */
    private function setFakeUserInfoResponse()
    {
        $dir = realpath(__DIR__ . '/../..') . '/Common/fakeAPIResponses';
        $this->proxy->setResponse(file_get_contents("$dir/getUserInfo.json"));
    }
}