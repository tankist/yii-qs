<?php

Yii::import('qs.web.auth.external.widgets.QsAuthExternalServiceChoice');
Yii::import('qs.web.auth.external.*');

/**
 * Test case for the extension "qs.web.auth.external.widgets.QsAuthExternalServiceChoice".
 * @see QsAuthExternalServiceChoice
 */
class QsAuthExternalServiceChoiceTest extends CTestCase {
	/**
	 * Creates test external service instance.
	 * @return QsAuthExternalService external service instance.
	 */
	protected function createTestExternalService() {
		$externalService = $this->getMock('QsAuthExternalService', array('authenticate'));
		return $externalService;
	}

	// Tests :

	public function testSetGet() {
		$widget = new QsAuthExternalServiceChoice();

		$externalServices = array(
			$this->createTestExternalService(),
			$this->createTestExternalService(),
		);
		$widget->setServices($externalServices);
		$this->assertEquals($externalServices, $widget->getServices(), 'Unable to setup external services!');
	}
}
