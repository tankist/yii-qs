<?php

Yii::import('qs.config.QsConfigItem', true);

/**
 * Test case for the extension "qs.config.QsConfigItem".
 * @see QsConfigItem
 */
class QsConfigItemTest extends CTestCase {
	/**
	 * @var CApplication current application backup
	 */
	protected static $applicationBackup;

	public static function setUpBeforeClass() {
		self::$applicationBackup = Yii::app();
		Yii::setApplication(null);
		self::createTestApplication();
	}

	public static function tearDownAfterClass() {
		Yii::setApplication(null);
		Yii::setApplication(self::$applicationBackup);
	}

	/**
	 * @return CApplication test application instance.
	 */
	protected static function createTestApplication() {
		$config = array(
			'name' => 'Test Application Name',
			'basePath' => self::$applicationBackup->basePath,
			'components' => array(
				'securityManager' => array(
					'validationKey' => 'testValidationKey',
					'encryptionKey' => 'testEncryptionKey'
				),
			),
			'params' => array(
				'param1' => 'param1value',
				'param2' => 'param2value',
			)
		);
		return Yii::createApplication('CWebApplication', $config);
	}

	// Tests :

	public function testSetGet() {
		$model = new QsConfigItem();

		$value = 'testValue';
		$model->setValue($value);
		$this->assertEquals($value, $model->getValue(), 'Unable to setup value!');

		$rules = array(
			'required'
		);
		$model->setRules($rules);
		$this->assertEquals($rules, $model->getRules(), 'Unable to setup rules!');
	}

	public function testLabel() {
		$model = new QsConfigItem();

		$label = 'TestPlaceholderLabel';
		$model->label = $label;

		$this->assertEquals($label, $model->getAttributeLabel('value'), 'Wrong value label!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetupRules() {
		$model = new QsConfigItem();

		$validationRules = array(
			array('required'),
		);
		$model->setRules($validationRules);
		$validatorList = $model->getValidatorList();

		$this->assertEquals(count($validationRules)+1, $validatorList->getCount(), 'Unable to set validation rules!');

		$validator = $validatorList->itemAt(1);
		$this->assertTrue($validator instanceof CRequiredValidator, 'Wrong validator created!');
	}

	/**
	 * Data provider for {@link testExtractCurrentValue}
	 * @return array test data.
	 */
	public function dataProviderExtractCurrentValue() {
		return array(
			array(
				'name',
				'Test Application Name',
			),
			array(
				'params.param1',
				'param1value',
			),
			array(
				array('params', 'param1'),
				'param1value',
			),
			array(
				'components.securityManager.validationKey',
				'testValidationKey',
			),
		);
	}

	/**
	 * @dataProvider dataProviderExtractCurrentValue
	 *
	 * @param $path
	 * @param $expectedValue
	 */
	public function testExtractCurrentValue($path, $expectedValue) {
		$model = new QsConfigItem();
		$model->path = $path;
		$this->assertEquals($expectedValue, $model->extractCurrentValue());
	}

	/**
	 * @depends testExtractCurrentValue
	 */
	public function testGetDefaultValue() {
		$model = new QsConfigItem();
		$model->path = 'params.param1';
		$defaultValue = $model->getValue();
		$this->assertEquals('param1value', $defaultValue, 'Wrong default value!');
	}

	/**
	 * Data provider for {@link testComposeConfig}.
	 * @return array test data.
	 */
	public function dataProviderComposeConfig() {
		return array(
			array(
				'name',
				array(
					'name' => 'value'
				),
			),
			array(
				'params.param1',
				array(
					'params' => array(
						'param1' => 'value'
					),
				)
			),
			array(
				'components.securityManager.validationKey',
				array(
					'components' => array(
						'securityManager' => array(
							'validationKey' => 'value'
						),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider dataProviderComposeConfig
	 *
	 * @param $path
	 * @param array $expectedConfig
	 */
	public function testComposeConfig($path, array $expectedConfig) {
		$model = new QsConfigItem();
		$model->path = $path;
		$model->value = 'value';
		$this->assertEquals($expectedConfig, $model->composeConfig());
	}
}