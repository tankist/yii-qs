<?php

Yii::import('qs.application.QsApplicationBehaviorApplyConfigManager');
Yii::import('qs.config.*');

/**
 * Test case for the extension "qs.application.QsApplicationBehaviorApplyConfigManager".
 * @see QsApplicationBehaviorApplyConfigManager
 */
class QsApplicationBehaviorApplyConfigManagerTest extends CTestCase {
	/**
	 * @var CCache cache application component backup.
	 */
	public static $_cacheBackup = null;

	public static function setUpBeforeClass() {
		Yii::app()->setComponent(self::getConfigManagerComponentId(), self::createTestConfigManager());

		if (Yii::app()->hasComponent('cache')) {
			self::$_cacheBackup = Yii::app()->getComponent('cache');
		}
		Yii::app()->setComponent('cache', array('class' => 'CDummyCache'), false);
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
		$fileName = self::getTestStorageFileName();
		if (file_exists($fileName)) {
			unlink($fileName);
		}
	}

	/**
	 * @return string test file name
	 */
	protected function getTestStorageFileName() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . getmypid() . '.php';
	}

	/**
	 * @return string config manager component id.
	 */
	protected static function getConfigManagerComponentId() {
		return __CLASS__ . 'ConfigManager';
	}

	/**
	 * Creates test config manager.
	 * @return QsConfigManager config manager instance.
	 */
	protected static function createTestConfigManager() {
		$config = array(
			'class' => 'QsConfigManager',
			'storage' => array(
				'class' => 'QsConfigStorageFile',
				'fileName' => self::getTestStorageFileName(),
			),
			'items' => array(
				'testParam1' => array(
					'value' => 'testParam1value',
				),
				'testParam2' => array(
					'value' => 'testParam1value',
				),
			),
		);
		$component = Yii::createComponent($config);
		return $component;
	}

	// Tests :

	public function testUpdateApplicationParams() {
		$behavior = new QsApplicationBehaviorApplyConfigManager();
		$behavior->configManagerComponentId = self::getConfigManagerComponentId();
		$behavior->attach(Yii::app());

		$testEvent = new CEvent(Yii::app());
		$behavior->beginRequest($testEvent);

		$configManager = $behavior->getConfigManager();

		$configItemValues = $configManager->getItemValues();

		foreach ($configItemValues as $paramName => $paramValue) {
			$this->assertEquals(Yii::app()->params[$paramName], $paramValue, 'CApplication::params does not contain value from the item config!');
		}
		$behavior->detach(Yii::app()); // cleanup
	}
}