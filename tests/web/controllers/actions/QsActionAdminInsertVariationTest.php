<?php

/**
 * Test case for the extension "qs.web.controllers.actions.QsActionAdminInsertVariation".
 * @see QsActionAdminInsertVariation
 */
class QsActionAdminInsertVariationTest extends CTestCase {
	const TEST_VARIATOR_RECORDS_COUNT = 3;
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
		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Variator:
		$testVariatorTableName = self::getTestVariatorTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testVariatorTableName, $columns);

		for ($i=1; $i<=self::TEST_VARIATOR_RECORDS_COUNT; $i++) {
			$data = array(
				'name' => 'variator '.$i
			);
			$dbSetUp->insert($testVariatorTableName, $data);
		}

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testVariatorTableName,
				'rules' => array(
					array('name', 'required'),
				),
			)
		);

		// Variation:
		$testVariationTableName = self::getTestVariationTableName();
		$columns = array(
			'id' => 'pk',
			'variation_main_id' => 'integer',
			'option_id' => 'integer',
			'variation_name' => 'string',
		);
		$dbSetUp->createTable($testVariationTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testVariationTableName,
				'rules' => array(
					array('variation_name', 'required'),
				),
			)
		);

		// Main:
		$testMainTableName = self::getTestMainTableName();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testMainTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testMainTableName,
				'rules' => array(
					array('name', 'required'),
				),
				'behaviors' => array(
					'variationBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorVariation',
						'variatorModelClassName' => $testVariatorTableName,
						'variationsRelationName' => 'variations',
						'defaultVariationRelationName' => 'variation',
						'relationConfig' => array(
							$testVariationTableName, 'variation_main_id'
						),
						'variationOptionForeignKeyName' => 'option_id',
						'defaultVariationOptionForeignKeyCallback' => array($testMainTableName, 'findDefaultOptionId'),
					)
				),
				'additionalCode' => '
					public static function findDefaultOptionId() {
						// Place logic to determine default option here:
						return "1";
					}
				',
			)
		);
	}

	public static function tearDownAfterClass() {
		Yii::app()->setComponent('request', self::$_requestBackup);

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestVariatorTableName());
		$dbSetUp->dropTable(self::getTestMainTableName());
		$dbSetUp->dropTable(self::getTestVariationTableName());
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testMainTableName = self::getTestMainTableName();
		$testVariationTableName = self::getTestVariationTableName();

		$dbSetUp->truncateTable($testMainTableName);
		$dbSetUp->truncateTable($testVariationTableName);
	}

	/**
	 * Returns the name of the variator test table.
	 * @return string test table name.
	 */
	public static function getTestVariatorTableName() {
		return 'test_variator_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the variator test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestVariatorActiveRecordClassName() {
		return self::getTestVariatorTableName();
	}

	/**
	 * Returns the name of the main test table.
	 * @return string test table name.
	 */
	public static function getTestMainTableName() {
		return 'test_main_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the main test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestMainActiveRecordClassName() {
		return self::getTestMainTableName();
	}

	/**
	 * Returns the name of the variation test table.
	 * @return string test table name.
	 */
	public static function getTestVariationTableName() {
		return 'test_variation_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the variation test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestVariationActiveRecordClassName() {
		return self::getTestVariationTableName();
	}

	/**
	 * Returns the model finder for the test active record.
	 * @return CActiveRecord model finder.
	 */
	public function getActiveRecordFinder() {
		$activeRecord = CActiveRecord::model(self::getTestMainActiveRecordClassName());
		return $activeRecord;
	}

	/**
	 * @return QsTestController test controller instance.
	 */
	public function createMockController() {
		$mockController = new QsTestController();

		$dataModelBehavior = new QsControllerBehaviorAdminDataModel();
		$dataModelBehavior->setModelClassName(self::getTestMainActiveRecordClassName());
		$mockController->attachBehavior('dataModelBehavior', $dataModelBehavior);

		return $mockController;
	}

	// Tests:

	public function testCreate() {
		$controller = new CController('test');
		$action = new QsActionAdminInsertVariation($controller, 'test');
		$this->assertTrue(is_object($action), 'Unable to create "QsActionAdminInsertVariation" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testViewForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertVariation($mockController, 'test');

		$viewRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$viewRendered = true;
		}

		$this->assertTrue($viewRendered, 'View is not rendered!');
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitForm() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertVariation($mockController, 'test');

		$modelClassName = self::getTestMainActiveRecordClassName();
		$model = CActiveRecord::model($modelClassName);
		$variationClassName = $model->getRelationConfigParam('class');

		$testMainRecordName = 'test_main_record_name_'.rand(1,100);
		$variationPostData = array();
		$variatorModels = CActiveRecord::model(self::getTestVariatorTableName())->findAll();
		foreach ($variatorModels as $variatorModel) {
			$variationPostData[] = array(
				'variation_name' => 'test_variation_record_name_'.rand(1,100)
			);
		}

		$_POST[$modelClassName] = array(
			'name' => $testMainRecordName,
		);
		$_POST[$variationClassName] = $variationPostData;

		$pageRedirected = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRedirect $exception) {
			$pageRedirected = true;
		}
		$this->assertTrue($pageRedirected, 'Page has not been redirected!');

		$insertedModel = CActiveRecord::model($modelClassName)->findByAttributes(array('name'=>$testMainRecordName));
		$this->assertTrue(is_object($insertedModel), 'Can not find inserted record!');
		$this->assertEquals(count($insertedModel->variations), count($variatorModels), 'Count of inserted variations missmatch the count of variators!');

		foreach ($insertedModel->variations as $variationKey => $variationModel) {
			$this->assertEquals($variationModel->variation_name, $variationPostData[$variationKey]['variation_name'], 'Variation record has wrong data!');
		}
	}

	/**
	 * @depends testViewForm
	 */
	public function testSubmitFormWithError() {
		$mockController = $this->createMockController();
		$action = new QsActionAdminInsertVariation($mockController, 'test');

		$_POST[self::getTestMainActiveRecordClassName()] = array(
			'name' => null,
		);

		$pageRendered = false;
		try {
			$mockController->runAction($action);
		} catch (QsTestExceptionRender $exception) {
			$pageRendered = true;
		}
		$this->assertTrue($pageRendered, 'Page has not been rendered after request with empty post!');
	}
}
