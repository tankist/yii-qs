<?php

/**
 * Test case for the extension "qs.application.QsApplicationBehaviorInitFromParam".
 * @see QsApplicationBehaviorInitFromParam
 */
class QsApplicationBehaviorInitFromParamTest extends CTestCase {
	protected static $_applicationParamsBackup = null;
	protected static $_applicationPropertyBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.application.QsApplicationBehaviorInitFromParam');

		self::$_applicationParamsBackup = clone Yii::app()->params;

		$applicationPropertyBackup = array();
		$applicationReflection = new ReflectionObject( Yii::app() );
		$applicationProperties = $applicationReflection->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($applicationProperties as $applicationProperty) {
			$applicationPropertyBackup[$applicationProperty->getName()] = $applicationProperty->getValue(Yii::app());
		}
		self::$_applicationPropertyBackup = $applicationPropertyBackup;
	}

	public static function tearDownAfterClass() {
		Yii::app()->params = self::$_applicationParamsBackup;

		foreach (self::$_applicationPropertyBackup as $propertyName => $propertyValue) {
			Yii::app()->$propertyName = $propertyValue;
		}

		// garbage collection:
		if (function_exists('gc_enabled')) {
			if (gc_enabled()) {
				gc_collect_cycles();
			} else {
				gc_enable();
				gc_collect_cycles();
				gc_disable();
			}
		}
	}

	// Tests:
	
	public function testCreate() {
		$behavior = new QsApplicationBehaviorInitFromParam();
		$this->assertTrue(is_object($behavior), 'Unable to create "QsApplicationBehaviorInitFromParam" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsApplicationBehaviorInitFromParam();

		$testPropertyParamNames = array(
			'test_property_name_1' => 'test_param_name_1',
			'test_property_name_2' => 'test_param_name_2',
		);
		$this->assertTrue($behavior->setPropertyParamNames($testPropertyParamNames), 'Unable to set property param names!');
		$this->assertEquals($behavior->getPropertyParamNames(), $testPropertyParamNames, 'Unable to set property param names correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testUpdateApplicationPropertysFromParams() {
		$behavior = new QsApplicationBehaviorInitFromParam();

		$testApplicationName = 'Test Application Name';
		$testApplicationNameParamName = 'test_app_name_param_name';
		Yii::app()->params[$testApplicationNameParamName] = $testApplicationName;

		$testApplicationCharset = 'Test Application Charset';
		$testApplicationCharsetParamName = 'test_app_charset_param_name';
		Yii::app()->params[$testApplicationCharsetParamName] = $testApplicationCharset;

		$testPropertyParamNames = array(
			'name' => $testApplicationNameParamName,
			'charset' => $testApplicationCharsetParamName,
		);
		$behavior->setPropertyParamNames($testPropertyParamNames);

		$testEvent = new CEvent(Yii::app());
		$behavior->beginRequest($testEvent);

		$this->assertEquals(Yii::app()->name, $testApplicationName, 'Unable to set up application name from params!');
		$this->assertEquals(Yii::app()->charset, $testApplicationCharset, 'Unable to set up application charset from params!');
	}
}
