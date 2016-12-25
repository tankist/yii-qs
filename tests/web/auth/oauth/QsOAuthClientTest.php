<?php

Yii::import('qs.web.auth.oauth.*');
Yii::import('qs.web.auth.oauth.signature.*');

/**
 * Test case for the extension "qs.web.auth.oauth.QsOAuthClient".
 * @see QsOAuthClient
 */
class QsOAuthClientTest extends CTestCase {
	/**
	 * Creates test OAuth client instance.
	 * @return QsOAuthClient oauth client.
	 */
	protected function createOAuthClient() {
		$oauthClient = $this->getMock('QsOAuthClient', array('setState', 'getState', 'composeRequestCurlOptions', 'refreshAccessToken', 'apiInternal'));
		$oauthClient->expects($this->any())->method('setState')->will($this->returnValue($oauthClient));
		$oauthClient->expects($this->any())->method('getState')->will($this->returnValue(null));
		//$oauthClient->expects($this->any())->method('apiInternal')->will($this->returnCallback());
		return $oauthClient;
	}

	/**
	 * Invokes the OAuth client method even if it is protected.
	 * @param QsOAuthClient $oauthClient OAuth client instance.
	 * @param string $methodName name of the method to be invoked.
	 * @param array $arguments method arguments.
	 * @return mixed method invoke result.
	 */
	protected function invokeOAuthClientMethod(QsOAuthClient $oauthClient, $methodName, array $arguments = array()) {
		$classReflection = new ReflectionClass(get_class($oauthClient));
		$methodReflection = $classReflection->getMethod($methodName);
		$methodReflection->setAccessible(true);
		$result = $methodReflection->invokeArgs($oauthClient, $arguments);
		$methodReflection->setAccessible(false);
		return $result;
	}

	// Tests :

	public function testSetGet() {
		$oauthClient = $this->createOAuthClient();

		$returnUrl = 'http://test.return.url';
		$oauthClient->setReturnUrl($returnUrl);
		$this->assertEquals($returnUrl, $oauthClient->getReturnUrl(), 'Unable to setup return URL!');

		$curlOptions = array(
			'option1' => 'value1',
			'option2' => 'value2',
		);
		$oauthClient->setCurlOptions($curlOptions);
		$this->assertEquals($curlOptions, $oauthClient->getCurlOptions(), 'Unable to setup cURL options!');
	}

	public function testSetupComponents() {
		$oauthClient = $this->createOAuthClient();

		$oauthToken = new QsOAuthToken();
		$oauthClient->setAccessToken($oauthToken);
		$this->assertEquals($oauthToken, $oauthClient->getAccessToken(), 'Unable to setup token!');

		$oauthSignatureMethod = new QsOAuthSignatureMethodPlainText();
		$oauthClient->setSignatureMethod($oauthSignatureMethod);
		$this->assertEquals($oauthSignatureMethod, $oauthClient->getSignatureMethod(), 'Unable to setup signature method!');
	}

	/**
	 * @depends testSetupComponents
	 */
	public function testSetupComponentsByConfig() {
		$oauthClient = $this->createOAuthClient();

		$oauthToken = array(
			'token' => 'test_token',
			'tokenSecret' => 'test_token_secret',
		);
		$oauthClient->setAccessToken($oauthToken);
		$this->assertEquals($oauthToken['token'], $oauthClient->getAccessToken()->getToken(), 'Unable to setup token as config!');

		$oauthSignatureMethod = array(
			'class' => 'QsOAuthSignatureMethodPlainText'
		);
		$oauthClient->setSignatureMethod($oauthSignatureMethod);
		$returnedSignatureMethod = $oauthClient->getSignatureMethod();
		$this->assertEquals($oauthSignatureMethod['class'], get_class($returnedSignatureMethod), 'Unable to setup signature method as config!');
	}

	/**
	 * Data provider fro {@link testComposeUrl()}.
	 * @return array test data.
	 */
	public function composeUrlDataProvider() {
		return array(
			array(
				'http://test.url',
				array(
					'param1' => 'value1',
					'param2' => 'value2',
				),
				'http://test.url?param1=value1&param2=value2',
			),
			array(
				'http://test.url?with=some',
				array(
					'param1' => 'value1',
					'param2' => 'value2',
				),
				'http://test.url?with=some&param1=value1&param2=value2',
			),
		);
	}

	/**
	 * @dataProvider composeUrlDataProvider
	 *
	 * @param string $url request URL.
	 * @param array $params request params
	 * @param string $expectedUrl expected composed URL.
	 */
	public function testComposeUrl($url, array $params, $expectedUrl) {
		$oauthClient = $this->createOAuthClient();
		$composedUrl = $this->invokeOAuthClientMethod($oauthClient, 'composeUrl', array($url, $params));
		$this->assertEquals($expectedUrl, $composedUrl);
	}

	/**
	 * Data provider for {@link testDetermineContentTypeByHeaders}.
	 * @return array test data.
	 */
	public function determineContentTypeByHeadersDataProvider() {
		return array(
			array(
				array(
					'content_type' => 'application/json'
				),
				QsOAuthClient::CONTENT_TYPE_JSON
			),
			array(
				array(
					'content_type' => 'application/x-www-form-urlencoded'
				),
				QsOAuthClient::CONTENT_TYPE_URLENCODED
			),
			array(
				array(
					'content_type' => 'application/xml'
				),
				QsOAuthClient::CONTENT_TYPE_XML
			),
			array(
				array(
					'some_header' => 'some_header_value'
				),
				QsOAuthClient::CONTENT_TYPE_AUTO
			),
			array(
				array(
					'content_type' => 'unknown'
				),
				QsOAuthClient::CONTENT_TYPE_AUTO
			),
		);
	}

	/**
	 * @dataProvider determineContentTypeByHeadersDataProvider
	 *
	 * @param array $headers request headers.
	 * @param string $expectedResponseType expected response type.
	 */
	public function testDetermineContentTypeByHeaders(array $headers, $expectedResponseType) {
		$oauthClient = $this->createOAuthClient();
		$responseType = $this->invokeOAuthClientMethod($oauthClient, 'determineContentTypeByHeaders', array($headers));
		$this->assertEquals($expectedResponseType, $responseType);
	}

	/**
	 * Data provider for {@link testDetermineContentTypeByRaw}.
	 * @return array test data.
	 */
	public function determineContentTypeByRawDataProvider() {
		return array(
			array('{name: value}', QsOAuthClient::CONTENT_TYPE_JSON),
			array('name=value', QsOAuthClient::CONTENT_TYPE_URLENCODED),
			array('name1=value1&name2=value2', QsOAuthClient::CONTENT_TYPE_URLENCODED),
			array('<?xml version="1.0" encoding="UTF-8"?><tag>Value</tag>', QsOAuthClient::CONTENT_TYPE_XML),
			array('<tag>Value</tag>', QsOAuthClient::CONTENT_TYPE_XML),
		);
	}

	/**
	 * @dataProvider determineContentTypeByRawDataProvider
	 *
	 * @param string $rawResponse raw response content.
	 * @param string $expectedResponseType expected response type.
	 */
	public function testDetermineContentTypeByRaw($rawResponse, $expectedResponseType) {
		$oauthClient = $this->createOAuthClient();
		$responseType = $this->invokeOAuthClientMethod($oauthClient, 'determineContentTypeByRaw', array($rawResponse));
		$this->assertEquals($expectedResponseType, $responseType);
	}

	/**
	 * Data provider for {@link testApiUrl}.
	 * @return array test data.
	 */
	public function apiUrlDataProvider() {
		return array(
			array(
				'http://api.base.url',
				'sub/url',
				'http://api.base.url/sub/url',
			),
			array(
				'http://api.base.url',
				'http://api.base.url/sub/url',
				'http://api.base.url/sub/url',
			),
			array(
				'http://api.base.url',
				'https://api.base.url/sub/url',
				'https://api.base.url/sub/url',
			),
		);
	}

	/**
	 * @dataProvider apiUrlDataProvider
	 *
	 * @param $apiBaseUrl
	 * @param $apiSubUrl
	 * @param $expectedApiFullUrl
	 */
	public function testApiUrl($apiBaseUrl, $apiSubUrl, $expectedApiFullUrl) {
		$oauthClient = $this->createOAuthClient();
		$oauthClient->expects($this->any())->method('apiInternal')->will($this->returnArgument(1));

		$accessToken = new QsOAuthToken();
		$accessToken->setToken('test_access_token');
		$accessToken->setExpireDuration(1000);
		$oauthClient->setAccessToken($accessToken);

		$oauthClient->apiBaseUrl = $apiBaseUrl;

		$this->assertEquals($expectedApiFullUrl, $oauthClient->api($apiSubUrl));
	}
}
