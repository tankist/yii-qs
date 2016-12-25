<?php

Yii::import('qs.web.auth.oauth.*');

/**
 * Test case for the extension "qs.web.auth.oauth.QsOAuthClient2".
 * @see QsOAuthClient2
 */
class QsOAuthClient2Test extends CTestCase {
	public function testBuildAuthUrl() {
		$oauthClient = new QsOAuthClient2();
		$authUrl = 'http://test.auth.url';
		$oauthClient->authUrl = $authUrl;
		$clientId = 'test_client_id';
		$oauthClient->clientId = $clientId;
		$returnUrl = 'http://test.return.url';
		$oauthClient->setReturnUrl($returnUrl);

		$builtAuthUrl = $oauthClient->buildAuthUrl();

		$this->assertContains($authUrl, $builtAuthUrl, 'No auth URL present!');
		$this->assertContains($clientId, $builtAuthUrl, 'No client id present!');
		$this->assertContains(QsOAuthHelper::urlEncode($returnUrl), $builtAuthUrl, 'No return URL present!');
	}
}
