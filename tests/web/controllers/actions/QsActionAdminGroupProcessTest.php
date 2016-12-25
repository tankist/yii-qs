<?php
 
/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminGroupProcess".
 * @see QsActionAdminGroupProcess
 */
class QsActionAdminGroupProcessTest extends CTestCase {
	const TEST_RECORDS_COUNT = 10;
	/**
	 * @var CComponent http request component backup.
	 */
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
		$activeRecordGenerator->generate(array('tableName' => $testTableName));
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
	 * Creates test controller instance.
	 * @param string $groupProcessName defines the name of the group process method.
	 * @return QsTestController test controller instance.
	 */
	protected function createMockController($groupProcessName=null) {
		if (!$groupProcessName) {
			$mockController = new QsTestController();

		} else {
			$classDefinitionCode = <<<EOD
class QsTestController{$groupProcessName} extends QsTestController {
	public function groupProcess{$groupProcessName}(array \$models) {
		throw new Exception(__FUNCTION__);
	}
}
return new QsTestController{$groupProcessName}();
EOD;
			$mockController = eval($classDefinitionCode);
		}

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setModelClassName(self::getTestActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);
		return $mockController;
	}

	// Tests:

	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminGroupProcess($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminGroupProcess" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$controller = new CController('test');
		$action = new QsActionAdminGroupProcess($controller, 'test');

		$testModelClassName = 'TestModelClassName';
		$this->assertTrue($action->setModelClassName($testModelClassName), 'Unable to set model class name!');
		$this->assertEquals($testModelClassName, $action->getModelClassName(), 'Unable to set model class name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineDefaultModelClassName() {
		$controller = $this->createMockController('test');
		$action = new QsActionAdminGroupProcess($controller, 'test');

		$defaultModelClassName = $action->getModelClassName();
		$this->assertFalse(empty($defaultModelClassName), 'Unable to determine default model class name!');
		$this->assertEquals($controller->getModelClassName(), $defaultModelClassName, 'Default model class name does not corresponds controller model class name!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGroupProcessByControllerMethod() {
		$testGroupProcessName = 'testGroupProcess';
		$mockController = $this->createMockController($testGroupProcessName);
		$action = new QsActionAdminGroupProcess($mockController, 'test');

		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$testIds = array($testId);

		$_REQUEST[$action->groupProcessNameParamName] = $testGroupProcessName;
		$_REQUEST[$action->modelKeysParamName] = $testIds;

		$processMethodName = null;
		try {
			$mockController->runAction($action);
		} catch (Exception $exception) {
			$processMethodName = $exception->getMessage();
		}
		$this->assertFalse(empty($processMethodName), 'Controller group process method has not been invoked!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGroupProcessByModelMethod() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminGroupProcess($mockController, 'test');

		$testModelMethodName = 'delete';
		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$testIds = array($testId);

		$action->allowedModelMethods = $testModelMethodName;

		$_REQUEST[$action->groupProcessNameParamName] = $testModelMethodName;
		$_REQUEST[$action->modelKeysParamName] = $testIds;

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected!');

		$returnedModel = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertFalse(is_object($returnedModel), 'Unable to perform model method by group process!');
	}

	/**
	 * @depends testGroupProcessByModelMethod
	 */
	public function testTestDenyGroupProcessByModelMethod() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminGroupProcess($mockController, 'test');

		$testModelMethodName = 'delete';
		$testId = rand(1, self::TEST_RECORDS_COUNT);
		$testIds = array($testId);

		$action->allowedModelMethods = array();

		$_REQUEST[$action->groupProcessNameParamName] = $testModelMethodName;
		$_REQUEST[$action->modelKeysParamName] = $testIds;

		$this->setExpectedException('CHttpException');
		$mockController->runAction($action);
	}
}
