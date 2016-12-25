<?php
 
/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminInternalDbTransaction".
 * @see QsActionAdminInternalDbTransaction
 */
class QsActionAdminInternalDbTransactionTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.actions.*');

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
	 * Creates test action instance.
	 * @return QsActionAdminInternalDbTransaction test action instance.
	 */
	protected function createTestAction() {
		$controller = new CController('test');
		$methodsList = array(
			'run'
		);
		return $this->getMock('QsActionAdminInternalDbTransaction', $methodsList, array($controller, 'test'));
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
		$dbCommand = $dbCommandBuilder->createFindCommand(self::getTestTableName(), $criteria);
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

	public function testCreate() {
		$action = $this->createTestAction();
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminInternalDbTransaction" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testBeginInternalDbTransaction() {
		$action = $this->createTestAction();

		$action->beginInternalDbTransaction();

		$internalTransaction = $action->getInternalDbTransaction();
		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();

		$this->assertEquals($currentDbTransaction, $internalTransaction, 'Unable to actually start transaction!');
	}

	/**
	 * @depends testBeginInternalDbTransaction
	 */
	public function testCommitInternalDbTransaction() {
		$dbConnection = Yii::app()->db;

		$action = $this->createTestAction();

		$action->beginInternalDbTransaction();

		$testTableName = self::getTestTableName();
		$testRecordName = 'test_record_name';
		$sql = 'INSERT INTO '.$testTableName.'(name) VALUES ("'.$testRecordName.'")';
		$command = $dbConnection->createCommand($sql);
		$command->execute();

		$action->commitInternalDbTransaction();

		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();
		$this->assertTrue(empty($currentDbTransaction), 'Active db transaction is present after the internal transaction commit!');

		$this->assertTestTableRecordExists(array('name' => $testRecordName), 'Data has not been inserted!');
	}

	/**
	 * @depends testBeginInternalDbTransaction
	 */
	public function testRollbackInternalDbTransaction() {
		$dbConnection = Yii::app()->db;

		$action = $this->createTestAction();

		$action->beginInternalDbTransaction();

		$testTableName = self::getTestTableName();
		$testRecordName = 'test_record_name';
		$sql = 'INSERT INTO '.$testTableName.'(name) VALUES ("'.$testRecordName.'")';
		$command = $dbConnection->createCommand($sql);
		$command->execute();

		$action->rollbackInternalDbTransaction();

		$currentDbTransaction = Yii::app()->db->getCurrentTransaction();
		$this->assertTrue(empty($currentDbTransaction), 'Active db transaction is present after the internal transaction commit!');

		$this->assertTestTableRecordNotExists(array('name'=>$testRecordName), 'Data has been inserted!');
	}

	/**
	 * @depends testBeginInternalDbTransaction
	 */
	public function testDisableInternalDbTransaction() {
		$action = $this->createTestAction();

		$action->useInternalDbTransaction = false;
		$action->beginInternalDbTransaction();

		$internalTransaction = $action->getInternalDbTransaction();
		$this->assertEquals(null, $internalTransaction, 'Unable to prevent start of transaction!');
	}
}
