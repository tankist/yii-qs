<?php

Yii::import('qs.web.auth.external.*');

/**
 * Test case for the extension "qs.web.auth.external.QsAuthExternalUserIdentity".
 * @see QsAuthExternalUserIdentity
 */
class QsAuthExternalUserIdentityTest extends CTestCase {
	/**
	 * Creates test external auth service instance.
	 * @return QsAuthExternalService external auth service instance.
	 */
	protected function createTestAuthExternalService() {
		return $this->getMock('QsAuthExternalService', array('authenticate'));
	}

	/**
	 * Invokes the user identity method even if it is protected.
	 * @param QsAuthExternalService $userIdentity user identity isntance.
	 * @param string $methodName name of the method to be invoked.
	 * @param array $arguments method arguments.
	 * @return mixed method invoke result.
	 */
	protected function invokeUserIdentityMethod($userIdentity, $methodName, array $arguments = array()) {
		$userIdentityClassReflection = new ReflectionClass(get_class($userIdentity));
		$methodReflection = $userIdentityClassReflection->getMethod($methodName);
		$methodReflection->setAccessible(true);
		$result = $methodReflection->invokeArgs($userIdentity, $arguments);
		$methodReflection->setAccessible(false);
		return $result;
	}

	// Tests:

	public function testSetGet() {
		$userIdentity = new QsAuthExternalUserIdentity();

		$service = $this->createTestAuthExternalService();
		$userIdentity->setService($service);
		$this->assertEquals($service, $userIdentity->getService(), 'Unable to setup service!');
	}

	public function testFetchIdFromServerAttributes() {
		$userIdentity = new QsAuthExternalUserIdentity();
		$id = 'test_id';
		$serviceAttributes = array(
			'id' => $id
		);
		$this->assertEquals($id, $this->invokeUserIdentityMethod($userIdentity, 'fetchidFromServiceAttributes', array($serviceAttributes)));
	}

	/**
	 * Data provider for {@link testFetchName}
	 * @return array test data.
	 */
	public function fetchNameDataProvider() {
		return array(
			array(
				array(
					'name' => 'test_name',
				),
				'test_name',
			),
			array(
				array(
					'firstname' => 'test_first_name',
					'lastname' => 'test_last_name',
				),
				'test_first_name test_last_name',
			),
			array(
				array(
					'first_name' => 'test_first_name',
					'last_name' => 'test_last_name',
				),
				'test_first_name test_last_name',
			),
			array(
				array(
					'firstName' => 'test_first_name',
					'lastName' => 'test_last_name',
				),
				'test_first_name test_last_name',
			),
		);
	}

	/**
	 * @dataProvider fetchNameDataProvider
	 *
	 * @param array $serviceAttributes
	 * @param $expectedName
	 */
	public function testFetchName(array $serviceAttributes, $expectedName) {
		$userIdentity = new QsAuthExternalUserIdentity();
		$this->assertEquals($expectedName, $this->invokeUserIdentityMethod($userIdentity, 'fetchNameFromServiceAttributes', array($serviceAttributes)));
	}
}
