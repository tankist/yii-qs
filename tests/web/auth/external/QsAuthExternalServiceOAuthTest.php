<?php

Yii::import('qs.web.auth.external.*');

/**
 * Test case for the extension "qs.web.auth.external.QsAuthExternalServiceOAuth".
 * @see QsAuthExternalServiceOAuth
 */
class QsAuthExternalServiceOAuthTest extends CTestCase {
	/**
	 * Creates test external auth service instance.
	 * @return QsAuthExternalServiceOAuth2 external auth service instance.
	 */
	protected function createTestAuthExternalService() {
		return $this->getMock('QsAuthExternalServiceOAuth', array('authenticate'));
	}

	// Tests :

	public function testSetGet() {
		$authExternalService = $this->createTestAuthExternalService();

		$oAuthClient = new QsOAuthClient2();
		$authExternalService->setOauthClient($oAuthClient);
		$this->assertEquals($oAuthClient, $authExternalService->getOauthClient(), 'Unable to setup OAuth client!');
	}
}
