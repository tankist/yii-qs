<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminInsertRole".
 * @see QsActionAdminInsertRole
 */
class QsActionAdminInsertRoleTest extends CTestCase {
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
		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Slave:
		$testSlaveTableName = self::getTestSlaveTableName();
		$columns = array(
			'id' => 'pk',
			'master_id' => 'integer',
			'slave_name' => 'string',
		);
		$dbSetUp->createTable($testSlaveTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testSlaveTableName,
				'rules' => array(
					array('slave_name', 'required'),
				),
			)
		);

		// Master:
		$testMasterTableName = self::getTestMasterTableName();
		$columns = array(
			'id' => 'pk',
			'master_name' => 'string',
		);
		$dbSetUp->createTable($testMasterTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testMasterTableName,
				'rules' => array(
					array('master_name', 'required'),
				),
				'behaviors' => array(
					'roleBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorRole',
						'relationName' => 'slave',
						'relationConfig' => array(
							$testSlaveTableName, 'master_id'
						),
					),
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		Yii::app()->setComponent('request', self::$_requestBackup);

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestMasterTableName());
		$dbSetUp->dropTable(self::getTestSlaveTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->truncateTable(self::getTestMasterTableName());
		$dbSetUp->truncateTable(self::getTestSlaveTableName());
	}

	/**
	 * Returns the name of the master test table.
	 * @return string test table name.
	 */
	public static function getTestMasterTableName() {
		return 'test_master_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the master test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestMasterActiveRecordClassName() {
		return self::getTestMasterTableName();
	}

	/**
	 * Returns the name of the slave test table.
	 * @return string test table name.
	 */
	public static function getTestSlaveTableName() {
		return 'test_slave_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the slave test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestSlaveActiveRecordClassName() {
		return self::getTestSlaveTableName();
	}

	/**
	 * @return QsTestController test controller instance.
	 */
	public function createMockController() {
		$mockController = new QsTestController();

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setModelClassName(self::getTestMasterActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);

		return $mockController;
	}

	// Tests:

	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminInsertRole($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminInsertRole" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertRole($mockController, 'test');

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
	public function testSubmitForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertRole($mockController, 'test');

		$testMasterRecordName = 'test_master_record_name_'.rand(1,100);
		$testSlaveRecordName = 'test_slave_record_name_'.rand(1,100);

		$modelClassName = self::getTestMasterActiveRecordClassName();
		$model = CActiveRecord::model(self::getTestMasterActiveRecordClassName());
		$subModelClassName = $model->getRelationConfigParam('class');

		$_POST[$modelClassName] = array(
			'master_name' => $testMasterRecordName,
		);
		$_POST[$subModelClassName] = array(
			'slave_name' => $testSlaveRecordName,
		);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue( $pageRedirected, 'Page has not been redirected!' );

		$insertedModel = CActiveRecord::model(self::getTestMasterActiveRecordClassName())->findByAttributes(array('master_name'=>$testMasterRecordName));
		$this->assertTrue(is_object($insertedModel), 'Can not find inserted record!');

		$this->assertEquals($insertedModel->slave->slave_name, $testSlaveRecordName, 'Slave record has wrong data!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitFormWithError() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertRole($mockController, 'test');

		$_POST[self::getTestMasterActiveRecordClassName()] = array(
			'name' => null,
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
