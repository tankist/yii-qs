<?php
 
/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorDynamicColumn".
 * @see QsActiveRecordBehaviorDynamicColumn
 */
class QsActiveRecordBehaviorDynamicColumnTest extends CTestCase {
	const TEST_COLUMN_RECORDS_COUNT = 3;

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Column:
		$testColumnTableName = self::getTestColumnTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'default_value' => 'string',
		);
		$dbSetUp->createTable($testColumnTableName, $columns);

		for ($i=1; $i<=self::TEST_COLUMN_RECORDS_COUNT; $i++) {
			$data = array(
				'name' => 'column_'.$i,
				'default_value' => 'default_value_'.$i
			);
			$dbSetUp->insert($testColumnTableName, $data);
		}

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testColumnTableName,
				'rules' => array(
					array('name', 'required'),
					array('default_value', 'safe'),
				),
			)
		);

		// Column Values:
		$testColumnValueTableName = self::getTestColumnValueTableName();
		$columns = array(
			'id' => 'pk',
			'main_id' => 'integer',
			'column_id' => 'integer',
			'value' => 'string',
		);
		$dbSetUp->createTable($testColumnValueTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testColumnValueTableName,
				'rules' => array(
					array('main_id,column_id,value', 'safe'),
					array('main_id,column_id', 'numerical', 'integerOnly'=>true)
				),
			)
		);

		// Main:
		$testMainTableName = self::getTestMainTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testMainTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testMainTableName,
				'rules' => array(
					array('name', 'required'),
				),
				'behaviors' => array(
					'dynamicColumnBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorDynamicColumn',
						'columnModelClassName' => $testColumnTableName,
						'relationConfig' => array(
							$testColumnValueTableName, 'main_id'
						),
					)
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestColumnTableName());
		$dbSetUp->dropTable(self::getTestMainTableName());
		$dbSetUp->dropTable(self::getTestColumnValueTableName());

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
		$testMainTableName = self::getTestMainTableName();
		$testColumnValueTableName = self::getTestColumnValueTableName();

		$dbSetUp->truncateTable($testMainTableName);
		$dbSetUp->truncateTable($testColumnValueTableName);

		// insert:
		for ($mainId=1; $mainId<=5; $mainId++) {
			$data = array(
				'name' => 'main name '.$mainId
			);
			$dbSetUp->insert($testMainTableName, $data);

			for ($columnId=1; $columnId<=self::TEST_COLUMN_RECORDS_COUNT; $columnId++) {
				$data = array(
					'main_id' => $mainId,
					'column_id' => $columnId,
					'value' => 'column value '.$mainId.'/'.$columnId
				);
				$dbSetUp->insert($testColumnValueTableName, $data);
			}
		}
	}

	/**
	 * Returns the name of the column test table.
	 * @return string test table name.
	 */
	public static function getTestColumnTableName() {
		return 'test_column_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the column test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestColumnActiveRecordClassName() {
		return self::getTestColumnTableName();
	}

	/**
	 * Returns the name of the main test table.
	 * @return string test table name.
	 */
	public static function getTestMainTableName() {
		return 'test_main_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the main test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestMainActiveRecordClassName() {
		return self::getTestMainTableName();
	}

	/**
	 * Returns the name of the column value test table.
	 * @return string test table name.
	 */
	public static function getTestColumnValueTableName() {
		return 'test_variation_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the column value test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestColumnValueActiveRecordClassName() {
		return self::getTestColumnValueTableName();
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();

		$testIsInitialized = 'test_is_initialized';
		$this->assertTrue($behavior->setIsInitialized($testIsInitialized), 'Unable to set is initialized!');
		$this->assertEquals($testIsInitialized, $behavior->getIsInitialized(), 'Unable to set is initialized correctly!');

		$testColumnModels = array(
			'test_column_1' => new CFormModel(),
			'test_column_2' => new CFormModel(),
		);
		$this->assertTrue($behavior->setColumnModels($testColumnModels), 'Unable to set column models!');
		$this->assertEquals($testColumnModels, $behavior->getColumnModels(), 'Unable to set column models correctly!');

		$testColumnModelClassName = 'TestColumnModelClassName';
		$this->assertTrue($behavior->setColumnModelClassName($testColumnModelClassName), 'Unable to set column model class name!');
		$this->assertEquals($testColumnModelClassName, $behavior->getColumnModelClassName(), 'Unable to set column model class name correctly!');

		$testColumnModelSearchCriteria = array(
			'condition' => 'id = 5'
		);
		$this->assertTrue($behavior->setColumnModelSearchCriteria($testColumnModelSearchCriteria), 'Unable to set column model search criteria!');
		$this->assertEquals($testColumnModelSearchCriteria, $behavior->getColumnModelSearchCriteria(), 'Unable to set column model search criteria correctly!');

		$testColumnModelSearchCriteriaCallback = 'testColumnModelSearchCriteriaCallback';
		$this->assertTrue($behavior->setColumnModelSearchCriteriaCallback($testColumnModelSearchCriteriaCallback), 'Unable to set column model search criteria callback!');
		$this->assertEquals($testColumnModelSearchCriteriaCallback, $behavior->getColumnModelSearchCriteriaCallback(), 'Unable to set column model search criteria callback correctly!');

		$testColumnValueRelationName = 'testColumnValueRelationName';
		$this->assertTrue($behavior->setColumnValueRelationName($testColumnValueRelationName), 'Unable to set column value relation name!');
		$this->assertEquals($testColumnValueRelationName, $behavior->getColumnValueRelationName(), 'Unable to set column value relation name correctly!');

		$testRelationConfig = array(
			'testArg1',
			'testArg2'
		);
		$this->assertTrue($behavior->setRelationConfig($testRelationConfig), 'Unable to set relation config!');
		$this->assertEquals($testRelationConfig, $behavior->getRelationConfig(), 'Unable to set relation config correctly!');

		$testColumnValueModels = array(
			'column_1' => new CFormModel(),
			'column_2' => new CFormModel(),
		);
		$this->assertTrue($behavior->setColumnValueModels($testColumnValueModels), 'Unable to set column value models!');
		$this->assertEquals($testColumnValueModels, $behavior->getColumnValueModels(), 'Unable to set column value models correctly!');

		$testAutoAdjustColumnValueScenarios = array(
			'test_scenario_1',
			'test_scenario_2',
		);
		$this->assertTrue($behavior->setAutoAdjustColumnValueScenarios($testAutoAdjustColumnValueScenarios), 'Unable to set auto adjust column value scenarios!');
		$this->assertEquals($testAutoAdjustColumnValueScenarios, $behavior->getAutoAdjustColumnValueScenarios(), 'Unable to set auto adjust column value scenarios correctly!');

		// Attribute Names:
		$testColumnValueColumnForeignKeyName = 'test_column_foreign_key';
		$this->assertTrue($behavior->setColumnValueColumnForeignKeyName($testColumnValueColumnForeignKeyName), 'Unable to set column value column foreign key name!');
		$this->assertEquals($testColumnValueColumnForeignKeyName, $behavior->getColumnValueColumnForeignKeyName(), 'Unable to set column value column foreign key name correctly!');

		$testColumnNameAttributeName = 'test_column_name_attribute';
		$this->assertTrue($behavior->setColumnNameAttributeName($testColumnNameAttributeName), 'Unable to set column name attribute name!');
		$this->assertEquals($testColumnNameAttributeName, $behavior->getColumnNameAttributeName(), 'Unable to set column name attribute name correctly!');

		$testColumnValueAttributeName = 'test_column_value_attribute';
		$this->assertTrue($behavior->setColumnValueAttributeName($testColumnValueAttributeName), 'Unable to set column value attribute name!');
		$this->assertEquals($testColumnValueAttributeName, $behavior->getColumnValueAttributeName(), 'Unable to set column value attribute name correctly!');

		$testColumnDefaultValueAttributeName = 'test_column_default_value_attribute';
		$this->assertTrue($behavior->setColumnDefaultValueAttributeName($testColumnDefaultValueAttributeName), 'Unable to set column default value attribute name!');
		$this->assertEquals($testColumnDefaultValueAttributeName, $behavior->getColumnDefaultValueAttributeName(), 'Unable to set column default value attribute name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultColumnModels() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();
		$behavior->setColumnModelClassName(self::getTestColumnActiveRecordClassName());

		$defaultColumnModels = $behavior->getColumnModels();
		$this->assertFalse(empty($defaultColumnModels), 'Unable to get default column models!');

		$expectedColumnModels = array();
		$rawExpectedColumnModels = CActiveRecord::model(self::getTestColumnActiveRecordClassName())->findAll();
		foreach ($rawExpectedColumnModels as $rawExpectedColumnModel) {
			$expectedColumnModels[$rawExpectedColumnModel->name] = $rawExpectedColumnModel;
		}
		$this->assertEquals($expectedColumnModels, $defaultColumnModels, 'Unable to get default column models correctly!');
	}

	/**
	 * @depends testGetDefaultColumnModels
	 */
	public function testGetColumnModelByName() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();
		$behavior->setColumnModelClassName(self::getTestColumnActiveRecordClassName());

		$testColumnModelId = rand(1, self::TEST_COLUMN_RECORDS_COUNT);
		$testColumnModel = CActiveRecord::model(self::getTestColumnActiveRecordClassName())->findByPk($testColumnModelId);

		$returnedColumnModel = $behavior->getColumnModel($testColumnModel->name);
		$this->assertTrue(is_object($returnedColumnModel), 'Unable to get column model by name!');
		$this->assertEquals($testColumnModel, $returnedColumnModel, 'Unable to get correct column model by name!');
	}

	/**
	 * @depends testGetDefaultColumnModels
	 */
	public function testGetDefaultColumnModelsByCriteria() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();
		$behavior->setColumnModelClassName(self::getTestColumnActiveRecordClassName());

		$testColumnModelId = rand(1, self::TEST_COLUMN_RECORDS_COUNT);
		$testColumnModelSearchCriteria = array(
			'condition' => 'id = :id',
			'params' => array(
				'id' => $testColumnModelId
			),
		);
		$behavior->setColumnModelSearchCriteria($testColumnModelSearchCriteria);

		$defaultColumnModels = $behavior->getColumnModels();
		$this->assertTrue(count($defaultColumnModels)==1, 'Unable to get default column models with criteria!');

		$defaultColumnModel = array_shift($defaultColumnModels);
		$this->assertEquals($testColumnModelId, $defaultColumnModel->id, 'Unable to get default column models with criteria correctly!');
	}

	/**
	 * @depends testGetDefaultColumnModelsByCriteria
	 */
	public function testGetDefaultColumnModelsByCriteriaCallback() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();
		$behavior->setColumnModelClassName(self::getTestColumnActiveRecordClassName());

		$testColumnModelId = rand(1, self::TEST_COLUMN_RECORDS_COUNT);

		$testCallbackClassName = 'TestColumnModelsByCriteriaCallbackClass';
		$testCallbackFunctionName = 'testColumnModelsByCriteriaCallback';
		$callbackCode = <<<EOD
class {$testCallbackClassName} {
	public static function {$testCallbackFunctionName}() {
		return array(
			'condition' => 'id = :id',
			'params' => array(
				'id' => '{$testColumnModelId}'
			),
		);
	}
}
EOD;
		eval($callbackCode);

		$testCallback = array($testCallbackClassName, $testCallbackFunctionName);
		$behavior->setColumnModelSearchCriteriaCallback($testCallback);

		$defaultColumnModels = $behavior->getColumnModels();
		$this->assertTrue(count($defaultColumnModels)==1, 'Unable to get default column models with criteria!');

		$defaultColumnModel = array_shift($defaultColumnModels);
		$this->assertEquals($testColumnModelId, $defaultColumnModel->id, 'Unable to get default column models with criteria correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetRelationConfigParam() {
		$behavior = new QsActiveRecordBehaviorDynamicColumn();

		$testClassName = 'TestClassName';
		$testForeignKey = 'test_foreign_key';
		$testRelationConfig = array(
			$testClassName,
			$testForeignKey
		);
		$behavior->setRelationConfig($testRelationConfig);

		$returnedRelationClass = $behavior->getRelationConfigParam('class');
		$this->assertEquals($testClassName, $returnedRelationClass, 'Unable to get relation config param "class"!');

		$returnedForeignKey = $behavior->getRelationConfigParam('foreignKey');
		$this->assertEquals($testForeignKey, $returnedForeignKey, 'Unable to get relation config param "foreignKey"!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetColumnValueModelsNewRecord() {
		$activeRecordClassName = self::getTestMainActiveRecordClassName();
		$model = new $activeRecordClassName();

		$columnValueModels =  $model->getColumnValueModels();
		$this->assertTrue(is_array($columnValueModels), 'Unable to get column value models from new record!');

		$columnsCount = CActiveRecord::model(self::getTestColumnTableName())->count();
		$this->assertEquals($columnsCount, count($columnValueModels), 'Wrong count of column value models for the new record!');
	}

	/**
	 * @depends testGetColumnValueModelsNewRecord
	 */
	public function testAdjustColumnValueModelsMissing() {
		$activeRecordFinder = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		$columnModelClassName = $activeRecordFinder->getColumnModelClassName();
		$columnModelFinder = CActiveRecord::model($columnModelClassName);

		$columnModels = $columnModelFinder->findAll();
		list($someColumnModel) = $columnModels;

		$dbSetUp = new QsTestDbMigration();
		$data = array(
			'name' => 'test main name for adjust'
		);
		$dbSetUp->insert(self::getTestMainTableName(), $data);
		$mainId = Yii::app()->db->getLastInsertID();
		$columnId = $someColumnModel->getPrimaryKey();
		$data = array(
			'main_id' => $mainId,
			'column_id' => $columnId,
			'value' => 'adjust value '.$mainId.'/'.$columnId
		);
		$dbSetUp->insert(self::getTestColumnValueTableName(), $data);

		$foundActiveRecord = $activeRecordFinder->findByPk($mainId);
		$columnValueModels = $foundActiveRecord->getColumnValueModels();
		$this->assertTrue(count($columnValueModels) == count($columnModels), 'Count of column value models missmatch the count of columns!');
	}

	/**
	 * @depends testGetColumnValueModelsNewRecord
	 */
	public function testAdjustColumnValueModelsExtra() {
		$activeRecordFinder = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		$columnModelClassName = $activeRecordFinder->getColumnModelClassName();
		$columnModelFinder = CActiveRecord::model($columnModelClassName);

		$columnModels = $columnModelFinder->findAll();

		$dbSetUp = new QsTestDbMigration();
		$data = array(
			'name' => 'test main name for adjust'
		);
		$dbSetUp->insert(self::getTestMainTableName(), $data);
		$mainId = Yii::app()->db->getLastInsertID();
		foreach ($columnModels as $columnModel) {
			$columnId = $columnModel->getPrimaryKey();
			$data = array(
				'main_id' => $mainId,
				'column_id' => $columnId,
				'value' => 'adjust column value '.$mainId.'/'.$columnId
			);
			$dbSetUp->insert(self::getTestColumnValueTableName(), $data);
		}
		$extraColumnId = rand(self::TEST_COLUMN_RECORDS_COUNT+100, 500);
		$data = array(
			'main_id' => $mainId,
			'column_id' => $extraColumnId,
			'value' => 'adjust column value '.$mainId.'/'.$extraColumnId
		);
		$dbSetUp->insert(self::getTestColumnValueTableName(), $data);

		$foundActiveRecord = $activeRecordFinder->findByPk($mainId);
		$columnValueModels = $foundActiveRecord->getColumnValueModels();
		$this->assertTrue(count($columnValueModels) == count($columnModels), 'Count of column value models missmatch the count of columns!');
	}

	/**
	 * @depends testGetColumnValueModelsNewRecord
	 */
	public function testGetColumnValueModelByName() {
		$activeRecordClassName = self::getTestMainActiveRecordClassName();
		$model = new $activeRecordClassName();

		$testColumnModelId = rand(1, self::TEST_COLUMN_RECORDS_COUNT);
		$testColumnModel = CActiveRecord::model(self::getTestColumnActiveRecordClassName())->findByPk($testColumnModelId);

		$returnedColumnValueModel = $model->getColumnValueModel($testColumnModel->name);
		$this->assertTrue(is_object($returnedColumnValueModel), 'Unable to get column value model by name!');
		$this->assertEquals($testColumnModel->id, $returnedColumnValueModel->column_id, 'Unable to get correct column value model by name!');
	}

	/**
	 * @depends testGetColumnValueModelsNewRecord
	 */
	public function testActiveRecordColumnValueValidate() {
		$activeRecordFinder = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		$activeRecord = $activeRecordFinder->find();

		$this->assertTrue($activeRecord->validate(), 'Just found model fails on validate!');

		$columnValueModel = $activeRecord->columnValues[0];
		$columnValueModel->column_id = 'fake_value';
		$this->assertFalse($activeRecord->validate(), 'Model considered as valid, while one of column value models is invalid!');

		$activeRecord->setAutoAdjustColumnValueScenarios(array());
		$this->assertTrue($activeRecord->validate(), 'Model considered as invalid, while auto adjust scenarios are unset!');
	}

	/**
	 * @depends testActiveRecordColumnValueValidate
	 */
	public function testActiveRecordColumnValueSave() {
		$activeRecordFinder = CActiveRecord::model(self::getTestMainActiveRecordClassName());

		$activeRecord = $activeRecordFinder->find();

		$testColumnValueBase = 'test column value #'.rand();
		foreach ($activeRecord->columnValues as $key => $columnValueActiveRecord) {
			$testColumnValue = $testColumnValueBase.'#'.$key;
			$columnValueActiveRecord->value = $testColumnValue;
		}

		$activeRecord->save(false);

		$refreshedActiveRecord = $activeRecordFinder->findByPk($activeRecord->getPrimaryKey());

		foreach ($refreshedActiveRecord->columnValues as $key => $columnValueActiveRecord) {
			$testColumnValue = $testColumnValueBase.'#'.$key;
			$this->assertEquals($columnValueActiveRecord->value, $testColumnValue, 'Unable to save column value active records while saving the main one!');
		}

		// Auto adjust scenarios:
		$activeRecord = $activeRecordFinder->find();
		$activeRecord->setAutoAdjustColumnValueScenarios(array());

		$testColumnValue = 'Test Column Value Auto Adjust';
		$columnValueModel = $activeRecord->columnValues[0];
		$columnValueModel->value = $testColumnValue;

		$activeRecord->save();
		$refreshedActiveRecord = $activeRecordFinder->findByPk($activeRecord->getPrimaryKey());

		$this->assertNotEquals($refreshedActiveRecord->columnValues[0]->value, $testColumnValue, 'Column value active records are saved, while auto adjust scenarios are unset!');
	}

	/**
	 * @depends testGetColumnValueModelsNewRecord
	 */
	public function testColumnValuePropertyAccess() {
		$activeRecordClassName = self::getTestMainActiveRecordClassName();

		$activeRecord = new $activeRecordClassName();

		$columnActiveRecordFinder = CActiveRecord::model(self::getTestColumnActiveRecordClassName());
		$columnModel = $columnActiveRecordFinder->find(array('order'=>'RAND()'));

		$testPropertyName = $columnModel->name;
		$testPropertyValue = 'test_column_value';

		$activeRecord->$testPropertyName = $testPropertyValue;

		$columnValueModels = $activeRecord->getColumnValueModels();
		$this->assertEquals($testPropertyValue, $columnValueModels[$testPropertyName]->value, 'Unable to set column value through the property access!');
		$this->assertEquals($testPropertyValue, $activeRecord->$testPropertyName, 'Unable to get column value through the property access!');
	}
}
