<?php
 
/**
 * Test case for the model {@link ImageTranslation} of the module "qs.i18n.modules.imagetranslation.ImagetranslationModule".
 * @see ImagetranslationModule
 * @see ImageTranslation
 */
class ImageTranslationTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected static $_modulesBackup = array();
	/**
	 * @var QsImageTranslationSource image translation source component backup.
	 */
	protected static $_imageTranslationSourceBackup = null;
	/**
	 * @var CAssetManager asset manager application component backup.
	 */
	protected static $_assetManagerBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.imagetranslation.ImagetranslationModule');
		Yii::import('qs.i18n.modules.imagetranslation.models.*');
		Yii::import('qs.i18n.images.*');

		// Modules:
		self::$_modulesBackup = Yii::app()->getModules();

		// Components:
		if (Yii::app()->hasComponent('imageTranslationSource')) {
			self::$_imageTranslationSourceBackup = Yii::app()->getComponent('imageTranslationSource');
		}
		if (Yii::app()->hasComponent('assetManager')) {
			self::$_assetManagerBackup = Yii::app()->getComponent('assetManager');
		}

		// Database:
		$testTableName = self::getTestLanguageTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'native_name' => 'string',
			'code' => 'string',
			'locale_code' => 'string',
			'html_code' => 'string',
		);
		$dbSetUp->createTable($testTableName, $columns);

		for ($i=1; $i<=3; $i++) {
			$columns = array(
				'name' => 'test_name_'.$i,
				'native_name' => 'test_name_'.$i,
				'code' => 'c'.$i,
				'locale_code' => 'l'.$i,
				'html_code' => 'h'.$i,
			);
			$dbSetUp->insert($testTableName, $columns);
		}

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testTableName));
	}

	public static function tearDownAfterClass() {
		Yii::app()->setModules(self::$_modulesBackup);

		if (is_object(self::$_imageTranslationSourceBackup)) {
			Yii::app()->setComponent('imageTranslationSource', self::$_imageTranslationSourceBackup);
		}
		if (is_object(self::$_assetManagerBackup)) {
			Yii::app()->setComponent('assetManager', self::$_assetManagerBackup);
		}

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestLanguageTableName());
	}

	public function setUp() {
		Yii::app()->setModules($this->createTestModulesConfig());
		Yii::app()->setComponent('imageTranslationSource', $this->createImageTranslationSource());
		Yii::app()->setComponent('assetManager', $this->createTestAssetManager());
	}

	/**
	 * Returns the name of the test language table.
	 * @return string test language table name.
	 */
	public static function getTestLanguageTableName() {
		return 'test_language_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test language active record class.
	 * @return string test language active record class name.
	 */
	public static function getTestLanguageActiveRecordClassName() {
		return self::getTestLanguageTableName();
	}

	/**
	 * Creates the configuration array for the application module
	 * @return array test modules config.
	 */
	protected function createTestModulesConfig() {
		$modulesConfig = array(
			'imagetranslation' => array(
				'class' => 'ImagetranslationModule',
				'components' => array(
					'languageManager' => array(
						'languageModelClassName' => self::getTestLanguageActiveRecordClassName()
					),
				)
			)
		);
		return $modulesConfig;
	}

	/**
	 * Creates test image translation source component.
	 * @return QsImageTranslationSource image translation source instance.
	 */
	protected function createImageTranslationSource() {
		$methodsList = array(
			'loadImageTranslation',
			'imageTranslationExists',
			'saveImageTranslation',
		);
		$imageTranslationSource = $this->getMock('QsImageTranslationSource', $methodsList);
		return $imageTranslationSource;
	}

	/**
	 * Creates test asset manager component.
	 * @return CAssetManager test asset manager component.
	 */
	protected function createTestAssetManager() {
		$methodsList = array(
			'publish'
		);
		$assetManager = $this->getMock('CAssetManager', $methodsList);
		$assetManager->expects($this->any())->method('publish')->will($this->returnArgument(0));
		return $assetManager;
	}

	/**
	 * Returns test image default base path.
	 * @return string image default base path.
	 */
	protected function getTestDefaultBasePath() {
		return Yii::getPathOfAlias('system.gii.assets.images');
	}

	// Tests:

	public function testCreate() {
		$model = new ImageTranslation();
		$this->assertTrue(is_object($model), 'Unable to create model instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testMissingImageUrlSetup() {
		$model = new ImageTranslation();

		$testMissingImageUrl = 'http://test/missing/image.jpg';
		$this->assertTrue($model->setMissingImageUrl($testMissingImageUrl), 'Unable to set missing image URL!');
		$this->assertEquals($model->getMissingImageUrl(), $testMissingImageUrl, 'Unable to set missing image URL correctly!');
	}

	/**
	 * @depends testMissingImageUrlSetup
	 */
	public function testGetDefaultMissingImageUrl() {
		$model = new ImageTranslation();

		$defaultMissingImageUrl = $model->getMissingImageUrl();
		$this->assertFalse(empty($defaultMissingImageUrl), 'Unable to get default missing image URL!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetFinder() {
		$modelFinder = ImageTranslation::model();
		$this->assertTrue(is_object($modelFinder), 'Unable to get model finder instance!');
	}

	/**
	 * @depends testGetFinder
	 */
	public function testFindAll() {
		$modelFinder = ImageTranslation::model();

		$testDefaultBasePath = $this->getTestDefaultBasePath();
		$modelFinder->setDefaultBasePath($testDefaultBasePath);

		$models = $modelFinder->findAll();

		$this->assertFalse(empty($models), 'Unable to find all models!');
	}

	/**
	 * @depends testFindAll
	 */
	public function testFindByName() {
		$modelFinder = ImageTranslation::model();

		$testDefaultBasePath = $this->getTestDefaultBasePath();
		$modelFinder->setDefaultBasePath($testDefaultBasePath);

		list($expectedModel) = $modelFinder->findAll();

		$testName = $expectedModel->name;

		$model = $modelFinder->findByName($testName);

		$this->assertTrue(is_object($model), 'Unable to find model by name!');
		$this->assertEquals($expectedModel, $model, 'Wrong model has been found by name!');
	}

	/**
	 * @depends testFindAll
	 */
	public function testFindAllFiltered() {
		$modelFinder = ImageTranslation::model();

		$testDefaultBasePath = $this->getTestDefaultBasePath();
		$modelFinder->setDefaultBasePath($testDefaultBasePath);

		list($testModel) = $modelFinder->findAll();

		$filter = array(
			'name' => $testModel->name
		);
		$models = $modelFinder->findAll($filter);

		$this->assertTrue(count($models)==1, 'Filter returns wrong count of models!');
		list($model) = $models;
		$this->assertEquals($model->name, $testModel->name, 'Filter returns wrong model!');

		$filter = array(
			'name' => $testModel->name.'_test_file_tail'
		);
		$models = $modelFinder->findAll($filter);
		$this->assertTrue(empty($models), 'Filter does not exclude files!');
	}
}
