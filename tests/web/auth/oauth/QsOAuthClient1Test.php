<?php

Yii::import('qs.web.auth.oauth.*');
Yii::import('qs.web.auth.oauth.signature.*');

/**
 * Test case for the extension "qs.web.auth.oauth.QsOAuthClient1".
 * @see QsOAuthClient1
 */
class QsOAuthClient1Test extends CTestCase {
	/**
	 * Invokes the OAuth client method even if it is protected.
	 * @param QsOAuthClient1 $oauthClient OAuth client instance.
	 * @param string $methodName name of the method to be invoked.
	 * @param array $arguments method arguments.
	 * @return mixed method invoke result.
	 */
	protected function invokeOAuthClientMethod(QsOAuthClient1 $oauthClient, $methodName, array $arguments = array()) {
		$classReflection = new ReflectionClass(get_class($oauthClient));
		$methodReflection = $classReflection->getMethod($methodName);
		$methodReflection->setAccessible(true);
		$result = $methodReflection->invokeArgs($oauthClient, $arguments);
		$methodReflection->setAccessible(false);
		return $result;
	}

	// Tests :

	public function testSignRequest() {
		$oauthClient = new QsOAuthClient1();

		$oauthSignatureMethod = new QsOAuthSignatureMethodPlainText();
		$oauthClient->setSignatureMethod($oauthSignatureMethod);

		$signedParams = $this->invokeOAuthClientMethod($oauthClient, 'signRequest', array('GET', 'http://test.url', array()));
		$this->assertNotEmpty($signedParams['oauth_signature'], 'Unable to sign request!');
	}

	/**
	 * Data provider for {@link testComposeAuthorizationHeader()}.
	 * @return array test data.
	 */
	public function composeAuthorizationHeaderDataProvider() {
		return array(
			array(
				'',
				array(
					'oauth_test_name_1' => 'oauth_test_value_1',
					'oauth_test_name_2' => 'oauth_test_value_2',
				),
				'Authorization: OAuth oauth_test_name_1="oauth_test_value_1", oauth_test_name_2="oauth_test_value_2"'
			),
			array(
				'test_realm',
				array(
					'oauth_test_name_1' => 'oauth_test_value_1',
					'oauth_test_name_2' => 'oauth_test_value_2',
				),
				'Authorization: OAuth realm="test_realm", oauth_test_name_1="oauth_test_value_1", oauth_test_name_2="oauth_test_value_2"'
			),
			array(
				'',
				array(
					'oauth_test_name_1' => 'oauth_test_value_1',
					'test_name_2' => 'test_value_2',
				),
				'Authorization: OAuth oauth_test_name_1="oauth_test_value_1"'
			),
		);
	}

	/**
	 * @dataProvider composeAuthorizationHeaderDataProvider
	 *
	 * @param string $realm authorization realm.
	 * @param array $params request params.
	 * @param string $expectedAuthorizationHeader expected authorization header.
	 */
	public function testComposeAuthorizationHeader($realm, array $params, $expectedAuthorizationHeader) {
		$oauthClient = new QsOAuthClient1();
		$authorizationHeader = $this->invokeOAuthClientMethod($oauthClient, 'composeAuthorizationHeader', array($params, $realm));
		$this->assertEquals($expectedAuthorizationHeader, $authorizationHeader);
	}

	public function testBuildAuthUrl() {
		$oauthClient = new QsOAuthClient1();
		$authUrl = 'http://test.auth.url';
		$oauthClient->authUrl = $authUrl;

		$requestTokenToken = 'test_request_token';
		$requestToken = new QsOAuthToken();
		$requestToken->setToken($requestTokenToken);

		$builtAuthUrl = $oauthClient->buildAuthUrl($requestToken);

		$this->assertContains($authUrl, $builtAuthUrl, 'No auth URL present!');
		$this->assertContains($requestTokenToken, $builtAuthUrl, 'No token present!');
	}
}
