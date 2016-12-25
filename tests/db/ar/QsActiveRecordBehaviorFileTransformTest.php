<?php
 
/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorFileTransform".
 * @see QsActiveRecordBehaviorFileTransform
 */
class QsActiveRecordBehaviorFileTransformTest extends CTestCase {
	/**
	 * @var IQsFileStorage application file storage component backup.
	 */
	protected static $_fileStorageBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');
		Yii::import('qs.files.storages.*');
		Yii::import('qs.files.storages.filesystem.*');

		// Database:
		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'file_extension' => 'string',
			'file_version' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'behaviors' => array(
					'fileTransformBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorFileTransform',
						'transformCallback' => 'copy',
						'fileTransforms' => array(
							'default' => null,
							'custom' => null
						),
						'defaultFileUrl' => array(
							'default' => 'http://test.default.url',
							'custom' => 'http://test.custom.url'
						),
					)
				),
			)
		);

		// File Storage:
		if (Yii::app()->hasComponent('fileStorage')) {
			self::$_fileStorageBackup = Yii::app()->getComponent('fileStorage');
		}
		$fileStorage = Yii::createComponent(self::createFileStorageConfig());
		Yii::app()->setComponent('fileStorage', $fileStorage);
	}

	public static function tearDownAfterClass() {
		// Database:
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
		// File Storage:
		if (is_object(self::$_fileStorageBackup)) {
			Yii::app()->setComponent('fileStorage', self::$_fileStorageBackup);
		}
		// garbage collection:
		if (function_exists('gc_enabled')) {
			if (gc_enabled()) {
				gc_collect_cycles();
			} else {
				gc_enable();
				gc_collect_cycles();
				gc_disable();
			}
		}
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();
		$dbSetUp->truncateTable($testTableName);

		$columns = array(
			'name' => 'test_name',
		);
		$dbSetUp->insert($testTableName, $columns);

		$_FILES = array();
		CUploadedFile::reset();

		// Test source path:
		$testFileSourcePath = $this->getTestSourceBasePath();
		if (!file_exists($testFileSourcePath)) {
			mkdir($testFileSourcePath, 0777, true);
		}
	}

	public function tearDown() {
		$behaviorTempFilePath = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . 'QsActiveRecordBehaviorFileTransform';
		$command = "rm -rf {$behaviorTempFilePath}";
		exec($command);

		$testFileSourcePath = $this->getTestSourceBasePath();
		$command = "rm -rf {$testFileSourcePath}";
		exec($command);

		$testFileStoragePath = self::getTestFileStorageBasePath();
		$command = "rm -rf {$testFileStoragePath}";
		exec($command);
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
	 * Returns the base path for the test files.
	 * @return string test file base path.
	 */
	protected function getTestSourceBasePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.get_class($this).'_source';
	}

	/**
	 * Returns the base path for the test files.
	 * @return string test file base path.
	 */
	protected static function getTestFileStorageBasePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.__CLASS__.'_fileStorage';
	}

	/**
	 * Returns the file storage component configuration.
	 * @return array file storage component config.
	 */
	protected static function createFileStorageConfig() {
		$fileStorageConfig = array(
			'class' => 'QsFileStorageFileSystem',
			'basePath' => self::getTestFileStorageBasePath(),
			'baseUrl' => 'http://www.mydomain.com/files',
			'filePermission' => 0777
		);
		return $fileStorageConfig;
	}

	/**
	 * Returns the test active record finder.
	 * @return CActiveRecord active record finder instance.
	 */
	protected function getActiveRecordFinder() {
		return CActiveRecord::model(self::getTestTableName());
	}

	/**
	 * Creates new test active record instance.
	 * @return CActiveRecord active record instance.
	 */
	protected function newActiveRecord() {
		$className = self::getTestTableName();
		$activeRecord = new $className();
		return $activeRecord;
	}

	/**
	 * Returns the test file path.
	 * @return string test file full name.
	 */
	protected function getTestFileFullName() {
		$fileFullName = YII_PATH . '/cli/views/webapp/css/bg.gif';
		return $fileFullName;
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorFileTransform();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorFileTransform();

		$transformCallBack = 'copy';
		$this->assertTrue($behavior->setTransformCallback($transformCallBack), 'Unable to set transform callback!');
		$this->assertEquals($behavior->getTransformCallback(), $transformCallBack, 'Unable to set transform callback correctly!');

		$testFileTransforms = array(
			'test' => array(
				'param_1' => 'value_1',
				'param_2' => 'value_2',
			),
		);
		$this->assertTrue($behavior->setFileTransforms($testFileTransforms), 'Unable to set file transforms!');
		$this->assertEquals($behavior->getFileTransforms(), $testFileTransforms, 'Unable to set file transforms correctly!');

		$defaultFileTransformName = 'test_default_file_transform_name';
		$this->assertTrue($behavior->setDefaultFileTransformName($defaultFileTransformName), 'Unable to set default file transform name!');
		$this->assertEquals($defaultFileTransformName, $behavior->getDefaultFileTransformName(), 'Unable to set default file transform name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultValueOfDefaultFileTransformName() {
		$behavior = new QsActiveRecordBehaviorFileTransform();

		// Empty transform options:
		$behavior->setDefaultFileTransformName('');
		$expectedDefaultFileTransformName = 'default_file_transform_name_empty_transform_options';
		$behavior->setFileTransforms(array($expectedDefaultFileTransformName));

		$returnedDefaultFileTransformName = $behavior->getDefaultFileTransformName();
		$this->assertEquals($expectedDefaultFileTransformName, $returnedDefaultFileTransformName, 'Unable to get default value of default file transform name from empty transform options!');

		// With transform options:
		$behavior->setDefaultFileTransformName('');
		$expectedDefaultFileTransformName = 'default_file_transform_name_has_transform_options';
		$behavior->setFileTransforms(array($expectedDefaultFileTransformName=>array()));

		$returnedDefaultFileTransformName = $behavior->getDefaultFileTransformName();
		$this->assertEquals($expectedDefaultFileTransformName, $returnedDefaultFileTransformName, 'Unable to get default value of default file transform name with transform options!');
	}

	/**
	 * @depends testCreate
	 */
	public function testDefaultFileUrlSetUp() {
		$behavior = new QsActiveRecordBehaviorFileTransform();

		$testDefaultFileUrl = 'http://default/file/web/src';
		$this->assertTrue($behavior->setDefaultFileUrl($testDefaultFileUrl), 'Unable to set default file URL!');
		$this->assertEquals($behavior->getDefaultFileUrl(), $testDefaultFileUrl, 'Unable to set default file URL correctly!');

		$testDefaultFileUrlArray = array(
			'name1' => 'http://default/file/web/src/1',
			'name2' => 'http://default/file/web/src/2',
		);
		$this->assertTrue($behavior->setDefaultFileUrl($testDefaultFileUrlArray), 'Unable to set default file URL with array!');
		//$this->assertEquals($behavior->getDefaultFileUrl(), $testDefaultFileUrlArray, 'Unable to set default file URL with array correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSaveFile() {
		$activeRecordFinder = $this->getActiveRecordFinder();
		$fileTransforms = $activeRecordFinder->getFileTransforms();

		$activeRecord = $activeRecordFinder->find(null);

		$testFileName = $this->getTestFileFullName();
		$testFileExtension = CFileHelper::getExtension($testFileName);

		$this->assertTrue($activeRecord->saveFile($testFileName), 'Unable to save file!');

		$refreshedActiveRecord = $activeRecordFinder->findByPk($activeRecord->getPrimaryKey());

		foreach ($fileTransforms as $fileTransformName => $fileTransform) {
			$returnedFileFullName = $activeRecord->getFileFullName($fileTransformName);
			$fileStorageBucket = $activeRecord->getFileStorageBucket();

			$this->assertTrue($fileStorageBucket->fileExists($returnedFileFullName), "File for transformation name '{$fileTransformName}' does not exist!");
			$this->assertEquals(CFileHelper::getExtension($returnedFileFullName), $testFileExtension, 'Saved file has wrong extension!');

			$this->assertEquals($refreshedActiveRecord->getFileFullName($fileTransformName), $returnedFileFullName, 'Wrong full file name from the refreshed record!');
		}
	}

	/**
	 * @depends testSaveFile
	 */
	public function testSaveFileWithEmptyTransforms() {
		$activeRecordFinder = $this->getActiveRecordFinder();

		$activeRecord = $activeRecordFinder->find(null);
		$activeRecord->setFileTransforms(array());

		$testFileName = $this->getTestFileFullName();

		$this->setExpectedException('CException');
		$activeRecord->saveFile($testFileName);
	}

	/**
	 * @depends testSetGet
	 * @depends testDefaultFileUrlSetUp
	 */
	public function testUseDefaultFileUrl() {
		$activeRecordModel = $this->getActiveRecordFinder();
		$activeRecord = $activeRecordModel->find(null);

		// Single string:
		$emptyDefaultFileWebSrc = '';
		$activeRecord->setDefaultFileUrl($emptyDefaultFileWebSrc);
		$returnedFileWebSrc = $activeRecord->getFileUrl();
		$this->assertFalse(empty($returnedFileWebSrc), 'Unable to get file web src with empty default one!');

		$testDefaultFileWebSrc = 'http://test/default/file/web/src';
		$activeRecord->setDefaultFileUrl($testDefaultFileWebSrc);
		$returnedFileWebSrc = $activeRecord->getFileUrl();
		$this->assertEquals($returnedFileWebSrc, $testDefaultFileWebSrc, 'Default file web src does not used!');

		// Array:
		$transformNamePrefix = 'test_transform_';
		$defaultWebSrcPrefix = 'http://default/';
		$transformsCount = 3;
		$testDefaultFileWebSrcArray = array();
		for ($i=1; $i<=$transformsCount; $i++) {
			$transformName = $transformNamePrefix.$i;
			$defaultWebSrc = $defaultWebSrcPrefix.$i.rand();
			$testDefaultFileWebSrcArray[$transformName] = $defaultWebSrc;
		}
		$activeRecord->setDefaultFileUrl($testDefaultFileWebSrcArray);

		for ($i=1; $i<=$transformsCount; $i++) {
			$transformName = $transformNamePrefix.$i;
			$returnedMainFileWebSrc = $activeRecord->getFileUrl($transformName);
			$this->assertEquals($returnedMainFileWebSrc, $testDefaultFileWebSrcArray[$transformName], 'Unable to apply default file web src per each transfromation!');
		}
	}

	/**
	 * @depends testSaveFile
	 * @depends testGetDefaultValueOfDefaultFileTransformName
	 */
	public function testUseDefaultFileTransformName() {
		$activeRecordModel = $this->getActiveRecordFinder();
		$activeRecord = $activeRecordModel->find(null);

		$testFileName = $this->getTestFileFullName();
		$activeRecord->saveFile($testFileName);

		$defaultFileTransformName = $activeRecord->getDefaultFileTransformName();

		$this->assertEquals($activeRecord->getFileSelfName($defaultFileTransformName), $activeRecord->getFileSelfName(), 'Unable to get file self name for default file transform!');
		$this->assertEquals($activeRecord->getFileFullName($defaultFileTransformName), $activeRecord->getFileFullName(), 'Unable to get file full name for default file transform!');
		$this->assertEquals($activeRecord->getFileContent($defaultFileTransformName), $activeRecord->getFileContent(), 'Unable to get file content for default file transform!');
		$this->assertEquals($activeRecord->getFileUrl($defaultFileTransformName), $activeRecord->getFileUrl(), 'Unable to get file URL for default file transform!');
	}
}
