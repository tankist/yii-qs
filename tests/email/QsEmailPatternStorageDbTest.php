<?php

/**
 * Test case for the extension "qs.email.includes.storages.QsEmailPatternStorageDb".
 * @see QsEmailPatternStorageDb
 */
class QsEmailPatternStorageDbTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.storages.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'timestamp' => 'integer',
			'email_from' => 'string',
			'email_name' => 'string',
			'subject' => 'text',
			'body' => 'text',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName'=>$testTableName));
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
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

	// Tests:

	public function testCreate() {
		$emailPatternStorage = new QsEmailPatternStorageDb();
		$this->assertTrue(is_object($emailPatternStorage));
	}

	/**
	 * @depends testCreate
	 */
	public function testCachedPatterns() {
		$emailPatternStorage = new QsEmailPatternStorageDb();

		$testCachedPatterns = array(
			'test_name' => new QsEmailPattern()
		);
		$this->assertTrue($emailPatternStorage->setCachedPatterns($testCachedPatterns), 'Unable to set CachedPatterns!');
		$this->assertEquals($emailPatternStorage->getCachedPatterns(), $testCachedPatterns, 'Unable to set CachedPatterns correctly!');

		$this->assertTrue($emailPatternStorage->clearCachedPatterns(), 'Unable to clear CachedPatterns!');
		$returnedCachedPatterns = $emailPatternStorage->getCachedPatterns();
		$this->assertTrue(empty($returnedCachedPatterns), 'Unable to clear CachedPatterns correctly!');

		$testPatternId = 'test_pattern_id';
		$testPatternInstance = new QsEmailPattern();
		$testPatternInstance->setId($testPatternId);
		$this->assertTrue($emailPatternStorage->addCachedPattern($testPatternInstance), 'Unable to add CachedPattern!');
		$this->assertEquals($emailPatternStorage->getCachedPattern($testPatternId), $testPatternInstance, 'Unable to add CachedPattern correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetPattern() {
		$emailPatternStorage = new QsEmailPatternStorageDb();
		$emailPatternStorage->setModelClassName(self::getTestActiveRecordClassName());

		$testPatternName = 'test_pattern_name';

		$dbCommandBuilder = Yii::app()->getDb()->getCommandBuilder();
		$testEmailPatternData = array(
			'name' => $testPatternName,
			'subject' => 'test subject',
			'body' => 'test body',
		);
		$dbCommand = $dbCommandBuilder->createInsertCommand(self::getTestTableName(), $testEmailPatternData);
		$dbCommand->execute();

		$emailPattern = $emailPatternStorage->getPattern($testPatternName);
		$this->assertInstanceOf('QsEmailPattern', $emailPattern, 'Could not get email pattern object!');
	}

	/**
	 * @depends testGetPattern
	 */
	public function testGetMissingPattern() {
		$emailPatternStorage = new QsEmailPatternStorageDb();
		$emailPatternStorage->setModelClassName(self::getTestActiveRecordClassName());

		$testPatternName = 'unexisting_pattern_name';

		$this->setExpectedException('CException');

		$emailPattern = $emailPatternStorage->getPattern($testPatternName);
	}

	/**
	 * @depends testCreate
	 */
	public function testModelClassName() {
		$emailPatternStorage = new QsEmailPatternStorageDb();

		$testModelClassName = 'testModelClassName';
		$this->assertTrue($emailPatternStorage->setModelClassName($testModelClassName), 'Unable to set ModelClassName!');
		$this->assertEquals($emailPatternStorage->getModelClassName(), $testModelClassName, 'Unable to set ModelClassName correctly!');
	}

}
