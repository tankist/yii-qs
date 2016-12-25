<?php
 
/**
 * Created by JetBrains PhpStorm.
 * User: pklimov
 * Date: 24.01.12
 * Time: 12:22
 * To change this template use File | Settings | File Templates.
 */
class MessageTranslationFilterTest extends CTestCase {
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
						'languageModelClassName' => self::getTestLanguageActiveRecordClassName()
					)
				),
			),
		);
		return $modulesConfig;
	}

	// Tests:

	public function testSetGet() {
		$filter = new MessageTranslationFilter();

		$testLanguages = array(
			'lang_1',
			'lang_2',
		);
		$this->assertTrue($filter->setLanguages($testLanguages), 'Unable to set languages!');
		$this->assertEquals($filter->getLanguages(), $testLanguages, 'Unable to set languages correctly!');

		$testContents = array(
			'lang_1' => 'value_1',
			'lang_2' => 'value_2',
		);
		$this->assertTrue($filter->setContents($testContents), 'Unable to set contents!');
		$this->assertEquals($filter->getContents(), $testContents, 'Unable to set contents correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLanguages() {
		$filter = new MessageTranslationFilter();

		$defaultLanguages = $filter->getLanguages();
		$this->assertFalse(empty($defaultLanguages), 'Unable to get default languages!');

		$languageManager = Yii::app()->getModule('messagetranslation')->getComponent('languageManager');
		$lanuageModels = $languageManager->getLanguages();

		$this->assertEquals(count($lanuageModels), count($defaultLanguages), 'Wrong default languages count!');
	}

	/**
	 * @depends testGetDefaultLanguages
	 */
	public function testContentAttributesSetup() {
		$filter = new MessageTranslationFilter();

		$languageManager = Yii::app()->getModule('messagetranslation')->getComponent('languageManager');
		$languages = $languageManager->getLanguages();

		foreach ($languages as $language) {
			$attributeName = 'content_'.$language->locale_code;
			$attributeValue = $filter->$attributeName;

			$this->assertTrue(empty($attributeValue), 'Not set attribute not empty!');

			$testAttributeValue = 'test_content_'.$language->locale_code.'_'.rand();
			$filter->$attributeName = $testAttributeValue;
			$this->assertEquals($testAttributeValue, $filter->$attributeName, 'Unable to set attribute value!');
		}
	}

	/**
	 * @depends testContentAttributesSetup
	 */
	public function testIsEmpty() {
		$filter = new MessageTranslationFilter();

		$this->assertTrue($filter->isEmpty(), 'Just created filter is not empty!');

		$filter->name = 'test_name';
		$this->assertFalse($filter->isEmpty(), 'Filled up filter is empty!');
	}

	/**
	 * @depends testContentAttributesSetup
	 */
	public function testFilterByName() {
		$models = array();
		for ($i=1; $i<=5; $i++) {
			$model = new MessageTranslation();
			$model->name = 'name_'.$i;
			$model->category_name = 'category_name_'.$i;
			$models[] = $model;
		}

		$filter = new MessageTranslationFilter();
		$testFilterName = 'name_'.rand(1,5);
		$filter->name = $testFilterName;
		$filteredModels = $filter->apply($models);

		$this->assertEquals(1, count($filteredModels), 'Unable to filter models by name!');
		foreach ($filteredModels as $filteredModel) {
			$this->assertEquals($testFilterName, $filteredModel->name, 'Unable to filter models by name correctly!');
		}
	}

	/**
	 * @depends testContentAttributesSetup
	 */
	public function testFilterByCategoryName() {
		$models = array();
		for ($i=1; $i<=5; $i++) {
			$model = new MessageTranslation();
			$model->name = 'name_'.$i;
			$model->category_name = 'category_name_'.$i;
			$models[] = $model;
		}

		$filter = new MessageTranslationFilter();
		$testFilterCategoryName = 'category_name_'.rand(1,5);
		$filter->category_name = $testFilterCategoryName;
		$filteredModels = $filter->apply($models);

		$this->assertEquals(1, count($filteredModels), 'Unable to filter models by category name!');
		foreach ($filteredModels as $filteredModel) {
			$this->assertEquals($testFilterCategoryName, $filteredModel->category_name, 'Unable to filter models by category name correctly!');
		}
	}
}
