<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorNameValue".
 * @see QsActiveRecordBehaviorNameValue
 */
class QsActiveRecordBehaviorNameValueTest extends CTestCase {

	public static function setUpBeforeClass() {
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
		$activeRecord = $this->getTestActiveRecordFinder();
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

	/**
	 * @return CActiveRecord test active record finder.
	 */
	public function getTestActiveRecordFinder() {
		$activeRecord = CActiveRecord::model(self::getTestActiveRecordClassName());
		return $activeRecord;
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorNameValue();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorNameValue();

		$testNameProperty = 'test_name_property';
		$this->assertTrue($behavior->setNamePropertyName($testNameProperty), 'Unable to set name attribute name!');
		$this->assertEquals($behavior->getNamePropertyName(), $testNameProperty, 'Unable to set name attribute name correctly!');

		$testValueProperty = 'test_value_property';
		$this->assertTrue($behavior->setValuePropertyName($testValueProperty), 'Unable to set value attribute name!');
		$this->assertEquals($behavior->getValuePropertyName(), $testValueProperty, 'Unable to set value attribute name correctly!');

		$testAutoNamePrefix = 'test_auto_name_prefix';
		$this->assertTrue($behavior->setAutoNamePrefix($testAutoNamePrefix), 'Unable to set auto name prefix!');
		$this->assertEquals($behavior->getAutoNamePrefix(), $testAutoNamePrefix, 'Unable to set auto name prefix correctly!');

		$testValuesCacheDuration = rand();
		$this->assertTrue($behavior->setValuesCacheDuration($testValuesCacheDuration), 'Unable to set values cache duration!');
		$this->assertEquals($behavior->getValuesCacheDuration(), $testValuesCacheDuration, 'Unable to set values cache duration correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetValues() {
		$activeRecord = $this->getTestActiveRecordFinder();

		$returnedValues = $activeRecord->getValues();
		$this->assertTrue(is_array($returnedValues) && !empty($returnedValues), 'Unable to get values!');

		$allRecords = $activeRecord->findAll();
		$nameAttribute = $activeRecord->getNamePropertyName();
		$valueAttribute = $activeRecord->getValuePropertyName();

		$autoNamePrefix = $activeRecord->getAutoNamePrefix();

		$expectedValues = array();
		foreach ($allRecords as $record) {
			$expectedValues[$autoNamePrefix.$record->$nameAttribute] = $record->$valueAttribute;
		}
		$this->assertEquals($returnedValues, $expectedValues, 'Unable to get values correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testUpdateValues() {
		$activeRecord = $this->getTestActiveRecordFinder();

		$originalValues = $activeRecord->getValues();
		$newValues = array();

		foreach ($originalValues as $name=>$value) {
			$newValues[$name] = "value of {$name} ".rand();
		}

		$this->assertTrue($activeRecord->updateValues($newValues), 'Unable to update values!');

		$returnedValues = $activeRecord->getValues();
		$this->assertEquals($returnedValues, $newValues, 'Unable to update values correctly!');
	}

	/**
	 * @depends testGetValues
	 * @depends testUpdateValues
	 */
	public function testCache() {
		$activeRecord = $this->getTestActiveRecordFinder();

		$originalValues = $activeRecord->getValues();

		$testCachedValues = array(
			'test_cache_name' => 'test cache value'
		);
		$cacheId = $activeRecord->getValuesCacheId();

		Yii::app()->cache->set($cacheId, $testCachedValues);

		$expectedCachedValues = $activeRecord->getValues();

		$this->assertEquals($testCachedValues, $expectedCachedValues, 'Values do not fetched from the cache!');
		$this->assertNotEquals($originalValues, $expectedCachedValues, 'Original values have not been overriden by cache!');

		$record = $activeRecord->find();
		$record->save();

		$expectedRefreshedValues = $activeRecord->getValues();
		$this->assertEquals($expectedRefreshedValues, $originalValues, 'Cache has not been cleared on save!');
	}

	/**
	 * @depends testGetValues
	 * @depends testUpdateValues
	 */
	public function testNamePrefix() {
		$activeRecord = $this->getTestActiveRecordFinder();

		$testAutoNamePrefix = 'test_auto_name_prefix';
		$activeRecord->setAutoNamePrefix($testAutoNamePrefix);

		$returnedValues = $activeRecord->getValues();

		$prefixApplied = true;

		foreach ($returnedValues as $name=>$value) {
			$prefixIsAtPlace = ( strpos($name, $testAutoNamePrefix) === 0 );
			$prefixApplied = $prefixApplied && $prefixIsAtPlace;
		}
		$this->assertTrue($prefixApplied, 'Auto name prefix has not been applied!');
	}
}
