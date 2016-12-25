<?php

Yii::import('qs.web.auth.openid.QsOpenIdClient');

/**
 * Test case for the extension "qs.web.auth.openid.QsOpenIdClient".
 * @see QsOpenIdClient
 */
class QsOpenIdClientTest extends CTestCase {
	public function setUp() {
		$_SERVER['HTTP_HOST'] = 'tst.host.com';
		$_SERVER['REQUEST_URI'] = '';
	}

	public function testSetUpOpenIdInstance() {
		$openIdClient = new QsOpenIdClient();
		$openId = array(
			'returnUrl' => 'http://test.return.url'
		);
		$openIdClient->setOpenId($openId);
		$returnedOpenId = $openIdClient->getOpenId();
		$this->assertTrue(is_object($returnedOpenId), 'Unable to get OpenId instance!');
		$this->assertEquals($openId['returnUrl'], $returnedOpenId->returnUrl, 'Unable to setup property!');
	}

	/**
	 * @depends testSetUpOpenIdInstance
	 */
	public function testOpenIdPropertyMagicAccess() {
		$openIdClient = new QsOpenIdClient();
		$propertyName = 'returnUrl';
		$propertyValue = 'http://test.return.url';

		$openIdClient->$propertyName = $propertyValue;
		$returnedOpenId = $openIdClient->getOpenId();
		$this->assertEquals($propertyValue, $returnedOpenId->$propertyName, 'Unable to set OpenId property by magic method!');

		$this->assertEquals($propertyValue, $openIdClient->$propertyName, 'Unable to get OpenId property by magic method!');
	}

	/**
	 * @depends testSetUpOpenIdInstance
	 */
	public function testOpenIdMethodMagicAccess() {
		$openIdClient = new QsOpenIdClient();
		$this->assertNotNull($openIdClient->getAttributes(), 'Unable to invoke OpenId method by magic method!');
	}
}
