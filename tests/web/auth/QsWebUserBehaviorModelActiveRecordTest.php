<?php
 
/**
 * Test case for the extension "qs.web.auth.QsWebUserBehaviorModelActiveRecord".
 * @see QsWebUserBehaviorModelActiveRecord
 */
class QsWebUserBehaviorModelActiveRecordTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.auth.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'group_id' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testTableName));
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->truncateTable(self::getTestTableName());
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
	 * Returns the test model finder.
	 * @return CActiveRecord test model finder.
	 */
	protected function getTestModelFinder() {
		return CActiveRecord::model(self::getTestActiveRecordClassName());
	}

	/**
	 * Creates test web user component.
	 * @return QsWebUser test web user instance.
	 */
	protected function createTestWebUser() {
		$testWebUserConfig = array(
			'class' => 'QsWebUser',
			'behaviors' => array(
				'modelBehavior' => array(
					'class' => 'QsWebUserBehaviorModelActiveRecord',
					'modelClassName' => self::getTestActiveRecordClassName(),
				),
			),
		);
		$testWebUser = Yii::createComponent($testWebUserConfig);
		$testWebUser->init();
		return $testWebUser;
	}

	// Tests:

	public function testSetGet() {
		$behavior = new QsWebUserBehaviorModelActiveRecord();

		$testModelClassName = 'TestModelClassName';
		$this->assertTrue($behavior->setModelClassName($testModelClassName), 'Unable to set model class name!');
		$this->assertEquals($testModelClassName, $behavior->getModelClassName(), 'Unable to set model class name correctly!');

		$testModelFindCondition = array(
			'condition' => 'test_field = test_value',
		);
		$this->assertTrue($behavior->setModelFindCondition($testModelFindCondition), 'Unable to set model find condition!');
		$this->assertEquals($testModelFindCondition, $behavior->getModelFindCondition(), 'Unable to set model find condition correctly!');

		$testModelClassName = self::getTestActiveRecordClassName();
		$testModel = new $testModelClassName();
		$this->assertTrue($behavior->setModel($testModel), 'Unable to set model!');
		$this->assertEquals($testModel, $behavior->getModel(), 'Unable to set model correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultModel() {
		$testWebUser = $this->createTestWebUser();

		$testModelClassName = self::getTestActiveRecordClassName();
		$testRecord = new $testModelClassName();
		$testUserName = 'test_user_name';
		$testRecord->name = $testUserName;
		$testRecord->save(false);

		$testWebUser->setId($testRecord->getPrimaryKey());

		$defaultModel = $testWebUser->getModel();
		$this->assertTrue(is_object($defaultModel), 'Unable to get default model!');
		$this->assertEquals($testRecord->id, $defaultModel->id, 'Default model id is incorrect!');
		$this->assertEquals($testRecord->name, $defaultModel->name, 'Default model name is incorrect!');
	}

	/**
	 * @depends testGetDefaultModel
	 */
	public function testGetDefaultModelGuest() {
		$testWebUser = $this->createTestWebUser();
		$model = $testWebUser->getModel();
		$this->assertFalse(is_object($model), 'Model for guest exists!');
	}

	/**
	 * @depends testGetDefaultModel
	 */
	public function testModelFindCondition() {
		$testModelClassName = self::getTestActiveRecordClassName();
		$testRecord = new $testModelClassName();
		$testUserName = 'test_user_name';
		$testRecord->name = $testUserName;
		$testUserGroupId = rand(1,100);
		$testRecord->group_id = $testUserGroupId;
		$testRecord->save(false);

		$testWebUser = $this->createTestWebUser();
		$testWebUser->setId($testRecord->getPrimaryKey());
		$testModelFindCondition = array(
			'condition' => 'group_id = :group_id',
			'params' => array(
				'group_id' => $testUserGroupId
			),
		);
		$testWebUser->setModelFindCondition($testModelFindCondition);
		$webUserModel = $testWebUser->getModel();
		$this->assertTrue(is_object($webUserModel), 'Unable to find model with find condition!');
	}

	/**
	 * @depends testModelFindCondition
	 */
	public function testModelFindConditionFiltering() {
		$testModelClassName = self::getTestActiveRecordClassName();
		$testRecord = new $testModelClassName();
		$testUserName = 'test_user_name';
		$testRecord->name = $testUserName;
		$testUserGroupId = rand(1,100);
		$testRecord->group_id = $testUserGroupId;
		$testRecord->save(false);

		$testWebUser = $this->createTestWebUser();
		$testWebUser->setId($testRecord->getPrimaryKey());
		$testModelFindCondition = array(
			'condition' => 'group_id = :group_id',
			'params' => array(
				'group_id' => $testUserGroupId+rand(1,100)
			),
		);
		$testWebUser->setModelFindCondition($testModelFindCondition);
		$webUserModel = $testWebUser->getModel();
		$this->assertFalse(is_object($webUserModel), 'Model has been found ignoring find condition!');
	}

	/**
	 * @depends testGetDefaultModel
	 */
	public function testRefreshStatesFromModel() {
		$testWebUser = $this->createTestWebUser();

		$testModelClassName = self::getTestActiveRecordClassName();
		$testRecord = new $testModelClassName();
		$testUserName = 'test_user_name';
		$testRecord->name = $testUserName;
		$testRecord->save(false);

		$testWebUser->setId($testRecord->getPrimaryKey());

		$this->assertTrue($testWebUser->refreshStatesFromModel(), 'Unable to refresh states from model!');
		$this->assertEquals($testRecord->name, $testWebUser->getState('name'), 'Unable to refresh state value!');
	}

	/**
	 * @depends testRefreshStatesFromModel
	 */
	public function testEnsureModel() {
		$testWebUser = $this->createTestWebUser();

		$testModelClassName = self::getTestActiveRecordClassName();
		$testRecord = new $testModelClassName();
		$testUserName = 'test_user_name';
		$testRecord->name = $testUserName;
		$testRecord->save(false);

		$testWebUser->setId($testRecord->getPrimaryKey());

		$this->assertTrue($testWebUser->ensureModel(), 'Unable to ensure existed model!');
		$this->assertEquals($testRecord->name, $testWebUser->getState('name'), 'Unable to refresh states, while ensure model!');
	}

	/**
	 * @depends testEnsureModel
	 */
	public function testEnsureModelLogout() {
		$testWebUser = $this->createTestWebUser();

		$testUserId = 'test_user_id';
		$testWebUser->setId($testUserId);
		$this->assertFalse($testWebUser->getIsGuest(), 'User not considered as logged in!');

		@$this->assertFalse($testWebUser->ensureModel(), 'Not existed model has been ensured!');

		$this->assertTrue($testWebUser->getIsGuest(), 'User has not been logged out!');
	}
}
