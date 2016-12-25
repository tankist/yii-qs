<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorClearCache".
 * @see QsActiveRecordBehaviorClearCache
 */
class QsActiveRecordBehaviorClearCacheTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName'=>$testTableName));
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
	 * @return array test cache ids.
	 */
	public function getTestCacheIds() {
		$testCacheIds = array(
			'test_cache_id_1',
			'test_cache_id_2'
		);
		return $testCacheIds;
	}

	// Tests:
	
	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorClearCache();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorClearCache();

		$testDependingCacheIds = array(
			'test_cache_id_1',
			'test_cache_id_2',
		);
		$this->assertTrue($behavior->setDependingCacheIds($testDependingCacheIds), 'Unable to set depending cache ids!');
		$this->assertEquals($behavior->getDependingCacheIds(), $testDependingCacheIds, 'Unable to set depending cache ids correctly!');

		$testAdditionalDependingCacheIds = array(
			'additional_cache_id_1',
			'additional_cache_id_2',
		);
		$this->assertTrue($behavior->mergeDependingCacheIds($testAdditionalDependingCacheIds), 'Unable to merge depending cache ids!');

		$testDependingCacheIdCallback = array(
			'someClass',
			'someMethod'
		);
		$this->assertTrue($behavior->setDependingCacheIdCallback($testDependingCacheIdCallback), 'Unable to set depending cache id callback!');
		$this->assertEquals($behavior->getDependingCacheIdCallback(), $testDependingCacheIdCallback, 'Unable to set depending cache id callback correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testClearDependingCache() {
		$behavior = new QsActiveRecordBehaviorClearCache();

		$testCacheId = 'test_cache_id';
		$testCacheValue = 'test cache value';
		Yii::app()->cache->set($testCacheId, $testCacheValue, 20);

		$returnedCacheValue = Yii::app()->cache->get($testCacheId);
		$this->assertEquals($testCacheValue, $returnedCacheValue, 'Unable to set up a cache for the test!');

		$testDependingCacheIds = array(
			$testCacheId
		);
		$behavior->setDependingCacheIds($testDependingCacheIds);

		$this->assertTrue($behavior->clearDependingCache(), 'Unable to clear depending cache!');

		$returnedCacheValue = Yii::app()->cache->get($testCacheId);
		$this->assertTrue(empty($returnedCacheValue), 'Unable to actually clear depending cache!');
	}

	/**
	 * @depends testClearDependingCache
	 */
	public function testClearDependingCacheWithModel() {
		$behavior = new QsActiveRecordBehaviorClearCache();

		$model = CActiveRecord::model(self::getTestActiveRecordClassName())->find();
		$model->attachBehavior('testClearCacheBehavior', $behavior);

		$testCacheId = 'test_model_cache_id';
		$testCacheValue = 'test model cache value';
		Yii::app()->cache->set($testCacheId, $testCacheValue, 20);

		$returnedCacheValue = Yii::app()->cache->get($testCacheId);
		$this->assertEquals($testCacheValue, $returnedCacheValue, 'Unable to set up a cache for the test!');

		$testDependingCacheIds = array(
			$testCacheId
		);
		$model->setDependingCacheIds($testDependingCacheIds);

		$model->save(false);

		$returnedCacheValue = Yii::app()->cache->get($testCacheId);
		$this->assertTrue(empty($returnedCacheValue), 'Depending cache has not been cleared on model save!');

		Yii::app()->cache->set($testCacheId, $testCacheValue, 20);
		$model->delete();
		$returnedCacheValue = Yii::app()->cache->get($testCacheId);
		$this->assertTrue(empty($returnedCacheValue), 'Depending cache has not been cleared on model delete!');
	}

	/**
	 * @depends testClearDependingCache
	 */
	public function testClearDependingCacheByCallback() {
		$behavior = new QsActiveRecordBehaviorClearCache();

		$testDependingCacheIdCallback = array($this, 'getTestCacheIds');
		$testCacheIds = call_user_func($testDependingCacheIdCallback);
		foreach ($testCacheIds as $testCacheId) {
			$testCacheValue = $testCacheId.'_value';
			Yii::app()->cache->set($testCacheId, $testCacheValue, 20);
		}

		$behavior->setDependingCacheIdCallback($testDependingCacheIdCallback);

		$this->assertTrue($behavior->clearDependingCache(), 'Unable to clear depending cache!');

		foreach ($testCacheIds as $testCacheId) {
			$returnedCacheValue = Yii::app()->cache->get($testCacheId);
			$this->assertTrue(empty($returnedCacheValue), 'Unable to actually clear depending cache!');
		}
	}
}
