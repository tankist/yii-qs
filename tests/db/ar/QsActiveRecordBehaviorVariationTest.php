<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorVariation".
 * @see QsActiveRecordBehaviorVariation
 */
class QsActiveRecordBehaviorVariationTest extends CTestCase {
	const TEST_VARIATOR_RECORDS_COUNT = 3;

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Variator:
		$testVariatorTableName = self::getTestVariatorTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testVariatorTableName, $columns);

		for ($i=1; $i<=self::TEST_VARIATOR_RECORDS_COUNT; $i++) {
			$data = array(
				'name' => 'variator '.$i
			);
			$dbSetUp->insert($testVariatorTableName, $data);
		}

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testVariatorTableName,
				'rules' => array(
					array('name', 'required'),
				),
			)
		);

		// Variation:
		$testVariationTableName = self::getTestVariationTableName();
		$columns = array(
			'id' => 'pk',
			'variation_main_id' => 'integer',
			'option_id' => 'integer',
			'variation_name' => 'string',
		);
		$dbSetUp->createTable($testVariationTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testVariationTableName,
				'rules' => array(
					array('variation_name', 'required'),
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
					'variationBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorVariation',
						'variatorModelClassName' => $testVariatorTableName,
						'variationsRelationName' => 'variations',
						'defaultVariationRelationName' => 'variation',
						'relationConfig' => array(
							$testVariationTableName, 'variation_main_id'
						),
						'variationOptionForeignKeyName' => 'option_id',
						'defaultVariationOptionForeignKeyCallback' => array($testMainTableName, 'findDefaultOptionId'),
					)
				),
				'additionalCode' => '
					public static function findDefaultOptionId() {
						// Place logic to determine default option here:
						return "1";
					}
				',
			)
		);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestVariatorTableName());
		$dbSetUp->dropTable(self::getTestMainTableName());
		$dbSetUp->dropTable(self::getTestVariationTableName());

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
		$testVariationTableName = self::getTestVariationTableName();

		$dbSetUp->truncateTable($testMainTableName);
		$dbSetUp->truncateTable($testVariationTableName);

		// insert:
		for ($mainId=1; $mainId<=5; $mainId++) {
			$data = array(
				'name' => 'main name '.$mainId
			);
			$dbSetUp->insert($testMainTableName, $data);

			for ($optionId=1; $optionId<=self::TEST_VARIATOR_RECORDS_COUNT; $optionId++) {
				$data = array(
					'variation_main_id' => $mainId,
					'option_id' => $optionId,
					'variation_name' => 'option name '.$mainId.'/'.$optionId
				);
				$dbSetUp->insert($testVariationTableName, $data);
			}
		}
	}

	/**
	 * Returns the name of the variator test table.
	 * @return string test table name.
	 */
	public static function getTestVariatorTableName() {
		return 'test_variator_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the variator test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestVariatorActiveRecordClassName() {
		return self::getTestVariatorTableName();
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
	 * Returns the name of the variation test table.
	 * @return string test table name.
	 */
	public static function getTestVariationTableName() {
		return 'test_variation_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the variation test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestVariationActiveRecordClassName() {
		return self::getTestVariationTableName();
	}

	/**
	 * Returns the model finder for the test active record.
	 * @return CActiveRecord model finder.
	 */
	public function getActiveRecordFinder() {
		$activeRecord = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		return $activeRecord;
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorVariation();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorVariation();

		$testInitialized = 'test_initialzed';
		$this->assertTrue($behavior->setInitialized($testInitialized), 'Unable it set initialzied!');
		$this->assertEquals($behavior->getInitialized(), $testInitialized, 'Unable it set initialzied correctly!');

		$testVariationsRelationName = 'test_variations_relation_name';
		$this->assertTrue($behavior->setVariationsRelationName($testVariationsRelationName), 'Unable to set variations relation name!');
		$this->assertEquals($behavior->getVariationsRelationName(), $testVariationsRelationName, 'Unable to set variations relation name correctly!');

		$testDefaultVariationRelationName = 'test_default_variation_relation_name';
		$this->assertTrue($behavior->setDefaultVariationRelationName($testDefaultVariationRelationName), 'Unable to set default variation relation config!');
		$this->assertEquals($behavior->getDefaultVariationRelationName(), $testDefaultVariationRelationName, 'Unable to set default variation relation config correctly!');

		$testRelationConfig = array(
			'testArg1',
			'testArg2'
		);
		$this->assertTrue($behavior->setRelationConfig($testRelationConfig), 'Unable to set relation config!');
		$this->assertEquals($behavior->getRelationConfig(), $testRelationConfig, 'Unable to set relation config correctly!');

		$testVariationOptionForeignKeyName = 'test_variation_option_id';
		$this->assertTrue($behavior->setVariationOptionForeignKeyName($testVariationOptionForeignKeyName), 'Unable to set variation option id name!');
		$this->assertEquals($behavior->getVariationOptionForeignKeyName(), $testVariationOptionForeignKeyName, 'Unable to set variation option id name correctly!');

		$testVariatorModelClassName = 'TestVariatorModelClassName';
		$this->assertTrue($behavior->setVariatorModelClassName($testVariatorModelClassName), 'Unable to set variator model class name!');
		$this->assertEquals($behavior->getVariatorModelClassName(), $testVariatorModelClassName, 'Unable to set variator model class name correctly!');

		$testDefaultVariationOptionForeignKeyCallback = array(
			'test_class_name',
			'test_class_method'
		);
		$this->assertTrue($behavior->setDefaultVariationOptionForeignKeyCallback($testDefaultVariationOptionForeignKeyCallback), 'Unable to set default foreign key callback!');
		$this->assertEquals($behavior->getDefaultVariationOptionForeignKeyCallback(), $testDefaultVariationOptionForeignKeyCallback, 'Unable to set default foreign key callback correctly!');

		$testAutoAdjustVariationScenarios = array(
			'test_scenario_1',
			'test_scenario_2',
		);
		$this->assertTrue($behavior->setAutoAdjustVariationScenarios($testAutoAdjustVariationScenarios), 'Unable to set auto adjust variation scenarios!');
		$this->assertEquals($behavior->getAutoAdjustVariationScenarios(), $testAutoAdjustVariationScenarios, 'Unable to set auto adjust variation scenarios correctly!');

		$testVariationAttributeDefaultValueMap = array(
			'test_variation_attribute_1' => 'test_main_attribute_1',
			'test_variation_attribute_2' => 'test_main_attribute_2',
		);
		$this->assertTrue($behavior->setVariationAttributeDefaultValueMap($testVariationAttributeDefaultValueMap), 'Unable to set variation attribute default value map!');
		$this->assertEquals($behavior->getVariationAttributeDefaultValueMap(), $testVariationAttributeDefaultValueMap, 'Unable to set variation attribute default value map correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetRelationConfigParam() {
		$behavior = new QsActiveRecordBehaviorRole();

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
	public function testNewActiveRecordVariation() {
		$activeRecordName = self::getTestMainActiveRecordClassName();
		$activeRecord = new $activeRecordName();

		$this->assertTrue(is_object($activeRecord));
		$this->assertTrue(is_object($activeRecord->variation));
	}

	/**
	 * @depends testNewActiveRecordVariation
	 */
	public function testFind() {
		$activeRecordBase = $this->getActiveRecordFinder();
		$testActiveRecord = $activeRecordBase->find();

		$this->assertTrue(is_object($testActiveRecord), 'Unable to find active record!');
		$this->assertTrue(is_object($testActiveRecord->variation), 'Unable to find active record\'s default variation!');
	}

	/**
	 * @depends testNewActiveRecordVariation
	 */
	public function testDefaultVariationPropertyAccess() {
		$activeRecordName = self::getTestMainActiveRecordClassName();

		$activeRecord = new $activeRecordName();
		$testVariationName = 'test_variation_name';
		$activeRecord->variation_name = $testVariationName;
		$this->assertEquals($activeRecord->variation->variation_name, $testVariationName, 'Unable to set property for the default variation active record!');
		$this->assertEquals($activeRecord->variation_name, $testVariationName, 'Unable to get property from the default variation active record directly!');
	}

	/**
	 * @depends testNewActiveRecordVariation
	 */
	public function testActiveRecordVariationValidate() {
		$startActiveRecord = $this->getActiveRecordFinder();
		$activeRecord = $startActiveRecord->find();

		$this->assertTrue($activeRecord->validate(), 'Just found model fails on validate!');

		$variationModel =$activeRecord->variations[0];
		$variationModel->variation_name = null;
		$this->assertFalse($activeRecord->validate(), 'Model considered as valid, while one of variation models is invalid!');

		$activeRecord->setAutoAdjustVariationScenarios(array());
		$this->assertTrue($activeRecord->validate(), 'Model considered as invalid, while auto adjust scenarios are unset!');
	}

	/**
	 * @depends testNewActiveRecordVariation
	 */
	public function testActiveRecordVariationSave() {
		$startActiveRecord = $this->getActiveRecordFinder();

		$activeRecord = $startActiveRecord->find();

		$testVariationNameBase = 'test variation name #'.rand();
		foreach ($activeRecord->variations as $key => $variationActiveRecord) {
			$testVariationName = $testVariationNameBase.'#'.$key;
			$variationActiveRecord->variation_name = $testVariationName;
		}

		$activeRecord->save(false);

		$refreshedActiveRecord = $startActiveRecord->findByPk($activeRecord->getPrimaryKey());

		foreach ($refreshedActiveRecord->variations as $key => $variationActiveRecord) {
			$testVariationName = $testVariationNameBase.'#'.$key;
			$this->assertEquals($variationActiveRecord->variation_name, $testVariationName, 'Unable to save variation active records while saving the main one!');
		}

		// Auto adjust scenarios:
		$activeRecord = $startActiveRecord->find();
		$activeRecord->setAutoAdjustVariationScenarios(array());

		$testVariationName = 'Test Variation Name Auto Adjust';
		$variationModel = $activeRecord->variations[0];
		$variationModel->variation_name = $testVariationName;

		$activeRecord->save();
		$refreshedActiveRecord = $startActiveRecord->findByPk($activeRecord->getPrimaryKey());

		$this->assertNotEquals($refreshedActiveRecord->variations[0]->variation_name, $testVariationName, 'Variation active records are saved while auto adjust scenarios are unset!');
	}

	/**
	 * @depends testNewActiveRecordVariation
	 */
	public function testInitialVariationModels() {
		$activeRecordName = self::getTestMainActiveRecordClassName();
		$activeRecord = new $activeRecordName();

		$variationModels = $activeRecord->getVariationModels();

		$this->assertTrue(!empty($variationModels), 'Variation models are empty for the new active record!');

		$variatorModelClassName = $activeRecord->getVariatorModelClassName();
		$variators = CActiveRecord::model($variatorModelClassName)->findAll();

		$this->assertEquals(count($variators), count($variationModels), 'Count of variations missmatch the count of the variators!');
	}

	/**
	 * @depends testInitialVariationModels
	 */
	public function testAdjustVariationModelsMissing() {
		$activeRecordFinder = $this->getActiveRecordFinder();
		$variatorClassName = $activeRecordFinder->getVariatorModelClassName();
		$variatorFinder = CActiveRecord::model($variatorClassName);

		$variatorModels = $variatorFinder->findAll();
		list($someVariatorModel) = $variatorModels;

		$dbSetUp = new QsTestDbMigration();
		$data = array(
			'name' => 'test main name for adjust'
		);
		$dbSetUp->insert(self::getTestMainTableName(), $data);
		$mainId = Yii::app()->db->getLastInsertID();
		$optionId = $someVariatorModel->getPrimaryKey();
		$data = array(
			'variation_main_id' => $mainId,
			'option_id' => $optionId,
			'variation_name' => 'adjust option name '.$mainId.'/'.$optionId
		);
		$dbSetUp->insert(self::getTestVariationTableName(), $data);

		$foundActiveRecord = $activeRecordFinder->findByPk($mainId);
		$variationModels = $foundActiveRecord->getVariationModels();
		$this->assertTrue(count($variationModels) == count($variatorModels), 'Count of variation models missmatch the count of variators!');
	}

	/**
	 * @depends testInitialVariationModels
	 */
	public function testAdjustVariationModelsExtra() {
		$activeRecordFinder = $this->getActiveRecordFinder();
		$variatorClassName = $activeRecordFinder->getVariatorModelClassName();
		$variatorFinder = CActiveRecord::model($variatorClassName);

		$variatorModels = $variatorFinder->findAll();

		$dbSetUp = new QsTestDbMigration();
		$data = array(
			'name' => 'test main name for adjust'
		);
		$dbSetUp->insert(self::getTestMainTableName(), $data);
		$mainId = Yii::app()->db->getLastInsertID();
		foreach ($variatorModels as $variatorModel) {
			$optionId = $variatorModel->getPrimaryKey();
			$data = array(
				'variation_main_id' => $mainId,
				'option_id' => $optionId,
				'variation_name' => 'adjust option name '.$mainId.'/'.$optionId
			);
			$dbSetUp->insert(self::getTestVariationTableName(), $data);
		}
		$extraOptionId = rand(self::TEST_VARIATOR_RECORDS_COUNT+100, 500);
		$data = array(
			'variation_main_id' => $mainId,
			'option_id' => $extraOptionId,
			'variation_name' => 'adjust option name '.$mainId.'/'.$extraOptionId
		);
		$dbSetUp->insert(self::getTestVariationTableName(), $data);

		$foundActiveRecord = $activeRecordFinder->findByPk($mainId);
		$variationModels = $foundActiveRecord->getVariationModels();
		$this->assertTrue(count($variationModels) == count($variatorModels), 'Count of variation models missmatch the count of variators!');
	}

	/**
	 * @depends testDefaultVariationPropertyAccess
	 */
	public function testFetchDefaultVariationAttributeValue() {
		$activeRecordName = self::getTestMainActiveRecordClassName();
		$activeRecord = new $activeRecordName();

		$testMainAttributeName = 'name';
		$testVariationAttributeName = 'variation_name';
		$testMainAttributeValue = 'test_main_name_value';

		$testVariationAttributeDefaultValueMap = array(
			$testVariationAttributeName => $testMainAttributeName
		);
		$activeRecord->setVariationAttributeDefaultValueMap($testVariationAttributeDefaultValueMap);

		$activeRecord->$testMainAttributeName = $testMainAttributeValue;

		$this->assertEquals($testMainAttributeValue, $activeRecord->fetchDefaultVariationAttributeValue($testVariationAttributeName), 'Unable to fetch default variation attribute name!');

		$this->assertEquals($testMainAttributeValue, $activeRecord->$testVariationAttributeName, 'Unable to fetch default variation attribute name through the direct access!');
	}
}
