<?php
 
/**
 * Test case for the model {@link MessageTranslation} of the module "qs.i18n.modules.messagetranslation.MessagetranslationModule".
 * @see MessagetranslationModule
 * @see MessageTranslation
 */
class MessageTranslationTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected static $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.messagetranslation.MessagetranslationModule');
		Yii::import('qs.i18n.modules.messagetranslation.models.*');

		self::$_modulesBackup = Yii::app()->getModules();

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

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestLanguageTableName());
	}

	public function setUp() {
		Yii::app()->setModules($this->createTestModulesConfig());
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
			'messagetranslation' => array(
				'class' => 'MessagetranslationModule',
				'components' => array(
					'languageManager' => array(
						'languageModelClassName' => self::getTestLanguageTableName()
					),
				)
			)
		);
		return $modulesConfig;
	}

	// Tests:

	public function testCreate() {
		$model = new MessageTranslation();
		$this->assertTrue(is_object($model), 'Unable to create model instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetId() {
		$model = new MessageTranslation();

		$testName = 'test_name';
		$testCategoryName = 'test_category_name';

		$model->name = $testName;
		$model->category_name = $testCategoryName;

		$id = $model->getId();
		$this->assertFalse(empty($id), 'Unable to get id!');

		$expectedId = base64_encode($testCategoryName.DIRECTORY_SEPARATOR.$testName);
		$this->assertEquals($expectedId, $id, 'Unable to get id correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetId() {
		$model = new MessageTranslation();

		$testName = 'test_name';
		$testCategoryName = 'test_category_name';
		$testId = base64_encode($testCategoryName.DIRECTORY_SEPARATOR.$testName);

		$this->assertTrue($model->setId($testId), 'Unable to set id!');

		$this->assertEquals($testName, $model->name, 'Model name has not been set!');
		$this->assertEquals($testCategoryName, $model->category_name, 'Model category name has not been set!');
	}

	/**
	 * @depends testCreate
	 */
	public function testTranslationSetup() {
		$model = new MessageTranslation();

		$testTranslations = array(
			'lang_1' => 'content_1',
			'lang_2' => 'content_2',
		);
		$this->assertTrue($model->setTranslations($testTranslations), 'Unable to set translations!');
		$this->assertEquals($model->getTranslations(), $testTranslations, 'Unable to set translations correctly!');

		$testTranslationLanguage = 'test_translation_language';
		$testTranslationContent = 'Test translation content';
		$this->assertTrue($model->addTranslation($testTranslationLanguage,$testTranslationContent), 'Unable to add translation!');
		$this->assertEquals($testTranslationContent, $model->getTranslation($testTranslationLanguage), 'Unable to add translation correctly!');
	}
}
