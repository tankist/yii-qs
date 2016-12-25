<?php
 
/**
 * Test case for the component {@link MessageTranslationMapperDb} of the module "qs.i18n.modules.messagetranslation.MessagetranslationModule".
 * @see MessagetranslationModule
 * @see MessageTranslationMapperDb
 */
class MessageTranslationMapperDbTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected static $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.messagetranslation.MessagetranslationModule');
		Yii::import('qs.i18n.modules.messagetranslation.components.*');
		Yii::import('qs.i18n.modules.messagetranslation.models.*');

		self::$_modulesBackup = Yii::app()->getModules();

		$testLanguageTableName = self::getTestLanguageTableName();

		$dbSetUp = new QsTestDbMigration();

		// Languages:
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'native_name' => 'string',
			'code' => 'string',
			'locale_code' => 'string',
			'html_code' => 'string',
		);
		$dbSetUp->createTable($testLanguageTableName, $columns);

		for ($i=1; $i<=3; $i++) {
			$columns = array(
				'name' => 'test_name_'.$i,
				'native_name' => 'test_name_'.$i,
				'code' => 'c'.$i,
				'locale_code' => 'l'.$i,
				'html_code' => 'h'.$i,
			);
			$dbSetUp->insert($testLanguageTableName, $columns);
		}

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testLanguageTableName));

		// Translation Tables:
		$columns = array(
			'id' => 'pk',
			'category' => 'string',
			'message' => 'text',
		);
		$dbSetUp->createTable(self::getTestSourceMessageTableName(), $columns);

		$columns = array(
			'id' => 'integer',
			'language' => 'string',
			'translation' => 'text',
		);
		$dbSetUp->createTable(self::getTestTranslatedMessageTableName(), $columns);
	}

	public static function tearDownAfterClass() {
		Yii::app()->setModules(self::$_modulesBackup);

		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestLanguageTableName());
		$dbSetUp->dropTable(self::getTestSourceMessageTableName());
		$dbSetUp->dropTable(self::getTestTranslatedMessageTableName());
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

	/**
	 * Creates the test message translation mapper instance.
	 * @return MessageTranslationMapperDb message translation mapper instance.
	 */
	protected function createMessageTranslationMapper() {
		$testDefaultMessagePath = Yii::getPathOfAlias('system.messages.fr');
		$messageTranslationMapperConfig = array(
			'class' => 'MessageTranslationMapperDb',
			'defaultMessagePath' => $testDefaultMessagePath,
		);
		$messageTranslationMapper = Yii::createComponent($messageTranslationMapperConfig);
		return $messageTranslationMapper;
	}

	/**
	 * Returns test source message table name.
	 * @return string source message table name.
	 */
	protected static function getTestSourceMessageTableName() {
		return 'test_message_source_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns test translated message table name.
	 * @return string translated message table name.
	 */
	protected static function getTestTranslatedMessageTableName() {
		return 'test_message_translated_'.__CLASS__.'_'.getmypid();
	}

	// Tests:

	public function testSetGet() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$testConnectionId = 'testConnectionId';
		$this->assertTrue($messageTranslationMapper->setConnectionId($testConnectionId), 'Unable to set connection id!');
		$this->assertEquals($messageTranslationMapper->getConnectionId(), $testConnectionId, 'Unable to set connection id correctly!');

		$testSourceMessageTable = 'testSourceMessageTable';
		$this->assertTrue($messageTranslationMapper->setSourceMessageTable($testSourceMessageTable), 'Unable to set source message table!');
		$this->assertEquals($messageTranslationMapper->getSourceMessageTable(), $testSourceMessageTable, 'Unable to set source message table correctly!');

		$testTranslatedMessageTable = 'testTranslatedMessageTable';
		$this->assertTrue($messageTranslationMapper->setTranslatedMessageTable($testTranslatedMessageTable), 'Unable to set translated message table!');
		$this->assertEquals($messageTranslationMapper->getTranslatedMessageTable(), $testTranslatedMessageTable, 'Unable to set translated message table correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultConnectionId() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$defaultConnectionId = $messageTranslationMapper->getConnectionId();
		$this->assertFalse(empty($defaultConnectionId), 'Unable to get default connection id!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultSourceMessageTable() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$defaultSourceMessageTable = $messageTranslationMapper->getSourceMessageTable();
		$this->assertFalse(empty($defaultSourceMessageTable), 'Unable to get default source message table!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultTranslatedMessageTable() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$defaultTranslatedMessageTable = $messageTranslationMapper->getTranslatedMessageTable();
		$this->assertFalse(empty($defaultTranslatedMessageTable), 'Unable to get default translated message table!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testFindAll() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$messageTranslationMapper->setSourceMessageTable(self::getTestSourceMessageTableName());
		$messageTranslationMapper->setTranslatedMessageTable(self::getTestTranslatedMessageTableName());

		$messageTranslations = $messageTranslationMapper->findAll();
		$this->assertFalse(empty($messageTranslations), 'Unable to find all message translations!');
	}

	/**
	 * @depends testFindAll
	 */
	public function testSave() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();
		$messageTranslationMapper->setSourceMessageTable(self::getTestSourceMessageTableName());
		$messageTranslationMapper->setTranslatedMessageTable(self::getTestTranslatedMessageTableName());

		$models = $messageTranslationMapper->findAll();

		$modelKey = array_rand($models,1);

		$model = $models[$modelKey];

		$testLanguage = 'test_lang';
		$model->language = $testLanguage;
		$testContent = 'Test content';
		$model->content = $testContent;

		$this->assertTrue($messageTranslationMapper->save($model), 'Unable to save model!');

		$filter = array(
			'name' => $model->name,
			'category_name' => $model->category_name,
		);
		list($model) = $messageTranslationMapper->findAll($filter);
		$this->assertEquals($testContent, $model->getTranslation($testLanguage), 'Unable to save content!');
	}
}
