<?php

/**
 * Test case for the extension "qs.web.controllers.QsControllerBehaviorAdminDataModel".
 * @see QsControllerBehaviorAdminDataModel
 */
class QsControllerBehaviorAdminDataModelTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.QsControllerBehaviorAdminDataModel');

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
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());

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
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);
		for ($i=1; $i<=10; $i++) {
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

	// Tests:

	public function testSetGet() {
		$behavior = new QsControllerBehaviorAdminDataModel();

		$testModelClassName = 'testModelClassName';
		$this->assertTrue($behavior->setModelClassName($testModelClassName), 'Unable to set model class name!');
		$this->assertEquals($behavior->getModelClassName(), $testModelClassName, 'Unable to set model class name correctly!');

		$testSearchModelName = 'testSearchModelClassName';
		$this->assertTrue($behavior->setSearchModelClassName($testSearchModelName), 'Unable to set search model class name!');
		$this->assertEquals($testSearchModelName, $behavior->getSearchModelClassName(), 'Unable to set search model class name correctly!');

		$testModelSearchScenarioName = 'test_model_scenario_name';
		$this->assertTrue($behavior->setModelSearchScenarioName($testModelSearchScenarioName), 'Unable to set model scenario name!');
		$this->assertEquals($behavior->getModelSearchScenarioName(), $testModelSearchScenarioName, 'Unable to set model scenario name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetNotSetSearchModelName() {
		$behavior = new QsControllerBehaviorAdminDataModel();

		$testModelClassName = 'testModelClassName';
		$behavior->setModelClassName($testModelClassName);

		$searchModelClassName = $behavior->getSearchModelClassName();
		$this->assertEquals($testModelClassName, $searchModelClassName, 'Unable to get search model class name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testLoadModel() {
		$behavior = new QsControllerBehaviorAdminDataModel();
		$behavior->setModelClassName(self::getTestActiveRecordClassName());

		$testId = 7;
		$model = $behavior->loadModel($testId);
		$this->assertTrue(is_object($model), 'Unable to load model!');
		$this->assertEquals($model->id, $testId, 'Loaded model is invalid!');

		$testUnexistingId = 9999;
		$exceptionCaught = false;
		try {
			$model = $behavior->loadModel($testUnexistingId);
		} catch (CException $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'Unexisting model has been loaded!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testNewModel() {
		$behavior = new QsControllerBehaviorAdminDataModel();

		$testActiveRecordName = self::getTestActiveRecordClassName();
		$behavior->setModelClassName($testActiveRecordName);

		$newModel = $behavior->newModel();
		$this->assertTrue(is_object($newModel), 'Unable to get new model!');
		$this->assertEquals(get_class($newModel), $testActiveRecordName, 'New model has wrong type!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testNewSearchModel() {
		$behavior = new QsControllerBehaviorAdminDataModel();

		$testActiveRecordName = self::getTestActiveRecordClassName();
		$behavior->setSearchModelClassName($testActiveRecordName);

		$testModelSearchScenarioName = 'test_model_scenario_name';
		$behavior->setModelSearchScenarioName($testModelSearchScenarioName);

		$newModel = $behavior->newSearchModel();
		$this->assertTrue(is_object($newModel), 'Unable to get new model!');
		$this->assertEquals(get_class($newModel), $testActiveRecordName, 'New model has wrong type!');
		$this->assertEquals($newModel->getScenario(), $testModelSearchScenarioName, 'New model has wrong scenario!');
	}
}
