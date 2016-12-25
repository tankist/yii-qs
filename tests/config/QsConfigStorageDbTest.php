<?php

Yii::import('qs.config.QsConfigStorageDb', true);

/**
 * Test case for the extension "qs.config.QsConfigStorageDb".
 * @see QsConfigStorageDb
 */
class QsConfigStorageDbTest extends CTestCase {
	public function setUp() {
		Yii::app()->setComponent(self::getTestDbComponentId(), $this->createTestDbConnection());
	}

	/**
	 * @return string test db connection application component name
	 */
	protected static function getTestDbComponentId() {
		return __CLASS__ . '_test_db';
	}

	/**
	 * @return string test table name
	 */
	protected static function getTestTableName() {
		return '_test_config';
	}

	/**
	 * Creates test SQLite database in memory and creates the log table in it.
	 * @return CDbConnection
	 */
	protected function createTestDbConnection() {
		$dbConnection = new CDbConnection('sqlite::memory:');
		$columns = array(
			'id' => 'string',
			'value' => 'string',
		);
		$dbConnection->createCommand()->createTable(self::getTestTableName(), $columns);
		return $dbConnection;
	}

	/**
	 * @return QsConfigStorageDb test storage instance.
	 */
	protected function createTestStorage() {
		$config = array(
			'class' => 'QsConfigStorageDb',
			'db' => self::getTestDbComponentId(),
			'table' => self::getTestTableName(),
		);
		$component = Yii::createComponent($config);
		return $component;
	}

	// Tests :

	public function testSave() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$this->assertTrue($storage->save($values), 'Unable to save values!');
	}

	/**
	 * @depends testSave
	 */
	public function testGet() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$storage->save($values);
		$this->assertEquals($values, $storage->get(), 'Unable to get values!');
	}

	/**
	 * @depends testGet
	 */
	public function testClear() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$storage->save($values);

		$this->assertTrue($storage->clear(), 'Unable to clear values!');
		$this->assertEquals(array(), $storage->get(), 'Values are not cleared!');
	}
}