<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminUpdateSetting".
 * @see QsActionAdminUpdateSetting
 */
class QsActionAdminUpdateSettingTest extends CTestCase {
	const TEST_RECORDS_COUNT = 10;
	protected static $_requestBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.actions.*');
		Yii::import('qs.web.controllers.*');

		// Components:
		self::$_requestBackup = Yii::app()->getRequest();
		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);

		// Database:
		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'value' => 'text',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'rules' => array(
					array('value', 'safe')
				),
				'behaviors' => array(
					'settingBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorNameValue',
						'autoNamePrefix' => 'test_'
					),
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		Yii::app()->setComponent('request', self::$_requestBackup);

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);
		for ($i=1; $i<=self::TEST_RECORDS_COUNT; $i++) {
			$columns = array(
				'name' => 'test_name_'.$i,
				'value' => 'test_value_'.$i,
			);
			$dbSetUp->insert($testTableName, $columns);
		}
	}

	/**
	 * Returns the name of the test table.
	 * @return string test table name.
	 */
	public static function getTestTableName() {
		return 'test_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestActiveRecordClassName() {
		return self::getTestTableName();
	}

	/**
	 * @return QsTestController test controller instance.
	 */
	public function createMockController() {
		$mockController = new QsTestController();

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setModelClassName(self::getTestActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);

		return $mockController;
	}

	// Tests:
	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminUpdateSetting($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminUpdateSetting" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdateSetting($mockController, 'test');

		$pageRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$pageRendered = true;
		}
		$this->assertTrue($pageRendered, 'Page has not been rendered!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdateSetting($mockController, 'test');

		$postValues = array();
		$beforeSaveModels = CActiveRecord::model(self::getTestActiveRecordClassName())->findAll();
		foreach ($beforeSaveModels as $model) {
			$postValues[$model->getPrimaryKey()]['value'] = 'value_'.$model->getPrimaryKey().'_'.rand(1,100);
		}
		$_POST[self::getTestActiveRecordClassName()] = $postValues;

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected after form submit!');

		$afterSaveModels = CActiveRecord::model(self::getTestActiveRecordClassName())->findAll();
		$newModelValues = array();
		foreach ($afterSaveModels as $model) {
			$newModelValues[$model->getPrimaryKey()]['value'] = $model->value;
		}

		$this->assertEquals($newModelValues, $postValues, 'Models have not been updated!');
	}

}
