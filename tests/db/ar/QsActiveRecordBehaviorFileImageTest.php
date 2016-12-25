<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorFileImage".
 * @see QsActiveRecordBehaviorFileImage
 */
class QsActiveRecordBehaviorFileImageTest extends CTestCase {
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
					'imageFileBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorFileImage',
						'fileTransforms' => array(
							'full' => array(800, 600),
							'thumbnail' => array(200, 150)
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
		$behaviorTempFilePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'QsActiveRecordBehaviorFileImage';
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
	 * Returns the test image file path.
	 * @return string test image file full name.
	 */
	protected function getTestImageFileFullName() {
		$fileFullName = YII_PATH . '/cli/views/webapp/css/bg.gif';
		return $fileFullName;
	}

	/**
	 * Creates the file convertor application component mock.
	 * @return IQsFileConvertor file convertor application component mock.
	 */
	protected function createTestImageFileConvertor() {
		Yii::import('qs.files.convert.*');
		$methods = array(
			'setDefaultOptions',
			'getDefaultOptions',
			'getFileInfo',
			'convert',
		);
		$imageFileConvertor = $this->getMock('QsFileConvertor', $methods);
		$imageFileConvertor->expects($this->any())->method('convert')->will($this->throwException(new CException('convert')));
		return $imageFileConvertor;
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorFileImage();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testGetDefaultTransformCallback() {
		$behavior = new QsActiveRecordBehaviorFileImage();
		$defaultTransformCallback = $behavior->getTransformCallback();
		$this->assertFalse(empty($defaultTransformCallback), 'Unable to get default transform callback!');
	}

	/**
	 * @depends testGetDefaultTransformCallback
	 */
	public function testSaveFile() {
		$activeRecordFinder = $this->getActiveRecordFinder();
		$imageTransforms = $activeRecordFinder->getFileTransforms();
		$this->assertFalse(empty($imageTransforms), 'Empty image sizes for the test active record class!');

		$activeRecord = $activeRecordFinder->find(null);

		$testFileName = $this->getTestImageFileFullName();
		$testFileExtension = CFileHelper::getExtension($testFileName);

		$this->assertTrue($activeRecord->saveFile($testFileName), 'Unable to save file!');

		$refreshedActiveRecord = $activeRecordFinder->findByPk($activeRecord->getPrimaryKey());

		foreach ($imageTransforms as $imageTransformName => $imageTransform) {
			$returnedFileFullName = $activeRecord->getFileFullName($imageTransformName);
			$fileStorageBucket = $activeRecord->getFileStorageBucket();

			$this->assertTrue($fileStorageBucket->fileExists($returnedFileFullName), "File for transformation name '{$imageTransformName}' does not exist!");
			$this->assertEquals(CFileHelper::getExtension($returnedFileFullName), $testFileExtension, 'Saved file has wrong extension!');

			$this->assertEquals($refreshedActiveRecord->getFileFullName($imageTransformName), $returnedFileFullName, 'Wrong full file name from the refreshed record!');
		}
	}

	/**
	 * @depends testSaveFile
	 */
	public function testTransformFileViaApplicationComponent() {
		$testImageFileConvertorComponentName = 'testImageFileConvertor_'.get_class($this);
		$testImageFileConvertor = $this->createTestImageFileConvertor();
		Yii::app()->setComponent($testImageFileConvertorComponentName, $testImageFileConvertor);

		$activeRecordFinder = $this->getActiveRecordFinder();

		$activeRecord = $activeRecordFinder->find(null);
		$activeRecord->imageFileConvertorComponentName = $testImageFileConvertorComponentName;

		$testFileName = $this->getTestImageFileFullName();

		$this->setExpectedException('CException', 'convert');

		$activeRecord->saveFile($testFileName);
	}

	/**
	 * @depends testSaveFile
	 */
	public function testTransformFileViaInnerCode() {
		$activeRecordFinder = $this->getActiveRecordFinder();
		$imageTransforms = $activeRecordFinder->getFileTransforms();

		$activeRecord = $activeRecordFinder->find(null);
		$activeRecord->imageFileConvertorComponentName = 'unexistingComponentName_'.getmypid();

		$testFileName = $this->getTestImageFileFullName();
		$testFileExtension = CFileHelper::getExtension($testFileName);

		$this->assertTrue($activeRecord->saveFile($testFileName), 'Unable to save file!');

		$refreshedActiveRecord = $activeRecordFinder->findByPk($activeRecord->getPrimaryKey());

		foreach ($imageTransforms as $imageTransformName => $imageTransform) {
			$returnedFileFullName = $activeRecord->getFileFullName($imageTransformName);
			$fileStorageBucket = $activeRecord->getFileStorageBucket();

			$this->assertTrue($fileStorageBucket->fileExists($returnedFileFullName), "File for transformation name '{$imageTransformName}' does not exist!");
			$this->assertEquals(CFileHelper::getExtension($returnedFileFullName), $testFileExtension, 'Saved file has wrong extension!');

			$this->assertEquals($refreshedActiveRecord->getFileFullName($imageTransformName), $returnedFileFullName, 'Wrong full file name from the refreshed record!');
		}
	}
}
