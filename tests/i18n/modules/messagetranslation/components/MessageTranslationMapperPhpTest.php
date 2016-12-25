<?php
 
/**
 * Test case for the component {@link MessageTranslationMapperPhp} of the module "qs.i18n.modules.messagetranslation.MessagetranslationModule".
 * @see MessagetranslationModule
 * @see MessageTranslationMapperPhp
 */
class MessageTranslationMapperPhpTest extends CTestCase {
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

		$path = $this->getTestBasePath();
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
	}

	public function tearDown() {
		$path = $this->getTestBasePath();
		if (file_exists($path)) {
			$command = "rm -rf {$path}";
			exec($command);
		}
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
	 * @return MessageTranslationMapperPhp message translation mapper instance.
	 */
	protected function createMessageTranslationMapper() {
		$testBasePath = $this->getTestBasePath();
		$testDefaultMessagePath = Yii::getPathOfAlias('system.messages.fr');
		$messageTranslationMapperConfig = array(
			'class' => 'MessageTranslationMapperPhp',
			'defaultMessagePath' => $testDefaultMessagePath,
			'basePath' => $testBasePath
		);
		$messageTranslationMapper = Yii::createComponent($messageTranslationMapperConfig);
		return $messageTranslationMapper;
	}

	/**
	 * Returns test translation messages base path.
	 * @return string test base path.
	 */
	protected function getTestBasePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.get_class($this);
	}

	// Tests:

	public function testSetGet() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$testBasePath = '/test/base/path';
		$this->assertTrue($messageTranslationMapper->setBasePath($testBasePath), 'Unable to set base path!');
		$this->assertEquals($messageTranslationMapper->getBasePath(), $testBasePath, 'Unable to set base path correctly!');

		$testFilePermission = 0777;
		$this->assertTrue($messageTranslationMapper->setFilePermission($testFilePermission), 'Unable to set file permission!');
		$this->assertEquals($messageTranslationMapper->getFilePermission(), $testFilePermission, 'Unable to set file permission correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultBasePath() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$defaultBasePath = $messageTranslationMapper->getBasePath();
		$this->assertFalse(empty($defaultBasePath), 'Unable to get default base path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testFindAll() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();
		$testDefaultMessageBasePath = Yii::getPathOfAlias('system.messages.fr');
		$messageTranslationMapper->setDefaultMessagePath($testDefaultMessageBasePath);
		$testBasePath = Yii::getPathOfAlias('system.messages');
		$messageTranslationMapper->setBasePath($testBasePath);

		$messageTranslations = $messageTranslationMapper->findAll();
		$this->assertFalse(empty($messageTranslations), 'Unable to find all message translations!');
	}

	/**
	 * @depends testFindAll
	 */
	public function testSave() {
		$messageTranslationMapper = $this->createMessageTranslationMapper();

		$models = $messageTranslationMapper->findAll();

		$modelKey = array_rand($models,1);

		$model = $models[$modelKey];

		$testLanguage = 'test_lang';
		$model->language = $testLanguage;
		$testContent = 'Test content';
		$model->content = $testContent;

		$this->assertTrue($messageTranslationMapper->save($model), 'Unable to save model!');
	}
}
