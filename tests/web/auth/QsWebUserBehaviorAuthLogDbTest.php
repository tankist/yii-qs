<?php
 
/**
 * Test case for the extension "qs.web.auth.QsWebUserBehaviorAuthLogDb".
 * @see QsWebUserBehaviorAuthLogDb
 */
class QsWebUserBehaviorAuthLogDbTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.auth.*');
		$dbSetUp = new QsTestDbMigration();

		$columns = array(
			'id' => 'pk',
			'date' => 'datetime',
			'ip' => 'varchar(50)',
			'host' => 'string',
			'url' => 'string',
			'script_name' => 'string',
			'user_id' => 'integer',
			'username' => 'string',
			'error_code' => 'string',
			'error_message' => 'string',
		);
		$dbSetUp->createTable(self::getTestAuthLogTableName(), $columns);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestAuthLogTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->truncateTable(self::getTestAuthLogTableName());
	}

	public function tearDown() {
		if (Yii::app()->hasComponent(self::getCustomDbConnectionId())) {
			$customDbConnection = Yii::app()->getComponent(self::getCustomDbConnectionId());
			$customDbConnection->active = false;
		}
	}

	/**
	 * Returns test auth log table name.
	 * @return string log table name.
	 */
	protected static function getTestAuthLogTableName() {
		$testLogTableName = 'test_auth_log_table_'.getmypid();
		return $testLogTableName;
	}

	/**
	 * Creates test web user component.
	 * @return QsWebUser test web user instance.
	 */
	protected function createTestWebUser() {
		$testWebUserConfig = array(
			'class' => 'QsWebUser',
			'behaviors' => array(
				'authLogBehavior' => array(
					'class' => 'QsWebUserBehaviorAuthLogDb',
					'authLogTableName' => self::getTestAuthLogTableName(),
				),
			),
		);
		$testWebUser = Yii::createComponent($testWebUserConfig);
		$testWebUser->init();
		return $testWebUser;
	}

	/**
	 * Returns the database log record.
	 * @param string $dbConnectionId db connection component id.
	 * @return mixed array or null log record.
	 */
	protected function queryAuthLogRecord($dbConnectionId='db') {
		$criteria = new CDbCriteria();
		$findCommand = Yii::app()->getComponent($dbConnectionId)->commandBuilder->createFindCommand(self::getTestAuthLogTableName(), $criteria);
		$record = $findCommand->queryRow(true);
		return $record;
	}

	/**
	 * Creates test user identity.
	 * @return IUserIdentity test user identity instance.
	 */
	protected function createTestUserIdentity() {
		$methods = array(
			'authenticate',
			'getIsAuthenticated',
			'getId',
			'getName',
			'getPersistentStates',
		);
		$testUserIdentity = $this->getMock('CBaseUserIdentity', $methods);
		return $testUserIdentity;
	}

	/**
	 * Creates test SQLite database in memory and creates the log table in it.
	 * @return CDbConnection
	 */
	protected function createTestDbConnection() {
		$dbConnection = new CDbConnection('sqlite::memory:');
		$columns = array(
			'id' => 'pk',
			//'date' => 'datetime',
			'ip' => 'varchar(50)',
			'host' => 'string',
			'url' => 'string',
			'script_name' => 'string',
			'user_id' => 'integer',
			'username' => 'string',
			'error_code' => 'integer',
			'error_message' => 'string',
		);
		$dbConnection->createCommand()->createTable(self::getTestAuthLogTableName(), $columns);
		return $dbConnection;
	}

	/**
	 * Returns name of the test custom db connection component id.
	 * @return string db connection component id.
	 */
	protected static function getCustomDbConnectionId() {
		return __CLASS__.getmypid();
	}

	/**
	 * Initializes the db connection component under id {@link getCustomDbConnectionId()}.
	 * @return boolean success.
	 */
	protected function initCustomDbConnection() {
		$testDbConnectionId = self::getCustomDbConnectionId();
		$testDbConnection = $this->createTestDbConnection();
		Yii::app()->setComponent($testDbConnectionId, $testDbConnection);
		return true;
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsWebUserBehaviorAuthLogDb();

		$testLogTableName = 'TestModel';
		$this->assertTrue($behavior->setAuthLogTableName($testLogTableName), 'Unable to set log table name!');
		$this->assertEquals($testLogTableName, $behavior->getAuthLogTableName(), 'Unable to set log table name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testWriteLog() {
		$testWebUser = $this->createTestWebUser();

		$this->assertTrue($testWebUser->writeAuthLog(), 'Unable to write log!');

		$dbLogRecord = $this->queryAuthLogRecord();
		$this->assertFalse(empty($dbLogRecord), 'Unable to insert record into the database!');
	}

	/**
	 * @depends testWriteLog
	 */
	public function testWriteLogDefaultData() {
		$testWebUser = $this->createTestWebUser();

		$testIp = '127.0.0.1';
		$_SERVER['REMOTE_ADDR'] = $testIp;
		$testScriptName = 'test/script.php';
		$_SERVER['SCRIPT_NAME'] = $testScriptName;
		$testHttpHost = 'test_domain.com';
		$_SERVER['HTTP_HOST'] = $testHttpHost;
		$testRequestUri = '/test/request/uri';
		$_SERVER['REQUEST_URI'] = $testRequestUri;

		$testWebUser->writeAuthLog();

		$dbLogRecord = $this->queryAuthLogRecord();

		$this->assertEquals($testIp, $dbLogRecord['ip'], 'Unable to log ip!');
		$this->assertEquals(gethostbyaddr($testIp), $dbLogRecord['host'], 'Unable to log host!');
		$this->assertEquals($testScriptName, $dbLogRecord['script_name'], 'Unable to log script name!');
		$this->assertEquals($testHttpHost.$testRequestUri, $dbLogRecord['url'], 'Unable to log URL!');
	}

	/**
	 * @depends testWriteLog
	 */
	public function testWriteLogError() {
		$testWebUser = $this->createTestWebUser();

		$testErrorMessage = 'Test error message';
		$testErrorCode = rand(1,100);
		$testWebUser->writeAuthLogError($testErrorMessage, $testErrorCode);

		$dbLogRecord = $this->queryAuthLogRecord();

		$this->assertEquals($testErrorMessage, $dbLogRecord['error_message'], 'Unable to log error message!');
		$this->assertEquals($testErrorCode, $dbLogRecord['error_code'], 'Unable to log error code!');
	}

	/**
	 * @depends testWriteLog
	 */
	public function testWriteLogFromUserIdentity() {
		$testWebUser = $this->createTestWebUser();

		$testUserIdentity = $this->createTestUserIdentity();
		$testErrorMessage = 'Test error message';
		$testUserIdentity->errorMessage = $testErrorMessage;
		$testErrorCode = rand(1,100);
		$testUserIdentity->errorCode = $testErrorCode;

		$this->assertTrue($testWebUser->writeAuthLogFromUserIdentity($testUserIdentity), 'Unable to write log from user identity');

		$dbLogRecord = $this->queryAuthLogRecord();

		$this->assertEquals($testErrorMessage, $dbLogRecord['error_message'], 'Unable to log error message!');
		$this->assertEquals($testErrorCode, $dbLogRecord['error_code'], 'Unable to log error code!');
	}

	/**
	 * @depends testWriteLog
	 */
	public function testWriteLogOnLogin() {
		$testWebUser = $this->createTestWebUser();

		$testUserIdentity = $this->createTestUserIdentity();
		@$testWebUser->login($testUserIdentity);

		$dbLogRecord = $this->queryAuthLogRecord();
		$this->assertFalse(empty($dbLogRecord), 'Unable to log user login!');
	}

	public function testGetDbConnection() {
		$behavior = new QsWebUserBehaviorAuthLogDb();

		$testDbConnectionId = self::getCustomDbConnectionId();
		$this->initCustomDbConnection();

		$behavior->dbConnectionId = $testDbConnectionId;

		$this->assertEquals(Yii::app()->getComponent($testDbConnectionId), $behavior->getDbConnection(), 'Unable to get custom db connection!');
	}

	/**
	 * @depends testWriteLog
	 * @depends testGetDbConnection
	 */
	public function testWriteLogCustomDbConnection() {
		$testWebUser = $this->createTestWebUser();

		$testDbConnectionId = self::getCustomDbConnectionId();
		$this->initCustomDbConnection();

		$testWebUser->dbConnectionId = $testDbConnectionId;

		$this->assertTrue($testWebUser->writeAuthLog(), 'Unable to write log with custom db connection!');

		$dbLogRecord = $this->queryAuthLogRecord($testDbConnectionId);
		$this->assertFalse(empty($dbLogRecord), 'Unable to insert record into the database!');
	}
}
