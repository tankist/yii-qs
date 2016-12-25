<?php
 
/**
 * Test case for the extension "qs.db.QsBehaviorInternalDbTransaction".
 * @see QsBehaviorInternalDbTransaction
 */
class QsBehaviorInternalDbTransactionTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.db.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testTableName, $columns, 'engine=INNODB');
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();
		$dbSetUp->truncateTable($testTableName);
	}

	public function tearDown() {
		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();
		if (is_object($currentDbTransaction)) {
			$currentDbTransaction->rollback();
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
	 * Finds the record in the test table, which matches given column values.
	 * @param array $columns list of column names and values to be matched (name=>value)
	 * @return mixed test table record.
	 */
	protected function findTestTableRecord(array $columns) {
		$dbConnection = Yii::app()->db;
		$dbCommandBuilder = $dbConnection->commandBuilder;
		$criteria = new CDbCriteria();
		$criteria->addColumnCondition($columns);
		$dbCommand = $dbCommandBuilder->createFindCommand(self::getTestTableName(),$criteria);
		$record = $dbCommand->queryRow();
		return $record;
	}

	/**
	 * Asserts the record is present in test table.
	 * @param array $columns list of column names and values to be matched (name=>value)
	 * @param string $message of failure message.
	 */
	protected function assertTestTableRecordExists(array $columns, $message='') {
		$record = $this->findTestTableRecord($columns);
		$this->assertFalse(empty($record), $message);
	}

	/**
	 * Asserts the record does NOT present in test table.
	 * @param array $columns list of column names and values to be matched (name=>value)
	 * @param string $message of failure message.
	 */
	protected function assertTestTableRecordNotExists(array $columns, $message='') {
		$record = $this->findTestTableRecord($columns);
		$this->assertTrue(empty($record), $message);
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsBehaviorInternalDbTransaction();

		$testInternalDbTransaction = Yii::app()->getDb()->beginTransaction();
		$this->assertTrue($behavior->setInternalDbTransaction($testInternalDbTransaction), 'Unable to set internal db transaction!');
		$this->assertEquals($testInternalDbTransaction,$behavior->getInternalDbTransaction(), 'Unable to set internal db transaction correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testBeginInternalTransaction() {
		$behavior = new QsBehaviorInternalDbTransaction();

		$behavior->beginInternalDbTransaction();

		$internalDbTransaction = $behavior->getInternalDbTransaction();
		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();

		$this->assertEquals($currentDbTransaction, $internalDbTransaction, 'Unable to actually start transaction!');
	}

	/**
	 * @depends testBeginInternalTransaction
	 */
	public function testCommitInternalTransaction() {
		$dbConnection = Yii::app()->db;

		$behavior = new QsBehaviorInternalDbTransaction();

		$behavior->beginInternalDbTransaction();

		$testTableName = self::getTestTableName();
		$testRecordName = 'test_record_name';
		$sql = 'INSERT INTO '.$testTableName.'(name) VALUES ("'.$testRecordName.'")';
		$command = $dbConnection->createCommand($sql);
		$command->execute();

		$behavior->commitInternalDbTransaction();

		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();
		$this->assertTrue(empty($currentDbTransaction), 'Active db transaction is present after the internal transaction commit!');

		$this->assertTestTableRecordExists(array('name'=>$testRecordName), 'Data has not been inserted!');
	}

	/**
	 * @depends testBeginInternalTransaction
	 */
	public function testRollbackInternalTransaction() {
		$dbConnection = Yii::app()->db;

		$behavior = new QsBehaviorInternalDbTransaction();

		$behavior->beginInternalDbTransaction();

		$testTableName = self::getTestTableName();
		$testRecordName = 'test_record_name';
		$sql = 'INSERT INTO '.$testTableName.'(name) VALUES ("'.$testRecordName.'")';
		$command = $dbConnection->createCommand($sql);
		$command->execute();

		$behavior->rollbackInternalDbTransaction();

		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();
		$this->assertTrue(empty($currentDbTransaction), 'Active db transaction is present after the internal transaction commit!');

		$this->assertTestTableRecordNotExists(array('name'=>$testRecordName), 'Data has been inserted!');
	}
}
