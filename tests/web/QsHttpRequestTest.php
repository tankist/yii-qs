<?php

Yii::import('qs.web.QsHttpRequest');

/**
 * Test case for the extension "qs.web.QsHttpRequestTest".
 * @see QsHttpRequestTest
 */
class QsHttpRequestTest extends CTestCase {
	/**
	 * Creates test HTTP request instance.
	 * @return QsHttpRequest test HTTP request instance.
	 */
	protected function createTestHttpRequest() {
		$config = array(
			'class' => 'QsHttpRequest'
		);
		$clientScript = Yii::createComponent($config);
		$clientScript->init();
		return $clientScript;
	}

	// Tests:

	public function testSetGet(){
		$httpRequest = new QsHttpRequest();

		$actualGet = array(
			'get_key_1' => 'get_value_1',
			'get_key_2' => 'get_value_2',
		);
		$httpRequest->setActualGet($actualGet);
		$this->assertEquals($actualGet, $httpRequest->getActualGet(), 'Unable to set actual GET!');

		$actualRequest = array(
			'request_key_1' => 'request_value_1',
			'request_key_2' => 'request_value_2',
		);
		$httpRequest->setActualRequest($actualRequest);
		$this->assertEquals($actualRequest, $httpRequest->getActualRequest(), 'Unable to set actual REQUEST!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSaveActualOnInit() {
		$actualGet = array(
			'get_key_1' => 'get_value_1',
			'get_key_2' => 'get_value_2',
		);
		$_GET = $actualGet;
		$actualRequest = $actualGet;
		$actualRequest['request_key_1'] = 'request_value_1';
		$_REQUEST = $actualRequest;

		$httpRequest = $this->createTestHttpRequest();
		$this->assertEquals($actualGet, $httpRequest->getActualGet(), 'Unable to save actual GET on init!');
		$this->assertEquals($actualRequest, $httpRequest->getActualRequest(), 'Unable to save actual REQUEST on init!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetMergedGet() {
		$httpRequest = new QsHttpRequest();

		$paramName = 'test_param';
		$actualParamValue = 'actual_param_value';
		$actualGet = array(
			$paramName => $actualParamValue
		);
		$httpRequest->setActualGet($actualGet);
		$globalGetParamValue = 'global_get_param_value';
		$_GET = array(
			$paramName => $globalGetParamValue,
			'some_additional_key' => 'some_additional_value'
		);

		$this->assertEquals(array_merge($_GET, $actualGet), $httpRequest->getMergedGet(), 'Unable to get merged GET!');
	}

	public function testCleanupArray() {
		$httpRequest = new QsHttpRequest();
		$httpRequestClassReflection = new ReflectionClass(get_class($httpRequest));
		$cleanupArrayReflection = $httpRequestClassReflection->getMethod('cleanupArray');
		$cleanupArrayReflection->setAccessible(true);

		$cleanArray = array(
			'some_scalar' => 'value',
			'zero' => 0,
			'some_array' => array(
				'some_array_key' => 'some_array_value'
			),
		);
		$dirtyArray = $cleanArray;
		$dirtyArray['null'] = null;
		$dirtyArray['false'] = false;
		$dirtyArray['empty_string'] = '';
		$dirtyArray['empty_array'] = array();
		$dirtyArray['array_with_empty_keys'] = array(
			'empty_key' => ''
		);
		$cleanedUpArray = $cleanupArrayReflection->invoke($httpRequest, $dirtyArray);
		$this->assertEquals($cleanArray, $cleanedUpArray, 'Unable to clean up array!');
	}

	/**
	 * @depends testCleanupArray
	 * @depends testSaveActualOnInit
	 */
	public function testCleanupGet() {
		$cleanArray = array(
			'some_scalar' => 'value',
		);
		$dirtyArray = $cleanArray;
		$dirtyArray['empty_string'] = '';
		$_GET = $dirtyArray;

		$httpRequest = $this->createTestHttpRequest();
		$httpRequest->cleanupGet();

		$this->assertEquals($cleanArray, $_GET, 'Unable to clean up $_GET!');
		$this->assertEquals($cleanArray, $httpRequest->getActualGet(), 'Unable to clean up actual get!');
	}
}
