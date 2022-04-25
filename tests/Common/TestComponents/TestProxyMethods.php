<?php

namespace CleverReach\Tests\Common\TestComponents;

use CleverReach\BusinessLogic\Entity\Recipient;
use CleverReach\BusinessLogic\Proxy;
use CleverReach\BusinessLogic\Utility\Filter;
use CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException;
use CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException;
use SebastianBergmann\CodeCoverage\InvalidArgumentException;

class TestProxyMethods extends Proxy
{
    public $addProductSearch = array();
    public $getAllGlobalAttributesCalled = false;
    public $createGlobalAttributes = array();
    public $updateGlobalAttributes = array();
    public $deactivateNewsletterStatusRecipientsEmails = array();
    public $deactivatedRecipients = array();
    public $fakeAllGroups = array();
    public $fakeCreateGroupResponse = array();
    public $fakeCreateFilterResponse = array();
    public $fakeAllFilters = array();
    public $throwExceptionCode;
    public $callHistory = array();
    private $response = array();
    public $corruptedRecipientsBatch;

    /**
     * @param array $eventParameters
     *
     * @return string
     * @throws HttpRequestException
     */
    public function registerEventHandler($eventParameters)
    {
        $this->throwErrorsIfNeeded();

        return 'callToken-fsdjkfsdiofjsdk';
    }

    /**
     * Fake API call method
     *
     * @param array $data
     * @return array
     * @throws HttpRequestException
     */
    public function addOrUpdateProductSearch($data)
    {
        $this->addProductSearch = $data;
        $this->throwErrorsIfNeeded();

        return $this->response;
    }

    /**
     * Get all global attributes ids from CR
     * Documentation url: https://rest.cleverreach.com/explorer/v3#!/attributes-v3/list_get
     * @return array
     */
    public function getAllGlobalAttributes()
    {
        $this->getAllGlobalAttributesCalled = true;

        return $this->response;
    }

    /**
     * Create attribute
     * Example attribute:
     * array(
     *   "name" => "FirstName",
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name",
     *   "default_value" => "Bruce"
     * )
     * Documentation url: https://rest.cleverreach.com/explorer/v3#!/attributes-v3/create_post
     *
     * @param array $attribute
     * @throws HttpRequestException
     */
    public function createGlobalAttribute($attribute)
    {
        $this->createGlobalAttributes[] = $attribute['name'];
        $this->throwErrorsIfNeeded();
    }

    /**
     * Update attribute
     * Example attribute:
     * array(
     *   "type" => "text",
     *   "description" => "Description",
     *   "preview_value" => "real name"
     * )
     * Documentation url: https://rest.cleverreach.com/explorer/v3#!/attributes-v3/update_attr_put
     *
     * @param int $id
     * @param array $attribute
     * @throws HttpRequestException
     */
    public function updateGlobalAttribute($id, $attribute)
    {
        $this->updateGlobalAttributes[] = $attribute['name'];
        $this->throwErrorsIfNeeded();
    }

    /**
     * Fake API call
     * Checks if group exists in fake group array
     *
     * @param string $serviceName
     *
     * @return int|null
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function getGroupId($serviceName)
    {
        $this->throwErrorsIfNeeded();

        if (in_array($serviceName, $this->fakeAllGroups, true)) {
            return 1;
        }

        return null;
    }

    /**
     * Fake API call
     *
     * @param string $name
     *
     * @throws InvalidArgumentException
     * @throws HttpRequestException
     * @return int
     */
    public function createGroup($name)
    {
        $this->callHistory['createGroup'][] = array('serviceName' => $name);
        $this->throwErrorsIfNeeded();

        return $this->fakeCreateGroupResponse['id'];
    }

    /**
     * @param Filter $filter
     * @param int $integrationID
     * @throws HttpRequestException
     * @return int
     */
    public function createFilter(Filter $filter, $integrationID)
    {
        $this->callHistory['createFilter'][] = array('$rules' => $filter, 'integrationID' => $integrationID);
        $this->throwErrorsIfNeeded();

        return $this->fakeCreateFilterResponse['id'];
    }

    /**
     * @param int $integrationId
     *
     * @return array
     * @throws HttpRequestException
     */
    public function getAllFilters($integrationId)
    {
        $this->callHistory['getAllCRFilters'][] = array('integrationID' => $integrationId);
        $this->throwErrorsIfNeeded();

        return $this->fakeAllFilters;
    }

    /**
     * @param int $filterID
     * @param int $integrationID
     * @return bool
     */
    public function deleteFilter($filterID, $integrationID)
    {
        $this->callHistory['deleteFilter'][] = array(
            'filterID' => $filterID,
            'integrationID' => $integrationID
        );

        return array_key_exists($filterID, $this->fakeAllFilters);
    }

    /**
     * @param array $recipients
     *
     * @return array
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function recipientsMassUpdate(array $recipients)
    {
        $this->callHistory['recipientsMassUpdate'][] = array('recipients' => $recipients);
        $httpCodeBatchSizeTooBig = 413;
        $maxNumberOfTooBigBatchSituationsInTest = 2;

        if (count($this->callHistory['recipientsMassUpdate']) > $maxNumberOfTooBigBatchSituationsInTest) {
            $this->throwExceptionCode = null;
        }

        if ($this->throwExceptionCode !== null) {
            if ($this->throwExceptionCode === $httpCodeBatchSizeTooBig) {
                throw new HttpBatchSizeTooBigException('Batch size too big', $this->throwExceptionCode);
            }

            if ($this->corruptedRecipientsBatch === null
                || ($this->corruptedRecipientsBatch !== null
                    && $this->corruptedRecipientsBatch === count($this->callHistory['recipientsMassUpdate']))
            ) {
                throw new HttpRequestException('Some error message', $this->throwExceptionCode);
            }
        }

        return $this->response;
    }

    /**
     * @param array $emails
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function updateNewsletterStatus($emails)
    {
        $this->deactivateNewsletterStatusRecipientsEmails[] = $emails;
        $this->throwErrorsIfNeeded();
    }

    /**
     * @inheritdoc
     */
    public function getRecipient($groupId, $poolId)
    {
        $this->throwErrorsIfNeeded();

        return parent::getRecipient($groupId, $poolId);
    }

    /**
     * @param Recipient[] $recipients Array of recipient entities.
     *
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function deactivateRecipients($recipients)
    {
        foreach ($recipients as $recipient) {
            $this->deactivatedRecipients[$recipient->getEmail()] = array(
                'newsletter' => $recipient->getNewsletterSubscription(),
                'tags' => $recipient->getTags(),
            );
        }

        $this->throwErrorsIfNeeded();
    }

    /**
     * Get user information from CleverReach
     *
     * @param $accessToken
     * @return array
     * @throws HttpRequestException
     */
    public function getUserInfo($accessToken)
    {
        $this->throwErrorsIfNeeded();

        return $this->response;
    }

    /**
     * Set expected response
     *
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = json_decode($response, true);
    }

    /**
     * @throws \CleverReach\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    private function throwErrorsIfNeeded()
    {
        if ($this->throwExceptionCode !== null) {
            throw new HttpRequestException('Some error message', $this->throwExceptionCode);
        }
    }
}
