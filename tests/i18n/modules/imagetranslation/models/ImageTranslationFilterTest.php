<?php
 
/**
 * Test case for the model {@link ImageTranslationFilter} of the module "qs.i18n.modules.imagetranslation.ImagetranslationModule".
 * @see ImagetranslationModule
 * @see ImageTranslationFilter
 */
class ImageTranslationFilterTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected static $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.imagetranslation.ImagetranslationModule');
		Yii::import('qs.i18n.modules.imagetranslation.models.*');

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
			'imagetranslation' => array(
				'class' => 'ImagetranslationModule',
				'components' => array(
					'languageManager' => array(
						'languageModelClassName' => self::getTestLanguageActiveRecordClassName()
					)
				),
			),
		);
		return $modulesConfig;
	}

	// Tests:

	public function testCreate() {
		$filter = new ImageTranslationFilter();
		$this->assertTrue(is_object($filter), 'Unable to create model instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$filter = new ImageTranslationFilter();

		$testLanguages = array(
			'lang_1',
			'lang_2',
		);
		$this->assertTrue($filter->setLanguages($testLanguages), 'Unable to set languages!');
		$this->assertEquals($filter->getLanguages(), $testLanguages, 'Unable to set languages correctly!');

		$testExistences = array(
			'lang_1' => 'value_1',
			'lang_2' => 'value_2',
		);
		$this->assertTrue($filter->setExistences($testExistences), 'Unable to set existences!');
		$this->assertEquals($filter->getExistences(), $testExistences, 'Unable to set existences correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLanguages() {
		$filter = new ImageTranslationFilter();

		$defaultLanguages = $filter->getLanguages();
		$this->assertFalse(empty($defaultLanguages), 'Unable to get default languages!');

		$languageManager = Yii::app()->getModule('imagetranslation')->getComponent('languageManager');
		$lanuageModels = $languageManager->getLanguages();

		$this->assertEquals(count($lanuageModels), count($defaultLanguages), 'Wrong default languages count!');
	}

	/**
	 * @depends testGetDefaultLanguages
	 */
	public function testExistAttributesSetup() {
		$filter = new ImageTranslationFilter();

		$languageManager = Yii::app()->getModule('imagetranslation')->getComponent('languageManager');
		$languages = $languageManager->getLanguages();

		foreach ($languages as $language) {
			$attributeName = 'exist_'.$language->locale_code;
			$attributeValue = $filter->$attributeName;

			$this->assertTrue(empty($attributeValue), 'Not set attribute not empty!');

			$testAttributeValue = 'test_exist_'.$language->locale_code.'_'.rand();
			$filter->$attributeName = $testAttributeValue;
			$this->assertEquals($testAttributeValue, $filter->$attributeName, 'Unable to set attribute value!');
		}
	}
}
