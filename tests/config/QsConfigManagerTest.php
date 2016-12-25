<?php

Yii::import('qs.config.*');

/**
 * Test case for the extension "qs.config.QsConfigManager".
 * @see QsConfigManager
 */
class QsConfigManagerTest extends CTestCase {
	/**
	 * @var CCache cache application component backup.
	 */
	public static $_cacheBackup = null;

	public static function setUpBeforeClass() {
		if (Yii::app()->hasComponent('cache')) {
			self::$_cacheBackup = Yii::app()->getComponent('cache');
		}
		Yii::app()->setComponent('cache', array('class' => 'CDummyCache'), false);
	}

	public static function tearDownAfterClass() {
		if (is_object(self::$_cacheBackup)) {
			Yii::app()->setComponent('cache', self::$_cacheBackup);
		}
	}

	public function tearDown() {
		$fileName = self::getTestFileName();
		if (file_exists($fileName)) {
			unlink($fileName);
		}
	}

	/**
	 * @return string test file name
	 */
	protected function getTestFileName() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . getmypid() . '.php';
	}

	/**
	 * Creates test config manager.
	 * @return QsConfigManager config manager instance.
	 */
	protected function createTestManager() {
		$config = array(
			'class' => 'QsConfigManager',
			'storage' => array(
				'class' => 'QsConfigStorageFile',
				'fileName' => self::getTestFileName(),
			),
		);
		$component = Yii::createComponent($config);
		return $component;
	}

	// Tests :

	public function testSetGet() {
		$manager = new QsConfigManager();

		$items = array(
			new QsConfigItem(),
			new QsConfigItem(),
		);
		$manager->setItems($items);
		$this->assertEquals($items, $manager->getItems(), 'Unable to setup items!');

		$storage = new QsConfigStorageFile();
		$manager->setStorage($storage);
		$this->assertEquals($storage, $manager->getStorage(), 'Unable to setup storage!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultStorage() {
		$manager = new QsConfigManager();
		$storage = $manager->getStorage();
		$this->assertTrue(is_object($storage), 'Unable to get default storage!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetItemById() {
		$manager = new QsConfigManager();

		$itemId = 'testItemId';
		$item = new QsConfigItem();
		$manager->setItems(array(
			$itemId => $item
		));
		$this->assertEquals($item, $manager->getItem($itemId), 'Unable to get item by id!');
	}

	/**
	 * @depends testGetItemById
	 */
	public function testCreateItem() {
		$manager = new QsConfigManager();

		$itemId = 'testItemId';
		$itemConfig = array(
			'label' => 'testLabel'
		);
		$manager->setItems(array(
			$itemId => $itemConfig
		));
		$item = $manager->getItem($itemId);
		$this->assertTrue(is_object($item), 'Unable to create item from config!');
		$this->assertEquals($itemConfig['label'], $item->label, 'Unable to setup attributes!');
	}

	/**
	 * @depends testCreateItem
	 */
	public function testSetupItemsByFile() {
		$manager = new QsConfigManager();

		$items = array(
			'item1' => array(
				'label' => 'item1label'
			),
			'item2' => array(
				'label' => 'item2label'
			),
		);
		$fileName = self::getTestFileName();
		$fileContent = '<?php return ' . var_export($items, true) . ';';
		file_put_contents($fileName, $fileContent);

		$manager->setItems($fileName);

		foreach ($items as $id => $itemConfig) {
			$item = $manager->getItem($id);
			$this->assertEquals($itemConfig['label'], $item->label, 'Wrong item label');
		}
	}

	/**
	 * @depends testCreateItem
	 */
	public function testSetupItemValues() {
		$manager = new QsConfigManager();
		$items = array(
			'item1' => array(),
			'item2' => array(),
		);
		$manager->setItems($items);

		$itemValues = array(
			'item1' => 'item1value',
			'item2' => 'item2value',
		);
		$manager->setItemValues($itemValues);
		$this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to setup item values!');
	}

	/**
	 * @depends testCreateItem
	 */
	public function testComposeConfig() {
		$manager = new QsConfigManager();
		$items = array(
			'item1' => array(
				'path' => 'params.item1',
				'value' => 'item1value',
			),
			'item2' => array(
				'path' => 'params.item2',
				'value' => 'item2value',
			),
		);
		$manager->setItems($items);

		$config = $manager->composeConfig();
		$expectedConfig = array(
			'params' => array(
				'item1' => 'item1value',
				'item2' => 'item2value',
			),
		);
		$this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
	}

	/**
	 * @depends testSetupItemValues
	 */
	public function testStoreValues() {
		$manager = $this->createTestManager();
		$items = array(
			'item1' => array(
				'value' => 'item1value',
			),
			'item2' => array(
				'value' => 'item2value',
			),
		);
		$manager->setItems($items);

		$this->assertTrue($manager->saveValues(), 'Unable to save values!');
		$itemValues = $manager->getItemValues();

		$emptyItemValues = array(
			'item1' => null,
			'item2' => null,
		);

		$manager->setItemValues($emptyItemValues);
		$manager->restoreValues();
		$this->assertEquals($itemValues, $manager->getItemValues(), 'Unable to restore values!');

		$manager->clearValues();

		$manager->setItemValues($emptyItemValues);
		$this->assertEquals($emptyItemValues, $manager->getItemValues(), 'Unable to clear values!');
	}

	/**
	 * @depends testComposeConfig
	 * @depends testStoreValues
	 */
	public function testFetchConfig() {
		$manager = $this->createTestManager();
		$items = array(
			'item1' => array(
				'path' => 'params.item1',
				'value' => 'item1value',
			),
			'item2' => array(
				'path' => 'params.item2',
				'value' => 'item2value',
			),
		);
		$manager->setItems($items);
		$manager->saveValues();

		$manager = $this->createTestManager();
		$manager->setItems($items);

		$config = $manager->fetchConfig();
		$expectedConfig = array(
			'params' => array(
				'item1' => 'item1value',
				'item2' => 'item2value',
			),
		);
		$this->assertEquals($expectedConfig, $config, 'Wrong config composed!');
	}

	/**
	 * @depends testSetupItemValues
	 */
	public function testValidate() {
		$manager = new QsConfigManager();

		$itemId = 'testItem';
		$items = array(
			$itemId => array(
				'rules' => array(
					array('required')
				)
			),
		);
		$manager->setItems($items);

		$itemValues = array(
			$itemId => ''
		);
		$manager->setItemValues($itemValues);
		$this->assertFalse($manager->validate(), 'Invalid values considered as valid!');

		$itemValues = array(
			$itemId => 'some value'
		);
		$manager->setItemValues($itemValues);
		$this->assertTrue($manager->validate(), 'Valid values considered as invalid!');
	}
}