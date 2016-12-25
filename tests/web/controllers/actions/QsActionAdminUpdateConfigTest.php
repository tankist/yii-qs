<?php

Yii::import('qs.web.controllers.actions.*');
Yii::import('qs.web.controllers.*');
Yii::import('qs.config.*');

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminUpdateConfig".
 * @see QsActionAdminUpdateConfig
 */
class QsActionAdminUpdateConfigTest extends CTestCase {
	/**
	 * @var CHttpRequest request backup
	 */
	protected static $_requestBackup;

	public static function setUpBeforeClass() {
		self::$_requestBackup = Yii::app()->getRequest();
		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);

		Yii::app()->setComponent(self::getConfigManagerComponentId(), self::createTestConfigManager());
	}

	public static function tearDownAfterClass() {
		Yii::app()->setComponent('request', self::$_requestBackup);
	}

	public function tearDown() {
		$fileName = self::getTestStorageFileName();
		if (file_exists($fileName)) {
			unlink($fileName);
		}
	}

	/**
	 * @return string test file name
	 */
	protected function getTestStorageFileName() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . getmypid() . '.php';
	}

	/**
	 * @return string config manager component id.
	 */
	protected static function getConfigManagerComponentId() {
		return __CLASS__ . 'ConfigManager';
	}

	/**
	 * @return QsTestController test controller instance.
	 */
	protected function createMockController() {
		$mockController = new QsTestController();
		return $mockController;
	}

	/**
	 * Creates test action
	 * @param CController $controller controller instance.
	 * @return QsActionAdminUpdateConfig action instance.
	 */
	protected function createTestAction($controller) {
		$action = new QsActionAdminUpdateConfig($controller, 'test');
		$action->configManagerComponentId = self::getConfigManagerComponentId();
		return $action;
	}

	/**
	 * Creates test config manager.
	 * @return QsConfigManager config manager instance.
	 */
	protected static function createTestConfigManager() {
		$config = array(
			'class' => 'QsConfigManager',
			'storage' => array(
				'class' => 'QsConfigStorageFile',
				'fileName' => self::getTestStorageFileName(),
			),
			'items' => array(
				'appName' => array(
					'path' => 'name',
				),
				'basePath' => array(
					'path' => 'basePath'
				),
			),
		);
		$component = Yii::createComponent($config);
		return $component;
	}

	// Tests:

	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminUpdateConfig($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminUpdateSetting" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewForm() {
		$controller = $this->createMockController();
		$action = $this->createTestAction($controller);

		$pageRendered = false;
		try {
			$controller->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$pageRendered = true;
		}
		$this->assertTrue($pageRendered, 'Page has not been rendered!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitForm() {
		/* @var $configManager QsConfigManager */
		$controller = $this->createMockController();
		$action = $this->createTestAction($controller);

		$configManager = Yii::app()->getComponent(self::getConfigManagerComponentId());
		$models = $configManager->getItems();

		$postValues = array();
		$expectedValues = array();
		foreach ($models as $model) {
			$value = 'value_' . $model->id . '_' . rand(1, 100);
			$postValues[CHtml::modelName($model)][$model->id]['value'] = $value;
			$expectedValues[$model->id] = $value;
		}
		$_POST = $postValues;

		$pageRedirected = false;
		try {
			$controller->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected after form submit!');

		$configManager->restoreValues();
		$newItemValues = $configManager->getItemValues();
		$this->assertEquals($expectedValues, $newItemValues, 'Models have not been updated!');
	}
}