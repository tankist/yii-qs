<?php

/**
 * Test case for the extension "qs.application.QsApplicationBehaviorParamDb".
 * @see QsApplicationBehaviorParamDb
 */
class QsApplicationBehaviorParamDbTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.application.QsApplicationBehaviorParamDb');
		Yii::import('qs.db.ar.*');

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
				'name' => 'name_'.$i,
				'value' => 'value_'.$i,
			);
			$dbSetUp->insert($testTableName, $columns);
		}
	}

	public function tearDown() {
		$activeRecord = CActiveRecord::model(self::getTestActiveRecordClassName());
		$activeRecord->clearValuesCache();
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

	public function testCreate() {
		$behavior = new QsApplicationBehaviorParamDb();
		$this->assertTrue(is_object($behavior), 'Unable to create "QsApplicationBehaviorParamDb" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsApplicationBehaviorParamDb();

		$testParamModelClassName = 'TestParamModelClassName';
		$this->assertTrue($behavior->setParamModelClassName($testParamModelClassName), 'Unable to set param model class name!');
		$this->assertEquals($behavior->getParamModelClassName(), $testParamModelClassName, 'Unable to set param model class name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testUpdateApplicationParams() {
		$behavior = new QsApplicationBehaviorParamDb();

		$testParamModelName = self::getTestActiveRecordClassName();
		$behavior->setParamModelClassName($testParamModelName);

		$testEvent = new CEvent(Yii::app());
		$behavior->beginRequest($testEvent);

		$paramModelValues = CActiveRecord::model($testParamModelName)->getValues();

		foreach ($paramModelValues as $paramName => $paramValue) {
			$this->assertEquals(Yii::app()->params[$paramName], $paramValue, 'CApplicationParams does not contain value from the param model!');
		}
	}

	/**
	 * @depends testUpdateApplicationParams
	 */
	public function testUpdateApplicationParamsFromRegularModel() {
		$behavior = new QsApplicationBehaviorParamDb();

		$testParamModelName = 'TestSettingSimple';

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'className' => $testParamModelName,
				'tableName' => self::getTestTableName(),
			)
		);

		$behavior->setParamModelClassName($testParamModelName);

		$testEvent = new CEvent(Yii::app());
		$behavior->beginRequest($testEvent);

		$paramModels = CActiveRecord::model($testParamModelName)->findAll();

		foreach ($paramModels as $paramModel) {
			$this->assertEquals(Yii::app()->params[$paramModel->name], $paramModel->value, 'CApplicationParams does not contain value from the regular model!');
		}
	}
}
