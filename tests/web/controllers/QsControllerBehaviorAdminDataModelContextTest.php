<?php

/**
 * Test case for the extension "qs.web.controllers.QsControllerBehaviorAdminDataModelContext".
 * @see QsControllerBehaviorAdminDataModelContext
 */
class QsControllerBehaviorAdminDataModelContextTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.QsControllerBehaviorAdminDataModelContext');

		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		$testGroupTableName = self::getTestGroupTableName();
		$testMainTableName = self::getTestMainTableName();

		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testGroupTableName, $columns);
		$activeRecordGenerator->generate(array('tableName' => $testGroupTableName));

		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'group_id' => 'integer',
		);
		$dbSetUp->createTable($testMainTableName, $columns);
		$activeRecordGenerator->generate(array('tableName' => $testMainTableName));
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestGroupTableName());
		$dbSetUp->dropTable(self::getTestMainTableName());

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

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();

		$testGroupTableName = self::getTestGroupTableName();
		$testMainTableName = self::getTestMainTableName();

		$dbSetUp->truncateTable($testGroupTableName);
		$dbSetUp->truncateTable($testMainTableName);

		for ($groupId=1; $groupId<=3; $groupId++) {
			$columns = array(
				'name' => 'test_name',
			);
			$dbSetUp->insert($testGroupTableName, $columns);

			for ($i=1; $i<=4; $i++) {
				$columns = array(
					'name' => 'test_name',
					'group_id' => $groupId
				);
				$dbSetUp->insert($testMainTableName, $columns);
			}
		}
	}

	/**
	 * Returns the name of the test main table.
	 * @return string test table name.
	 */
	public static function getTestMainTableName() {
		return 'test_main_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test main active record class.
	 * @return string test active record class name.
	 */
	public static function getTestMainActiveRecordClassName() {
		return self::getTestMainTableName();
	}

	/**
	 * Returns the name of the test group table.
	 * @return string test table name.
	 */
	public static function getTestGroupTableName() {
		return 'test_group_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test group active record class.
	 * @return string test active record class name.
	 */
	public static function getTestGroupActiveRecordClassName() {
		return self::getTestGroupTableName();
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testInitialized = 'test_initialized';
		$this->assertTrue($behavior->setInitialized($testInitialized), 'Unable to set initialized!');
		$this->assertEquals($behavior->getInitialized(), $testInitialized, 'Unable to set initialized correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetUpContexts() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testContexts = array(
			'testContextName1' => array(
				'class' => 'TestClass1',
				'foreignKeyName' => 'test_foreign_key_name_1',
				'controllerId' => 'test_controller_id_1',
			),
			'testContextName2' => array(
				'class' => 'TestClass2',
				'foreignKeyName' => 'test_foreign_key_name_2',
				'controllerId' => 'test_controller_id_2',
			),
		);
		$this->assertTrue($behavior->setContexts($testContexts), 'Unable to set contexts!');
		$this->assertEquals($behavior->getContexts(), $testContexts, 'Unable to set contexts correctly!');

		$testContextNameAdd = 'test_context_name_add';
		$testContextConfigAdd = array(
			'class' => 'TestClassAdd',
			'foreignKeyName' => 'test_foreign_key_name_add',
			'controllerId' => 'test_controller_id',
		);
		$this->assertTrue($behavior->addContext($testContextNameAdd, $testContextConfigAdd), 'Unable to add context!');
		$this->assertEquals($behavior->getContext($testContextNameAdd), $testContextConfigAdd, 'Unable to add context correctly!');

		$defaultContext = $behavior->getContext();
		$this->assertTrue(is_array($defaultContext) && !empty($defaultContext), 'Unable to get default context!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetUpActiveContexts() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testActiveContexts = array(
			'testContextName1' => array(
				'class' => 'TestClass1',
				'foreignKeyName' => 'test_foreign_key_name_1',
				'controllerId' => 'test_controller_id_1',
			),
			'testContextName2' => array(
				'class' => 'TestClass2',
				'foreignKeyName' => 'test_foreign_key_name_2',
				'controllerId' => 'test_controller_id_2',
			),
		);
		$this->assertTrue($behavior->setActiveContexts($testActiveContexts), 'Unable to set active contexts!');
		$this->assertEquals($behavior->getActiveContexts(), $testActiveContexts, 'Unable to set active contexts correctly!');

		$testActiveContextNameAdd = 'test_context_name_add';
		$testActiveContextConfigAdd = array(
			'class' => 'TestClassAdd',
			'foreignKeyName' => 'test_foreign_key_name_add',
			'controllerId' => 'test_controller_id',
		);
		$this->assertTrue($behavior->addActiveContext($testActiveContextNameAdd, $testActiveContextConfigAdd), 'Unable to add active context!');
		$this->assertEquals($behavior->getActiveContext($testActiveContextNameAdd), $testActiveContextConfigAdd, 'Unable to add active context correctly!');

		$defaultActiveContext = $behavior->getActiveContext();
		$this->assertTrue(is_array($defaultActiveContext) && !empty($defaultActiveContext), 'Unable to get default active context!');
	}

	/**
	 * @depends testSetUpContexts
	 * @depends testSetUpActiveContexts
	 */
	public function testInitActiveContexts() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testModelClassName = self::getTestMainActiveRecordClassName();
		$behavior->setModelClassName($testModelClassName);

		$testContextName = 'test_context';
		$testForeignKeyName = 'group_id';
		$testContextConfig = array(
			'class' => self::getTestGroupActiveRecordClassName(),
			'foreignKeyName' => $testForeignKeyName,
		);
		$behavior->addContext($testContextName, $testContextConfig);

		// Existing context id:
		$testContextId = 2;
		$_GET[$testForeignKeyName] = $testContextId;

		$returnedActiveContexts = $behavior->getActiveContexts();

		$this->assertTrue(!empty($returnedActiveContexts), 'Unable to init contexts!');
		reset($returnedActiveContexts);
		$returnedActiveContext = current($returnedActiveContexts);
		$this->assertTrue(is_object($returnedActiveContext['model']), 'Unable to init context model!');

		$this->assertEquals($returnedActiveContext['model']->id, $testContextId, 'Wrong context model has been found!');

		// Unexisting context id:
		$behavior->setInitialized(false);

		$unexistingContextId = 99999;
		$_GET[$testForeignKeyName] = $unexistingContextId;

		$exceptionCaught = false;
		try {
			$returnedActiveContexts = $behavior->getActiveContexts();
		} catch (Exception $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'No exception has been raisen for unexisting context!');
	}

	/**
	 * @depends testSetGet
	 * @depends testSetUpContexts
	 * @depends testSetUpActiveContexts
	 */
	public function testGetActiveContextModelAttributes() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testModelClassName = self::getTestMainActiveRecordClassName();
		$behavior->setModelClassName($testModelClassName);

		$testContextName = 'test_context';
		$testForeignKeyName = 'group_id';
		$testContextConfig = array(
			'class' => self::getTestGroupActiveRecordClassName(),
			'foreignKeyName' => $testForeignKeyName,
		);
		$behavior->addContext($testContextName, $testContextConfig);

		// Existing context id:
		$testContextId = 2;
		$_GET[$testForeignKeyName] = $testContextId;

		$returnedActiveContexts = $behavior->getActiveContexts();

		$this->assertFalse(empty($returnedActiveContexts), 'Unable to init contexts!');

		$expectedAttributes = array();
		foreach ($returnedActiveContexts as $returnedActiveContext) {
			$expectedAttributes[$returnedActiveContext['foreignKeyName']] = $returnedActiveContext['model']->getPrimaryKey();
		}

		$returnedAttributes = $behavior->getActiveContextModelAttributes();
		$this->assertTrue(is_array($returnedAttributes) && !empty($returnedAttributes), 'Unable to get active context attributes!');
		$this->assertEquals($returnedAttributes, $expectedAttributes, 'Unable to get active context attributes correctly!');
	}

	/**
	 * @depends testGetActiveContextModelAttributes
	 */
	public function testLoadModel() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testModelClassName = self::getTestMainActiveRecordClassName();
		$behavior->setModelClassName($testModelClassName);

		$testContextName = 'test_context';
		$testForeignKeyName = 'group_id';
		$testContextConfig = array(
			'class' => self::getTestGroupActiveRecordClassName(),
			'foreignKeyName' => $testForeignKeyName,
		);
		$behavior->addContext($testContextName, $testContextConfig);

		$testContextId = 2;

		$modelFinder = call_user_func(array($testModelClassName, 'model'));
		$testModel = $modelFinder->findByAttributes(array($testForeignKeyName=>$testContextId));
		$this->assertTrue(is_object($testModel), 'Unable to find model for the test!');

		$_GET[$testForeignKeyName] = $testContextId;

		$loadedModel = $behavior->loadModel($testModel->id);
		$this->assertTrue(is_object($loadedModel), 'Unable to load model!');
	}

	/**
	 * @depends testGetActiveContextModelAttributes
	 */
	public function testNewModel() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testModelClassName = self::getTestMainActiveRecordClassName();
		$behavior->setModelClassName($testModelClassName);

		$testContextName = 'test_context';
		$testForeignKeyName = 'group_id';
		$testContextConfig = array(
			'class' => self::getTestGroupActiveRecordClassName(),
			'foreignKeyName' => $testForeignKeyName,
		);
		$behavior->addContext($testContextName, $testContextConfig);

		$testContextId = 2;
		$_GET[$testForeignKeyName] = $testContextId;

		$newModel = $behavior->newModel();
		$this->assertTrue(is_object($newModel), 'Unable to get new model!');

		$activeContext = $behavior->getActiveContext();
		$this->assertEquals($newModel->$testForeignKeyName, $activeContext['model']->id, 'New model has wrong context foreign key!');
	}

	/**
	 * @depends testGetActiveContextModelAttributes
	 */
	public function testNewSearchModel() {
		$behavior = new QsControllerBehaviorAdminDataModelContext();

		$testModelClassName = self::getTestMainActiveRecordClassName();
		$behavior->setModelClassName($testModelClassName);

		$testContextName = 'test_context';
		$testForeignKeyName = 'group_id';
		$testContextConfig = array(
			'class' => self::getTestGroupActiveRecordClassName(),
			'foreignKeyName' => $testForeignKeyName,
		);
		$behavior->addContext($testContextName, $testContextConfig);

		$testContextId = 2;
		$_GET[$testForeignKeyName] = $testContextId;

		$newModel = $behavior->newSearchModel();
		$this->assertTrue(is_object($newModel), 'Unable to get new model!');

		$activeContext = $behavior->getActiveContext();
		$this->assertEquals($newModel->$testForeignKeyName, $activeContext['model']->id, 'New model has wrong context foreign key!');
	}
}
