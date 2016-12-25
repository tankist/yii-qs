<?php
 
/**
 * Test case for the {@link QsTranslationLanguageManager} component of the module "qs.i18n.modules.base.QsWebModuleTranslationBase".
 * @see QsWebModuleTranslationBase
 * @see QsTranslationLanguageManager
 */
class QsTranslationLanguageManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.base.QsWebModuleTranslationBase');
		Yii::import('qs.i18n.modules.base.components.*');

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
		$activeRecordGenerator->generate(array('tableName'=>$testTableName));
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestLanguageTableName());
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
	 * Creates test {@link QsTranslationLanguageManager} component.
	 * @return QsTranslationLanguageManager language manager instance.
	 */
	protected function createQsTranslationLanguageManager() {
		$languageManagerConfig = array(
			'class' => 'QsTranslationLanguageManager',
			'languageModelClassName' => self::getTestLanguageActiveRecordClassName()
		);
		return Yii::createComponent($languageManagerConfig);
	}

	// Tests:

	public function testSetGet() {
		$languageManager = $this->createQsTranslationLanguageManager();

		$testLanguageModelClassName = 'testLanguageModelClassName';
		$this->assertTrue($languageManager->setLanguageModelClassName($testLanguageModelClassName), 'Unable to set language model class name!');
		$this->assertEquals($languageManager->getLanguageModelClassName(), $testLanguageModelClassName, 'Unable to set language model class name correctly!');

		$testLanguageModelSearchCriteria = array(
			'condition' => 'id = test'
		);
		$this->assertTrue($languageManager->setLanguageModelSearchCriteria($testLanguageModelSearchCriteria), 'Unable to set language model search criteria!');
		$this->assertEquals($languageManager->getLanguageModelSearchCriteria(), $testLanguageModelSearchCriteria, 'Unable to set language model search criteria correctly!');

		$testLanguages = CActiveRecord::model(self::getTestLanguageActiveRecordClassName())->findAll();
		$this->assertTrue($languageManager->setLanguages($testLanguages), 'Unable to set languages!');
		$this->assertEquals($languageManager->getLanguages(), $testLanguages, 'Unable to set languages correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLanguages() {
		$languageManager = $this->createQsTranslationLanguageManager();

		$defaultLanguages = $languageManager->getLanguages();
		$this->assertFalse(empty($defaultLanguages), 'Unable to get default languages!');

		$languages = CActiveRecord::model(self::getTestLanguageActiveRecordClassName())->findAll();
		$this->assertEquals(count($languages), count($defaultLanguages), 'Wrong count of default languages!');
	}
}
