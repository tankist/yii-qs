<?php

Yii::import('qs.web.auth.external.*');

/**
 * Test case for the extension "qs.web.auth.external.QsAuthExternalServiceOpenId".
 * @see QsAuthExternalServiceOpenId
 */
class QsAuthExternalServiceOpenIdTest extends CTestCase {
	/**
	 * Creates test external auth service instance.
	 * @return QsAuthExternalServiceOpenId external auth service instance.
	 */
	protected function createTestAuthExternalService() {
		return $this->getMock('QsAuthExternalServiceOpenId', array('fake'));
	}

	// Tests :

	public function testSetGet() {
		$authExternalService = $this->createTestAuthExternalService();

		$openIdClient = new QsOpenIdClient();
		$authExternalService->setOpenIdClient($openIdClient);
		$this->assertEquals($openIdClient, $authExternalService->getOpenIdClient(), 'Unable to setup OpenId client!');

		$requiredAttributes = array(
			'test_attribute_1',
			'test_attribute_2',
		);
		$authExternalService->setRequiredAttributes($requiredAttributes);
		$this->assertEquals($requiredAttributes, $authExternalService->getRequiredAttributes(), 'Unable to setup OpenId required attributes!');
	}
}
