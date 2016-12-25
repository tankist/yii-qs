<?php
 
/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorCrypt".
 * @see QsActiveRecordBehaviorCrypt
 */
class QsActiveRecordBehaviorCryptTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'secure_attribute' => 'binary',
			'secure_attribute_custom_key' => 'binary',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'rules' => array(
					array('secure_attribute', 'required'),
				),
				'behaviors' => array(
					'cryptBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorCrypt',
						'cryptAttributes' => array(
							'secure_attribute',
							'secure_attribute_custom_key' => self::getTestEncryptionKey()
						),
					)
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
	 * Returns the test encryption key.
	 * @return string encryption key.
	 */
	protected static function getTestEncryptionKey() {
		return __CLASS__.getmypid();
	}

	/**
	 * Creates new active record model instance.
	 * @return CActiveRecord active record model instance.
	 */
	protected function newTestActiveRecordModel() {
		$className = self::getTestActiveRecordClassName();
		$model = new $className();
		return $model;
	}

	/**
	 * Returns the security manager application component.
	 * @return CSecurityManager security manager instance.
	 */
	protected function getSecurityManager() {
		return Yii::app()->getSecurityManager();
	}

	/**
	 * Finds the raw data in the db table, which active record is associated with.
	 * @param integer $pk primary key value.
	 * @return array|null raw record data.
	 */
	protected function findActiveRecordRawData($pk) {
		$dbCommandBuilder = Yii::app()->getDb()->getCommandBuilder();
		$criteria = new CDbCriteria();
		$criteria->addColumnCondition(array('id'=>$pk));
		$dbCommand = $dbCommandBuilder->createFindCommand(self::getTestTableName(), $criteria);
		return $dbCommand->queryRow(true);
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorCrypt();

		$testCryptAttributes = array(
			'test_attribute_1',
			'test_attribute_2',
		);
		$this->assertTrue($behavior->setCryptAttributes($testCryptAttributes), 'Unable to set crypt attributes!');
		$this->assertEquals($testCryptAttributes, $behavior->getCryptAttributes(), 'Unable to set crypt attributes correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testEncryptAttribute() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute';
		$testAttributeValue = 'test_secure_value';
		$model->$testAttributeName = $testAttributeValue;

		$model->encryptAttributes();

		$encryptedAttributeValue = $model->$testAttributeName;

		$this->assertNotEquals($testAttributeValue, $encryptedAttributeValue, 'Unable to crypt the attribute!');

		$decryptedAttributeValue = $this->getSecurityManager()->decrypt($encryptedAttributeValue);
		$this->assertEquals($testAttributeValue, $decryptedAttributeValue, 'Unable to decrypt the encrypted attribute value!');
	}

	/**
	 * @depends testEncryptAttribute
	 */
	public function testDecryptAttribute() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute';
		$testAttributeValue = 'test_secure_value';
		$model->$testAttributeName = $testAttributeValue;

		$model->encryptAttributes();
		$model->decryptAttributes();

		$this->assertEquals($testAttributeValue, $model->$testAttributeName, 'Unable to decrypt the attribute!');
	}

	/**
	 * @depends testEncryptAttribute
	 */
	public function testEncryptAttributeCustomKey() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute_custom_key';
		$testAttributeValue = 'test_secure_value_custom_key';
		$model->$testAttributeName = $testAttributeValue;

		$model->encryptAttributes();

		$encryptedAttributeValue = $model->$testAttributeName;

		$this->assertNotEquals($testAttributeValue, $encryptedAttributeValue, 'Unable to crypt the attribute using custom key!');

		$decryptedAttributeValue = $this->getSecurityManager()->decrypt($encryptedAttributeValue,self::getTestEncryptionKey());
		$this->assertEquals($testAttributeValue, $decryptedAttributeValue, 'Unable to decrypt the custom key encrypted attribute value!');
	}

	/**
	 * @depends testDecryptAttribute
	 */
	public function testDecryptAttributeCustomKey() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute_custom_key';
		$testAttributeValue = 'test_secure_value_custom_key';
		$model->$testAttributeName = $testAttributeValue;

		$model->encryptAttributes();
		$model->decryptAttributes();

		$this->assertEquals($testAttributeValue, $model->$testAttributeName, 'Unable to decrypt the attribute with custom key!');
	}

	/**
	 * @depends testEncryptAttribute
	 */
	public function testEncryptOnModelSave() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute';
		$testAttributeValue = 'test_secure_value';
		$model->$testAttributeName = $testAttributeValue;

		$model->save(false);

		$dbRow = $this->findActiveRecordRawData($model->getPrimaryKey());

		$this->assertNotEquals($testAttributeValue, $dbRow[$testAttributeName], 'Attribute value has not been encrypted before saving!');

		$decryptedAttributeValue = $this->getSecurityManager()->decrypt($dbRow[$testAttributeName]);
		$this->assertEquals($testAttributeValue, $decryptedAttributeValue, 'Unable to decrypt the encrypted attribute value!');
	}

	/**
	 * @depends testEncryptOnModelSave
	 */
	public function testDecryptOnModelFind() {
		$model = $this->newTestActiveRecordModel();

		$testAttributeName = 'secure_attribute';
		$testAttributeValue = 'test_secure_value';
		$model->$testAttributeName = $testAttributeValue;

		$model->save(false);

		$foundModel = $model->findByPk($model->getPrimaryKey());
		$this->assertEquals($testAttributeValue, $foundModel->$testAttributeName, 'Unable to decrypt the attribute value after model is found!');
	}
}
