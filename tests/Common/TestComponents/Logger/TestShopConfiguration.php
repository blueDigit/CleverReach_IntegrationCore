<?php

namespace CleverReach\Tests\Common\TestComponents\Logger;

use CleverReach\BusinessLogic\Entity\AuthInfo;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Logger\Logger;

class TestShopConfiguration extends Configuration
{
    protected static $context = '';
    private $integrationID;
    private $minLogeLevel = Logger::DEBUG;
    protected $accessToken;
    protected $refreshToken;
    protected $expirationTime;
    private $productSearchStatus = true;
    private $productSearchParameters;
    private $integrationName;
    private $integrationListName;
    private $userAccountId;
    private $loggerStatus;
    private $maxStartedTasksLimit = 8;
    private $batchSize = 250;
    protected $userInfo;
    private $clientId = 'zhYTmczOCA';
    private $clientSecret = 'p0ZlXjkyvdjd23f5I2qSiZahSwurl62K';
    private $taskRunnerStatus = '';
    private $verificationToken;
    private $callToken;
    private $currentTime = 0;

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration) proper context must be set based on middleware account
     * that is using core library functionality. This context should then be used by business services to fetch account specific
     * data.Core will set context provided upon task enqueueing before task execution.
     *
     * @param string $context Context to set
     */
    public function setContext($context)
    {
        self::$context = $context;
    }

    public function getCurrentTime()
    {
        if ($this->currentTime === 0) {
            $this->currentTime = time();
        }

        return $this->currentTime;
    }

    /**
     * @return string
     */
    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     */
    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    /**
     * @inheritdoc
     */
    public function getAccessTokenExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * @inheritdoc
     */
    public function setAccessTokenExpirationTime($expirationTime)
    {
        $this->expirationTime = $this->getCurrentTime() + min($expirationTime, 86400);
    }

    /**
     * @inheritdoc
     */
    public function getAuthInfo()
    {
        return new AuthInfo(
            $this->getAccessToken(),
            $this->getAccessTokenExpirationTime() - $this->getCurrentTime(),
            $this->getRefreshToken()
        );
    }

    /**
     * Gets task execution context
     *
     * @return string Context in which task is being executed. If no context is provided empty string is returned (global context)
     */
    public function getContext()
    {
        return self::$context;
    }

    /**
     * @return int
     */
    public function getIntegrationId()
    {
        return $this->integrationID;
    }

    /**
     * @param int $integrationID
     */
    public function setIntegrationID($integrationID)
    {
        $this->integrationID = $integrationID;
    }

    /**
     * Saves min log level in integration database
     *
     * @param int $minLogLevel
     */
    public function saveMinLogLevel($minLogLevel)
    {
        $this->minLogeLevel = $minLogLevel;
    }

    /**
     * Retrieves min log level from integration database
     *
     * @return int
     */
    public function getMinLogLevel()
    {
        return $this->minLogeLevel;
    }

    /**
     * Retrieves access token from integration database
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string|null $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Saves product search parameters
     * Example:
     * {
     *    "name": "My shop - Product search",
     *    "url": "http://myshop.com/endpoint",
     *    "password": "Adsdf34fdS4Dsd2Wdd345"
     * }
     *
     * @param array $productSearchParameters
     */
    public function setProductSearchParameters($productSearchParameters)
    {
        $this->productSearchParameters = $productSearchParameters;
    }

    /**
     * Retrieves parameters needed for product search registrations
     *
     * @return array Array should contain name, url and password
     */
    public function getProductSearchParameters()
    {
        return $this->productSearchParameters;
    }

    /**
     * Set product search status (enabled/disabled)
     *
     * @param bool $status
     */
    public function setIsProductSearchEnabled($status)
    {
        $this->productSearchStatus = $status;
    }

    /**
     * Return whether product search is enabled or not
     *
     * @return bool
     */
    public function isProductSearchEnabled()
    {
        return $this->productSearchStatus;
    }

    /**
     * Set integration name
     *
     * @param string $integrationName
     */
    public function setIntegrationName($integrationName)
    {
        $this->integrationName = $integrationName;
    }

    /**
     * Retrieves integration name
     *
     * @return string
     */
    public function getIntegrationName()
    {
        return $this->integrationName;
    }

    /**
     * Sets integration list name
     *
     * @param $listName
     */
    public function setIntegrationListName($listName)
    {
        $this->integrationListName = $listName;
    }

    /**
     * Retrieves integration list name
     *
     * @return string
     */
    public function getIntegrationListName()
    {
        return $this->integrationListName ? : $this->integrationName;
    }

    /**
     * Set user account id
     *
     * @param string $userAccountId
     */
    public function setUserAccountId($userAccountId)
    {
        $this->userAccountId = $userAccountId;
    }

    /**
     * Retrieves user account id
     *
     * @return string
     */
    public function getUserAccountId()
    {
        return $this->userAccountId;
    }

    /**
     * Set default logger status (enabled/disabled)
     *
     * @param bool $status
     */
    public function setDefaultLoggerEnabled($status)
    {
        $this->loggerStatus = $status;
    }

    /**
     * Return whether default logger is enabled or not
     *
     * @return bool
     */
    public function isDefaultLoggerEnabled()
    {
        return $this->loggerStatus;
    }

    /**
     * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks can be
     * in "in_progress" status at the same time
     *
     * @return int
     */
    public function getMaxStartedTasksLimit()
    {
        return $this->maxStartedTasksLimit;
    }

    public function getTaskRunnerWakeupDelay()
    {
        return null;
    }

    public function getTaskRunnerMaxAliveTime()
    {
        return null;
    }

    /**
     * SGets the number of maximum allowed started task at the point in time. This number will determine how many tasks can be
     * in "in_progress" status at the same time
     *
     * @param int $maxStartedTasksLimit New limit
     */
    public function setMaxStartedTasksLimit($maxStartedTasksLimit)
    {
        $this->maxStartedTasksLimit = $maxStartedTasksLimit;
    }

    /**
     * Gets maximum number of failed task execution retries. System will retry task execution in case of error until this number
     * is reached. Return null to use default system value (5)
     *
     * @return int|null
     */
    public function getMaxTaskExecutionRetries()
    {
        return null;
    }

    /**
     * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks as expired.
     * Return null to use default system value (30)
     *
     * @return int|null
     */
    public function getMaxTaskInactivityPeriod()
    {
        return null;
    }

    /**
     * @return int
     */
    public function getRecipientsSynchronizationBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     */
    public function setRecipientsSynchronizationBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * @param array $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * Get user information
     *
     * @return array
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Gets client id
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Gets client secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @return array
     */
    public function getTaskRunnerStatus()
    {
        return json_decode($this->taskRunnerStatus, true);
    }

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid
     * @param int $timestamp
     */
    public function setTaskRunnerStatus($guid, $timestamp)
    {
        $this->taskRunnerStatus = json_encode(array('guid' => $guid, 'timestamp' => $timestamp));
    }

    public function getCrEventHandlerURL()
    {
        return 'https://example.com/eventendpoint';
    }

    public function getCrEventHandlerVerificationToken()
    {
        if ($this->verificationToken === null) {
            $this->verificationToken = 'sd89fsdhfsfsdfhsdfsdfsdf';
        }

        return $this->verificationToken;
    }

    public function setCrEventHandlerCallToken($token)
    {
        $this->callToken = $token;
    }
}
