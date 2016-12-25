<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminList".
 * @see QsActionAdminList
 */
class QsActionAdminListTest extends CTestCase {
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
	 * @return QsTestController test controller instance.
	 */
	public function createMockController() {
		$mockController = new QsTestController();

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setSearchModelClassName(self::getTestActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);

		return $mockController;
	}

	// Tests:
	
	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminList($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminList" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewList() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminList($mockController, 'test');

		$pageRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$pageRendered = true;
		}
		$this->assertTrue($pageRendered, 'Page has not been rendered!');
	}
}
