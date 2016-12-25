<?php
 
/**
 * Test case for the extension "qs.caching.QsBehaviorDataCache".
 * @see QsBehaviorDataCache
 */
class QsBehaviorDataCacheTest extends CTestCase {
	/**
	 * @var CCache cache application component backup.
	 */
	public static $_cacheBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.caching.QsBehaviorDataCache');

		if (Yii::app()->hasComponent('cache')) {
			self::$_cacheBackup = Yii::app()->getComponent('cache');
		}
	}

	public static function tearDownAfterClass() {
		if (is_object(self::$_cacheBackup)) {
			Yii::app()->setComponent('cache', self::$_cacheBackup);
		}

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

	public function tearDown() {
		Yii::app()->cache->flush();
	}

	/**
	 * Creates test cache component instance.
	 * @return CCache test cache component instance.
	 */
	protected static function createTestCacheComponent() {
		$config = array(
			'class' => 'CFileCache',
			'cachePath' => self::getTestCacheFilePath(),
		);
		$component = Yii::createComponent($config);
		$component->init();
		return $component;
	}

	/**
	 * Returns the test cache file path.
	 * @return string test file path.
	 */
	protected static function getTestCacheFilePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.__CLASS__.getmypid();
	}

	/**
	 * Creates test component instance.
	 * @param string $className name of the class to be generated.
	 * @return CComponent test component instance.
	 */
	protected function createTestComponent($className=null) {
		if (empty($className)) {
			$className = get_class($this).'Component';
		}

		if (!class_exists($className,false)) {
			$classDefinitionCode = <<<EOD
class {$className} extends CFormModel {
	public \$id;

	public function behaviors() {
		return array(
			'dataCacheBehavior' => array(
				'class' => 'QsBehaviorDataCache'
			),
		);
	}
}
EOD;
			eval($classDefinitionCode);
		}
		$component = new $className();
		return $component;
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsBehaviorDataCache();

		$testCacheDuration = rand(100,200);
		$this->assertTrue($behavior->setCacheDuration($testCacheDuration), 'Unable to set cache duration!');
		$this->assertEquals($testCacheDuration, $behavior->getCacheDuration(), 'Unable to set cache duration correctly!');

		$testOwnerIdPropertyName = 'test_owner_id';
		$this->assertTrue($behavior->setOwnerIdPropertyName($testOwnerIdPropertyName), 'Unable to set owner id property name!');
		$this->assertEquals($testOwnerIdPropertyName, $behavior->getOwnerIdPropertyName(), 'Unable to set owner id property name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetValueToCache() {
		$component = $this->createTestComponent();

		$testCacheId = 'test_cache_id';
		$testData = 'test data';
		$this->assertTrue($component->setDataToCache($testCacheId,$testData), 'Unable to set data to cache!');
		$this->assertEquals($testData, $component->getDataFromCache($testCacheId), 'Unable to set data to cache correctly!');
	}

	/**
	 * @depends testSetValueToCache
	 */
	public function testCacheVaryByClassName() {
		$firstComponent = $this->createTestComponent(get_class($this).'FirstComponent');
		$secondComponent = $this->createTestComponent(get_class($this).'SecondComponent');

		$testCacheId = 'test_cache_id';
		$testData = 'test data';
		$firstComponent->setDataToCache($testCacheId,$testData);

		$this->assertFalse($secondComponent->getDataFromCache($testCacheId), 'Cache id class dependency failed!');
	}

	/**
	 * @depends testSetValueToCache
	 */
	public function testCacheVaryByOwnerId() {
		$component = $this->createTestComponent();
		$component->setOwnerIdPropertyName('id');

		$testCacheId = 'test_cache_id';
		$testData = 'test data';

		$testComponentId = rand(1,10);
		$component->id = $testComponentId;
		$component->setDataToCache($testCacheId,$testData);
		$this->assertEquals($testData, $component->getDataFromCache($testCacheId), 'Unable to set cache value when vary by owner id!');

		$component->id = $testComponentId.'_'.rand(1,10);
		$this->assertFalse($component->getDataFromCache($testCacheId), 'Cache id component id dependency failed!');
	}
}
