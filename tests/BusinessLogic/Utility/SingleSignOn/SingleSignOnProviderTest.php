<?php

namespace BusinessLogic\Utility\SingleSignOn;

use CleverReach\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\ServiceRegister;
use PHPUnit\Framework\TestCase;

class SingleSignOnProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        /** @var \CleverReach\Infrastructure\Interfaces\Required\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $accessToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6IjIwMTYifQ
.eyJpc3MiOiJyZXN0LmNsZXZlcnJlYWNoLmNvbSIsImlhdCI6MTU1Mzg2MjMxNywiZXhwIjoxNTg1Mzk4MzE3LCJjbGllbnRfaWQiOjE4MzI2NSwic2hhcmQiOiJzaGFyZDYiLCJ6b25lIjoxLCJ1c2VyX2lkIjowLCJsb2dpbiI6Im9hdXRoLVJlSHlCSVNzS2UiLCJyb2xlIjoidXNlciIsInNjb3BlcyI6Im9hX2Jhc2ljIG9hX3JlY2VpdmVycyBvYV9yZXBvcnRzIG9hX2Zvcm1zIG9hX21haWxpbmdzIG9hX2JyYW5kaW5nIG9hX3dlYmhvb2tzIG9hX3doaXRlbGFiZWwiLCJpbmRlbnRpZmllciI6InN5c3RlbSIsImNhbGxlciI6NSwibGltaXQiOnsibWFpbGluZ1NlbmQiOjB9fQ
.2xWEFXmEromhS6d-3e-tak1H8IMGEhg5Q1YqTwB8EmY';
        $configService->setAccessToken($accessToken);
        $userInfoFile = realpath(__DIR__ . '/../../../') . '/Common/fakeAPIResponses/getUserInfo.json';
        $configService->setUserInfo(json_decode(file_get_contents($userInfoFile), true));
    }

    public function testGettingSSOLink()
    {
        /** @var \CleverReach\Infrastructure\Interfaces\Required\Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $userInfo = $configService->getUserInfo();
        $deepLink = '/admin/mailing_create_new.php';
        $ssoUrl = SingleSignOnProvider::getUrl($deepLink);
        $urlComponents = parse_url($ssoUrl);
        $queryParams = array();
        parse_str($urlComponents['query'], $queryParams);

        $this->assertArrayHasKey('otp', $queryParams);
        $this->assertArrayHasKey('oid', $queryParams);
        $this->assertArrayHasKey('exp', $queryParams);
        $this->assertArrayHasKey('ref', $queryParams);

        $this->assertEquals($deepLink, urldecode($queryParams['ref']));
        $this->assertEquals($configService->getClientId(), $queryParams['oid']);
        $this->assertEquals($userInfo['login_domain'], $urlComponents['host']);
    }
}