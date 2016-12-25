<?php
 
/**
 * Test case for the extension "qs.db.ar.QsActiveRecordSaveManyToMany".
 * @see QsActiveRecordSaveManyToMany
 */
class QsActiveRecordSaveManyToManyTest extends CTestCase {
	const TEST_RELATED_RECORDS_COUNT = 5;

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Related:
		$testRelatedTableName = self::getTestRelatedTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testRelatedTableName, $columns);

		$activeRecordGenerator->generate(array('tableName' => $testRelatedTableName));

		// Connector:
		$testConnectorTableName = self::getTestConnectorTableName();
		$columns = array(
			'main_id' => 'integer',
			'related_id' => 'integer',
			'PRIMARY KEY(main_id,related_id)'
		);
		$dbSetUp->createTable($testConnectorTableName, $columns);

		$activeRecordGenerator->generate(array('tableName' => $testConnectorTableName));

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
				'relations' => array(
					self::getTestRelationName() => array(
						CActiveRecord::MANY_MANY,
						$testRelatedTableName,
						"{$testConnectorTableName}(main_id, related_id)"
					),
				),
				'behaviors' => array(
					self::getTestBehaviorName() => array(
						'class' => 'QsActiveRecordSaveManyToMany',
						'relationName' => self::getTestRelationName(),
					)
				),
				'rules' => array(
					array('name', 'required'),
					array(self::getTestRelationName().'_ids', 'safe'),
				),
			)
		);

		// Insert data:
		for ($i=1; $i<=self::TEST_RELATED_RECORDS_COUNT; $i++) {
			$columns = array(
				'name' => 'related_name_'.$i,
			);
			$dbSetUp->insert($testRelatedTableName, $columns);
		}
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestMainTableName());
		$dbSetUp->dropTable(self::getTestRelatedTableName());
		$dbSetUp->dropTable(self::getTestConnectorTableName());

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

		$dbSetUp->truncateTable($testMainTableName);

		for ($i=1; $i<=5; $i++) {
			$columns = array(
				'name' => 'main_name_'.$i,
			);
			$dbSetUp->insert($testMainTableName, $columns);
		}

		$dbSetUp->truncateTable(self::getTestConnectorTableName());
	}

	/**
	 * Returns the test "many to many" relation name.
	 * @return string relation name.
	 */
	public static function getTestRelationName() {
		return 'testManyToManyRelated';
	}

	/**
	 * Returns the test behavior name.
	 * @return string test behavior name.
	 */
	public static function getTestBehaviorName() {
		return 'testManyToManyBehavior';
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
	 * Returns the name of the test related table.
	 * @return string test table name.
	 */
	public static function getTestRelatedTableName() {
		return 'test_related_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test related active record class.
	 * @return string test active record class name.
	 */
	public static function getTestRelatedActiveRecordClassName() {
		return self::getTestRelatedTableName();
	}

	/**
	 * Returns the name of the test connector table.
	 * @return string test table name.
	 */
	public static function getTestConnectorTableName() {
		return 'test_connector_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test connector active record class.
	 * @return string test active record class name.
	 */
	public static function getTestConnectorActiveRecordClassName() {
		return self::getTestConnectorTableName();
	}

	/**
	 * Returns the test active record finder.
	 * @return CActiveRecord test active record finder.
	 */
	protected function getTestActiveRecordFinder() {
		$activeRecordFinder = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		return $activeRecordFinder;
	}

	/**
	 * Finds the connector table record with given foreign keys.
	 * @param integer $mainId main record foreign key.
	 * @param integer $relatedId related record foreign key.
	 * @return array|null connector table record.
	 */
	protected function findConnectorRecord($mainId, $relatedId) {
		$dbCommandBuilder = Yii::app()->db->getCommandBuilder();

		$connectorTableName = self::getTestConnectorTableName();
		$criteria = new CDbCriteria();
		$columns = array(
			'main_id' => $mainId,
			'related_id' => $relatedId,
		);
		$criteria->addColumnCondition($columns);
		$findCommand = $dbCommandBuilder->createFindCommand($connectorTableName, $criteria);
		return $findCommand->queryRow();
	}

	/**
	 * Asserts the connector table has a record with given foreign keys.
	 * @param integer $mainId main record foreign key.
	 * @param integer $relatedId related record foreign key.
	 * @param string $message message on assertion failure.
	 */
	protected function assertConnectorRecordExist($mainId, $relatedId, $message='') {
		$connectorRow = $this->findConnectorRecord($mainId, $relatedId);
		$this->assertFalse(empty($connectorRow), $message);
	}

	/**
	 * Asserts the connector table has NO record with given foreign keys.
	 * @param integer $mainId main record foreign key.
	 * @param integer $relatedId related record foreign key.
	 * @param string $message message on assertion failure.
	 */
	protected function assertConnectorRecordNotExist($mainId, $relatedId, $message='') {
		$connectorRow = $this->findConnectorRecord($mainId, $relatedId);
		$this->assertTrue(empty($connectorRow), $message);
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsActiveRecordSaveManyToMany();

		$testRelationName = 'test_relation_name';
		$this->assertTrue($behavior->setRelationName($testRelationName), 'Unable to set relation name!');
		$this->assertEquals($testRelationName, $behavior->getRelationName(), 'Unable to set relation name correctly!');

		$testRelationAttributeName = 'test_relation_attribute_name';
		$this->assertTrue($behavior->setRelationAttributeName($testRelationAttributeName), 'Unable to set relation attribute name!');
		$this->assertEquals($testRelationAttributeName, $behavior->getRelationAttributeName(), 'Unable to set relation attribute name correctly!');

		$testRelationAttributeValue = array(
			rand(1,10),
			rand(11,20),
		);
		$this->assertTrue($behavior->setRelationAttributeValue($testRelationAttributeValue), 'Unable to set relation attribute value!');
		$this->assertEquals($testRelationAttributeValue, $behavior->getRelationAttributeValue(), 'Unable to set relation attribute value correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultRelationAttributeName() {
		$behavior = new QsActiveRecordSaveManyToMany();

		$testRelationName = 'test_relation_name';
		$behavior->setRelationName($testRelationName);

		$defaultRelationAttributeName = $behavior->getRelationAttributeName();
		$this->assertContains($testRelationName, $defaultRelationAttributeName, 'Default attribute name does not contains relation name!');
		$this->assertTrue(strlen($defaultRelationAttributeName) > strlen($testRelationName), 'Attribute name length is not greater then relation name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultRelationAttributeValue() {
		$modelFinder = $this->getTestActiveRecordFinder();

		$testModel = $modelFinder->find(array('order'=>'RAND()'));
		$testRelatedModelId = rand(1,self::TEST_RELATED_RECORDS_COUNT);

		$dbSetUp = new QsTestDbMigration();
		$connectorTableName = self::getTestConnectorTableName();
		$columns = array(
			'main_id' => $testModel->id,
			'related_id' => $testRelatedModelId,
		);
		$dbSetUp->insert($connectorTableName,$columns);

		$defaultRelationAttributeValue = $testModel->getRelationAttributeValue();
		$this->assertTrue(is_array($defaultRelationAttributeValue) && !empty($defaultRelationAttributeValue), 'Unable to get default relation attribute value!');
		$this->assertTrue(in_array($testRelatedModelId, $defaultRelationAttributeValue), 'Default relation attribute name has wrong data!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetRelation() {
		$modelFinder = $this->getTestActiveRecordFinder();
		$behaviorName = self::getTestBehaviorName();

		$behavior = $modelFinder->$behaviorName;

		$relation = $behavior->getRelation();
		$this->assertTrue(is_object($relation), 'Unable to get relation!');
		$this->assertInstanceOf('CManyManyRelation', $relation, 'Wrong class name for the relation!');
	}

	/**
	 * @depends testGetRelation
	 */
	public function testGetRelatedTableSchema() {
		$modelFinder = $this->getTestActiveRecordFinder();
		$behaviorName = self::getTestBehaviorName();

		$behavior = $modelFinder->$behaviorName;

		$relatedTableSchema = $behavior->getRelatedTableSchema();
		$this->assertTrue(is_object($relatedTableSchema), 'Unable to get related table schema!');
		$this->assertInstanceOf('CDbTableSchema', $relatedTableSchema, 'Wrong class name for the related table schema!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testRelationAttributeValueDirectAccess() {
		$modelClassName = self::getTestMainActiveRecordClassName();
		$model = new $modelClassName();
		$relationAttributeName = $model->getRelationAttributeName();

		$testRelationAttributeValue = array(
			rand(1,10),
			rand(11,20),
		);
		$model->$relationAttributeName = $testRelationAttributeValue;
		$this->assertEquals($testRelationAttributeValue, $model->$relationAttributeName, 'Unable to access relation attribute value directly!');
	}

	/**
	 * @depends testGetRelatedTableSchema
	 */
	public function testSaveNewRecord() {
		$modelClassName = self::getTestMainActiveRecordClassName();
		$model = new $modelClassName();
		$relationAttributeName = $model->getRelationAttributeName();

		$model->name = 'test_new_model_name';
		$testRelatedId = rand(1, self::TEST_RELATED_RECORDS_COUNT);
		$testRelationAttributeValue = array(
			$testRelatedId
		);
		$model->$relationAttributeName = $testRelationAttributeValue;

		$this->assertTrue($model->save(false), 'Unable to save model!');

		$this->assertConnectorRecordExist($model->id, $testRelatedId, 'Unable to create connector record!');
	}

	/**
	 * @depends testSaveNewRecord
	 */
	public function testUpdateRecord() {
		$modelClassName = self::getTestMainActiveRecordClassName();
		$model = new $modelClassName();
		$relationAttributeName = $model->getRelationAttributeName();

		$model->name = 'test_model_update';
		$testStartRelatedId = 1;
		$testRelationAttributeValue = array(
			$testStartRelatedId
		);
		$model->$relationAttributeName = $testRelationAttributeValue;
		$model->save(false);

		$testNewRelatedId = rand($testStartRelatedId+1, self::TEST_RELATED_RECORDS_COUNT);
		$testRelationAttributeValue = array(
			$testNewRelatedId
		);
		$model->$relationAttributeName = $testRelationAttributeValue;

		$this->assertTrue($model->save(false), 'Unable to save existing model!');

		$this->assertConnectorRecordExist($model->id, $testNewRelatedId, 'Unable to create new connector record!');
		$this->assertConnectorRecordNotExist($model->id, $testStartRelatedId, 'Unable to remove old connector record!');
	}

	/**
	 * @depends testRelationAttributeValueDirectAccess
	 */
	public function testSetupAttributes() {
		$modelClassName = self::getTestMainActiveRecordClassName();
		$model = new $modelClassName();
		$relationAttributeName = $model->getRelationAttributeName();

		$testName = 'test_new_model_name';
		$testRelatedId = rand(1, self::TEST_RELATED_RECORDS_COUNT);
		$testRelationAttributeValue = array(
			$testRelatedId
		);
		$testModelAttributes = array(
			'name' => $testName,
			$relationAttributeName => $testRelationAttributeValue,
		);

		$model->attributes = $testModelAttributes;

		$this->assertEquals($testRelationAttributeValue, $model->$relationAttributeName, 'Unable to set relation attribute with batch attributes setup!');
	}
}
