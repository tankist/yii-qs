<?php
 
/**
 * Test case for the component {@link MessageTranslationMapper} of the module "qs.i18n.modules.messagetranslation.MessagetranslationModule".
 * @see MessagetranslationModule
 * @see MessageTranslationMapper
 */
class MessageTranslationMapperTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected static $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.messagetranslation.MessagetranslationModule');
		Yii::import('qs.i18n.modules.messagetranslation.components.*');
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

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testTableName));

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

	/**
	 * Creates the test message translation mapper instance.
	 * @return MessageTranslationMapper message translation mapper instance.
	 */
	protected function createMessageTranslationMapper() {
		$methodsList = array(
			'saveTranslation',
			'findTranslations',
		);
		$messageTranslationMapper = $this->getMock('MessageTranslationMapper', $methodsList);
		return $messageTranslationMapper;
	}

	// Tests:

	public function testSetGet() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$testDefaultMessagePath = '/test/default/message/path';
		$this->assertTrue($messageTranslationMapper->setDefaultMessagePath($testDefaultMessagePath), 'Unable to set default message path!');
		$this->assertEquals($messageTranslationMapper->getDefaultMessagePath(), $testDefaultMessagePath, 'Unable to set default message path correctly!');

		$testMessageCategoryNames = array(
			'test_category_1',
			'test_category_2',
		);
		$this->assertTrue($messageTranslationMapper->setMessageCategoryNames($testMessageCategoryNames), 'Unable to set message category names!');
		$this->assertEquals($messageTranslationMapper->getMessageCategoryNames(), $testMessageCategoryNames, 'Unable to set message category names correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultMessagePathDefaultValue() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$defaultMessagePathDefaultValue = $messageTranslationMapper->getDefaultMessagePath();
		$this->assertFalse(empty($defaultMessagePathDefaultValue), 'Unable to get default value of default message path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultMessageCategoryNames() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$testDefaultMessageBasePath = Yii::getPathOfAlias('system.messages.fr');
		$messageTranslationMapper->setDefaultMessagePath($testDefaultMessageBasePath);

		$messageCategoryNames = $messageTranslationMapper->getMessageCategoryNames();

		$this->assertFalse(empty($messageCategoryNames), 'Unable to get default message category names!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testFindAll() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();
		$testDefaultMessageBasePath = Yii::getPathOfAlias('system.messages.fr');
		$messageTranslationMapper->setDefaultMessagePath($testDefaultMessageBasePath);

		$messageTranslations = $messageTranslationMapper->findAll();
		$this->assertFalse(empty($messageTranslations), 'Unable to find all message translations!');
	}
}
