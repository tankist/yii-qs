<?php
 
/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminCallModelMethod".
 * @see QsActionAdminCallModelMethod
 */
class QsActionAdminCallModelMethodTest extends CTestCase {
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
			'group_id' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testTableName));
	}

	public static function tearDownAfterClass() {
		// Components:
		Yii::app()->setComponent('request', self::$_requestBackup);

		// Database:
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);
		for ($i=1; $i<=5; $i++) {
			$columns = array(
				'name' => 'test_name',
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
		$action = new QsActionAdminCallModelMethod($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminCallModelMethod" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$controller = new CController('test');
		$action = new QsActionAdminCallModelMethod($controller, 'test');

		$testViewName = 'test_view_name';
		$this->assertTrue($action->setView($testViewName), 'Unable to set view!');
		$this->assertEquals($action->getView(), $testViewName, 'Unable to set view correctly!');

		$testModelMethodParams = array(
			'testParam1',
			'testParam2',
		);
		$this->assertTrue($action->setModelMethodParams($testModelMethodParams), 'Unable to set model method params!');
		$this->assertEquals($action->getModelMethodParams(), $testModelMethodParams, 'Unable to set model method params correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testRedirect() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminCallModelMethod($mockController, 'test');
		$action->modelMethodName = 'validate';
		$action->setView('');

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}

		$this->assertTrue($pageRedirected, 'Page has not been redirected!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testRenderView() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminCallModelMethod($mockController, 'test');
		$action->modelMethodName = 'validate';
		$action->setView('view');

		$viewRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$viewRendered = true;
		}

		$this->assertTrue($viewRendered, 'View is not rendered!');
	}

	/**
	 * @depends testRedirect
	 */
	public function testFlashMessage() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminCallModelMethod($mockController, 'test');
		$action->modelMethodName = 'validate';

		$testFlashMessageKey = 'testFlashMessageKey';
		$action->flashMessageKey = $testFlashMessageKey;
		$testFlashMessageContent = 'Test flash message content';
		$action->flashMessage = $testFlashMessageContent;

		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			// shut down exception
		}

		$actualFlashMessageContent = Yii::app()->user->getFlash($testFlashMessageKey);

		$this->assertFalse(empty($actualFlashMessageContent), 'No flash message has been setup!');
		$this->assertEquals($testFlashMessageContent, $actualFlashMessageContent, 'Flash message is incorrect!');
	}
}
