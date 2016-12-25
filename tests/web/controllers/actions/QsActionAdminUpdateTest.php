<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminUpdate".
 * @see QsActionAdminUpdate
 */
class QsActionAdminUpdateTest extends CTestCase {
	const TEST_RECORDS_COUNT = 5;
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
			'group_id' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'rules' => array(
					array('name,group_id', 'required')
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
				'name' => 'test_name',
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
		$action = new QsActionAdminUpdate($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminUpdate" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$controller = new CController('test');
		$action = new QsActionAdminUpdate($controller, 'test');

		$testViewName = 'test_view_name';
		$this->assertTrue($action->setView($testViewName), 'Unable to set view!');
		$this->assertEquals($action->getView(), $testViewName, 'Unable to set view correctly!');

		$testAjaxValidationEnabled = 'testAjaxValidationEnabled';
		$this->assertTrue($action->setAjaxValidationEnabled($testAjaxValidationEnabled), 'Unable to set ajaxValidationEnabled!');
		$this->assertEquals($action->getAjaxValidationEnabled(), $testAjaxValidationEnabled, 'Unable to set ajaxValidationEnabled correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdate($mockController, 'test');

		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$viewRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$viewRendered = true;
		}

		$this->assertTrue($viewRendered, 'View is not rendered!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testViewFormMissingModel() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdate($mockController, 'test');

		$testId = rand(self::TEST_RECORDS_COUNT+1, self::TEST_RECORDS_COUNT*2);
		$_GET['id'] = $testId;

		$errorMissingPageRisen = false;
		try {
			$mockController->runAction($action);
		} catch (CHttpException $exception) {
			$errorMissingPageRisen = true;
		}

		$this->assertTrue($errorMissingPageRisen, 'No 404 error, while updating unexisting model!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdate($mockController, 'test');

		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$testRecordName = 'test_record_name_'.rand(1,100);
		$testRecordGroupId = rand(1,25);

		$_POST[self::getTestActiveRecordClassName()] = array(
			'name' => $testRecordName,
			'group_id' => $testRecordGroupId,
		);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected!');

		$updatedModel = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertEquals($updatedModel->name, $testRecordName, 'Can not update record field "name"!');
		$this->assertEquals($updatedModel->group_id, $testRecordGroupId, 'Can not update record field "group_id"!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitFormWithError() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminUpdate($mockController, 'test');

		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$_POST[self::getTestActiveRecordClassName()] = array(
			'name' => null,
			'group_id' => null,
		);

		$pageRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$pageRendered = true;
		}
		$this->assertTrue($pageRendered, 'Page has not been rendered after request with empty post!');
	}
}
