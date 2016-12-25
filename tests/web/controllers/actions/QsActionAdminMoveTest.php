<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminMove".
 * @see QsActionAdminMove
 */
class QsActionAdminMoveTest extends CTestCase {
	const TEST_RECORDS_COUNT = 10;
	/**
	 * @var CHttpRequest http request component backup.
	 */
	protected static $_requestBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.actions.*');
		Yii::import('qs.web.controllers.*');

		// Components:
		self::$_requestBackup = Yii::app()->getRequest();
		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);

		// Database:
		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'position' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'behaviors' => array(
					'positionBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorPosition'
					)
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		Yii::app()->setComponent('request', self::$_requestBackup);

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);
		for ($i=1; $i<=self::TEST_RECORDS_COUNT; $i++) {
			$columns = array(
				'name' => 'test_name',
				'position' => $i
			);
			$dbSetUp->insert($testTableName, $columns);
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
	 * @return QsTestController test controller instance.
	 */
	public function createMockController() {
		$mockController = new QsTestController();

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setModelClassName(self::getTestActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);

		return $mockController;
	}

	// Tests:

	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminMove($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminMove" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testMovePrev() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$_GET['to'] = 'prev';
		$testId = rand(1+1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$recordBeforeMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue( $pageRedirected, 'Page has not been redirected after moving!' );

		$recordAfterMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertEquals($recordBeforeMove->position-1, $recordAfterMove->position, 'Record position after moving is incorrect!');
	}

	/**
	 * @depends testCreate
	 */
	public function testMoveFirst() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$_GET['to'] = 'first';
		$testId = rand(1+2, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$recordBeforeMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected after moving!');

		$recordAfterMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertEquals(1, $recordAfterMove->position, 'Record position after moving is incorrect!');
	}

	/**
	 * @depends testCreate
	 */
	public function testMoveNext() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$_GET['to'] = 'next';
		$testId = rand(1, self::TEST_RECORDS_COUNT-1);
		$_GET['id'] = $testId;

		$recordBeforeMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue( $pageRedirected, 'Page has not been redirected after moving!' );

		$recordAfterMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertEquals($recordBeforeMove->position+1, $recordAfterMove->position, 'Record position after moving is incorrect!');
	}

	/**
	 * @depends testCreate
	 */
	public function testMoveLast() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$_GET['to'] = 'last';
		$testId = rand(1, self::TEST_RECORDS_COUNT-2);
		$_GET['id'] = $testId;

		$recordBeforeMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected after moving!');

		$recordAfterMove = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);
		$this->assertEquals(self::TEST_RECORDS_COUNT, $recordAfterMove->position, 'Record position after moving is incorrect!');
	}

	/**
	 * @depends testMovePrev
	 */
	public function testResultSort() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$_GET['to'] = 'prev';
		$testId = rand(1+1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$model = CActiveRecord::model(self::getTestActiveRecordClassName())->findByPk($testId);

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected after moving!');

		$redirectParams = $exception->getParams();
		$redirectUrl = $redirectParams['url'];

		$this->assertContains('sort/'.$model->getPositionAttributeName(), $redirectUrl, 'Sort by position attribute has not been applied!');
	}

	/**
	 * @depends testResultSort
	 */
	public function testResultCustomSort() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminMove($mockController, 'test');

		$testSortAttributeName = 'test_sort_attribute_name';
		$action->sortAttributeName = $testSortAttributeName;

		$_GET['to'] = 'prev';
		$testId = rand(1+1, self::TEST_RECORDS_COUNT);
		$_GET['id'] = $testId;

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue( $pageRedirected, 'Page has not been redirected after moving!' );

		$redirectParams = $exception->getParams();
		$redirectUrl = $redirectParams['url'];

		$this->assertContains('sort/'.$testSortAttributeName, $redirectUrl, 'Sort by custom sort attribute has not been applied!');
	}
}
