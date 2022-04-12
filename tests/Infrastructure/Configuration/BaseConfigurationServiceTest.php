<?php

namespace CleverReach\Tests\Infrastructure\Configuration;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Tests\Common\TestComponents\Configuration\TestConfiguration;
use CleverReach\Tests\Common\TestComponents\Configuration\TestConfigRepositoryService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Constraint_IsType as PHPUnit_IsType;

class BaseConfigurationServiceTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configService;

    public function setUp()
    {
        $configService = $this->configService = new TestConfiguration();
        $repository = new TestConfigRepositoryService();
        $repository->flush();
        new ServiceRegister(
            array(
                ConfigRepositoryInterface::CLASS_NAME =>
                    function() use($repository) {
                    return $repository;
                },
                Configuration::CLASS_NAME =>
                function() use ($configService) {
                    return $configService;
                }
            )
        );
    }

    public function testBehaviourOfConfigurationMethods()
    {
        $isFirstEmailBuild = $this->configService->isFirstEmailBuilt();
        self::assertFalse($isFirstEmailBuild);
        self::assertInternalType(PHPUnit_IsType::TYPE_BOOL, $isFirstEmailBuild);
        $this->configService->setIsFirstEmailBuilt(1);
        self::assertTrue($this->configService->isFirstEmailBuilt());

        $minLogLevel = $this->configService->getMinLogLevel();
        self::assertEquals(Logger::WARNING, $minLogLevel);
        self::assertInternalType(PHPUnit_IsType::TYPE_INT, $minLogLevel);
        $this->configService->saveMinLogLevel(Logger::DEBUG);
        self::assertEquals(Logger::DEBUG, $this->configService->getMinLogLevel());


        $userId = $this->configService->getUserAccountId();
        self::assertEquals('', $userId);
        self::assertInternalType(PHPUnit_IsType::TYPE_STRING, $userId);

        self::assertNull($this->configService->getUserInfo());
        $this->configService->setUserInfo(array('id' => '15646', 'name' => 'Test User', 'email' => 'test@example.com'));
        $userInfo = $this->configService->getUserInfo();
        self::assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $userInfo);
        self::assertArrayHasKey('id', $userInfo);
        self::assertEquals('15646', $this->configService->getUserAccountId());

        self::assertNull($this->configService->getTaskRunnerStatus());
        $this->configService->setTaskRunnerStatus('222_1111', 37845);
        $taskRunnerStatus = $this->configService->getTaskRunnerStatus();
        self::assertInternalType(PHPUnit_IsType::TYPE_ARRAY, $taskRunnerStatus);
        self::assertArrayHasKey('guid', $taskRunnerStatus);
        self::assertArrayHasKey('timestamp', $taskRunnerStatus);
    }

    public function testRefreshToken()
    {
        $token = md5(time());
        $this->configService->setRefreshToken($token);
        $retrievedToken = $this->configService->getRefreshToken();
        self::assertEquals($token, $retrievedToken);
    }

    public function testAccessTokenDuration()
    {
        $now = time() + 10;
        $this->configService->setAccessTokenExpirationTime(10);
        $duration = $this->configService->getAccessTokenExpirationTime();
        $diff = abs($duration - $now);
        self::assertLessThan(1, $diff);

        $now = time() + 86400;
        $this->configService->setAccessTokenExpirationTime(1000000);
        $duration = $this->configService->getAccessTokenExpirationTime();
        $diff = abs($duration - $now);
        self::assertLessThan(1, $diff);
    }

    public function testAccessTokenExpiration()
    {
        $expired = $this->configService->isAccessTokenExpired();
        self::assertFalse($expired);

        $this->configService->setAccessToken('testasdf');
        $expired = $this->configService->isAccessTokenExpired();
        self::assertFalse($expired);

        $this->configService->setAccessTokenExpirationTime(-10);
        $expired = $this->configService->isAccessTokenExpired();
        self::assertTrue($expired);

        $this->configService->setAccessTokenExpirationTime(10);
        $expired = $this->configService->isAccessTokenExpired();
        $this->assertFalse($expired);
    }
}