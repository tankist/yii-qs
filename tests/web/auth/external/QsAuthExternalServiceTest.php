<?php

Yii::import('qs.web.auth.external.*');

/**
 * Test case for the extension "qs.web.auth.external.QsAuthExternalService".
 * @see QsAuthExternalService
 */
class QsAuthExternalServiceTest extends CTestCase {
	/**
	 * Creates test external auth service instance.
	 * @return QsAuthExternalService external auth service instance.
	 */
	protected function createTestAuthExternalService() {
		return $this->getMock('QsAuthExternalService', array('authenticate'));
	}

	// Tests :

	public function testSetGet() {
		$authExternalService = $this->createTestAuthExternalService();

		$id = 'test_service_id';
		$authExternalService->setId($id);
		$this->assertEquals($id, $authExternalService->getId(), 'Unable to setup id!');

		$successUrl = 'http://test.success.url';
		$authExternalService->setSuccessUrl($successUrl);
		$this->assertEquals($successUrl, $authExternalService->getSuccessUrl(), 'Unable to setup success URL!');

		$cancelUrl = 'http://test.cancel.url';
		$authExternalService->setCancelUrl($cancelUrl);
		$this->assertEquals($cancelUrl, $authExternalService->getCancelUrl(), 'Unable to setup cancel URL!');

		$attributes = array(
			'test_attribute_name_1' => 'test_attribute_value_1',
			'test_attribute_name_2' => 'test_attribute_value_2',
		);
		$authExternalService->setAttributes($attributes);
		$this->assertEquals($attributes, $authExternalService->getAttributes(), 'Unable to setup attributes!');

		$normalizeAttributeMap = array(
			'test_normalized_attribute_name_1' => 'test_attribute_name_1',
			'test_normalized_attribute_name_2' => 'test_attribute_name_2',
		);
		$authExternalService->setNormalizeAttributeMap($normalizeAttributeMap);
		$this->assertEquals($normalizeAttributeMap, $authExternalService->getNormalizeAttributeMap(), 'Unable to setup normalize attribute map!');
	}

	public function testGetDescriptiveData() {
		$authExternalService = $this->createTestAuthExternalService();

		$this->assertNotEmpty($authExternalService->getName(), 'Unable to get name!');
		$this->assertNotEmpty($authExternalService->getTitle(), 'Unable to get title!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultSuccessUrl() {
		$authExternalService = $this->createTestAuthExternalService();

		$this->assertNotEmpty($authExternalService->getSuccessUrl(), 'Unable to get default success URL!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultCancelUrl() {
		$authExternalService = $this->createTestAuthExternalService();

		$this->assertNotEmpty($authExternalService->getSuccessUrl(), 'Unable to get default cancel URL!');
	}

	public function testRedirect() {
		$authExternalService = $this->createTestAuthExternalService();

		$url = 'http://test.url';
		$this->expectOutputRegex('/' . str_replace('/', '\\/', $url) . '/is');

		$authExternalService->redirect($url, true, array(), false);
	}

	public function testCreateUserIdentity() {
		$authExternalService = $this->createTestAuthExternalService();

		$userIdentity = $authExternalService->createUserIdentity();
		$this->assertTrue(is_object($userIdentity), 'Unable to create user identity!');
		$this->assertEquals($authExternalService, $userIdentity->getService(), 'Unable to setup service for created user identity!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testNormalizeAttributes() {
		$authExternalService = $this->createTestAuthExternalService();

		$attributes = array(
			'attribute_1' => 'value_1',
			'attribute_2' => 'value_2',
		);
		$normalizedNameSuffix = '_normalized';
		$normalizeAttributeMap = array();
		foreach ($attributes as $name => $value) {
			$normalizeAttributeMap[$name . $normalizedNameSuffix] = $name;
		}

		$authExternalService->setNormalizeAttributeMap($normalizeAttributeMap);
		$authExternalService->setAttributes($attributes);

		$normalizedAttributes = $authExternalService->getAttributes();

		foreach ($normalizeAttributeMap as $normalizedName => $actualName) {
			$this->assertEquals($attributes[$actualName], $normalizedAttributes[$normalizedName]);
		}
	}
}
