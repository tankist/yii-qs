<?php
 
/**
 * Test case for the extension "qs.i18n.images.QsImageTranslationSourceFileStorage".
 * @see QsImageTranslationSourceFileStorage
 */
class QsImageTranslationSourceFileStorageTest extends CTestCase {
	/**
	 * @var IQsFileStorage application file storage component backup.
	 */
	protected $_fileStorageBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.images.*');

		Yii::import('qs.files.storages.*');
		Yii::import('qs.files.storages.filesystem.*');
	}

	public function setUp() {
		// File Storage:
		if (Yii::app()->hasComponent('fileStorage')) {
			$this->_fileStorageBackup = Yii::app()->getComponent('fileStorage');
		}
		$fileStorage = $this->createFileStorage();
		Yii::app()->setComponent('fileStorage', $fileStorage);
	}

	public function tearDown() {
		// File Storage:
		if (is_object($this->_fileStorageBackup)) {
			Yii::app()->setComponent('fileStorage', $this->_fileStorageBackup);
		}
		$testFileStoragePath = $this->getTestFileStorageBasePath();
		$command = "rm -rf {$testFileStoragePath}";
		exec($command);
	}

	/**
	 * Creates the test image translation source instance
	 * @return QsImageTranslationSourceFileStorage image translation source instance
	 */
	protected function createImageTranslationSource() {
		$imageTranslationSourceConfig = array(
			'class' => 'QsImageTranslationSourceFileStorage'
		);
		$imageTranslationSource = Yii::createComponent($imageTranslationSourceConfig);
		return $imageTranslationSource;
	}

	/**
	 * Returns the base path for the test files.
	 * @return string test file base path.
	 */
	protected function getTestFileStorageBasePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.get_class($this).'_fileStorage';
	}

	/**
	 * Returns the file storage component configuration.
	 * @return IQsFileStorage file storage component instance.
	 */
	protected function createFileStorage() {
		$fileStorageConfig = array(
			'class' => 'QsFileStorageFileSystem',
			'basePath' => $this->getTestFileStorageBasePath(),
			'baseUrl' => 'http://www.mydomain.com/files',
			'filePermission' => 0777
		);
		$fileStorage = Yii::createComponent($fileStorageConfig);
		return $fileStorage;
	}

	// Tests:

	public function testSetGet() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testFileStorageComponentName = 'testFileStorageComponentName';
		$this->assertTrue($imageTranslationSource->setFileStorageComponentName($testFileStorageComponentName), 'Unable to set file storage component name!');
		$this->assertEquals($imageTranslationSource->getFileStorageComponentName(), $testFileStorageComponentName, 'Unable to set file storage component name correctly!');

		$testFileStorageBucketName = 'testFileStorageBucketName';
		$this->assertTrue($imageTranslationSource->setFileStorageBucketName($testFileStorageBucketName), 'Unable to set file storage bucket name!');
		$this->assertEquals($imageTranslationSource->getFileStorageBucketName(), $testFileStorageBucketName, 'Unable to set file storage bucket name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetFileStorageBucket() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testBucketName = 'testBucketName';
		Yii::app()->fileStorage->addBucket($testBucketName);

		$imageTranslationSource->setFileStorageBucketName($testBucketName);

		$fileStorageBucket = $imageTranslationSource->getFileStorageBucket();

		$this->assertTrue(is_object($fileStorageBucket), 'Unable to get file storage bucket!');
		$this->assertEquals($testBucketName, $fileStorageBucket->getName(), 'Returned file storage bucket has incorrect name!');
	}

	/**
	 * @depends testGetFileStorageBucket
	 */
	public function testGetFileStorageBucketIfNotExists() {
		$imageTranslationSource = $this->createImageTranslationSource();

		Yii::app()->fileStorage->setBuckets(array());

		$testBucketName = 'testBucketNameWhichNotPresentInStorage';

		$imageTranslationSource->setFileStorageBucketName($testBucketName);

		$fileStorageBucket = $imageTranslationSource->getFileStorageBucket();

		$this->assertTrue(is_object($fileStorageBucket), 'Unable to get file storage bucket!');
		$this->assertEquals($testBucketName, $fileStorageBucket->getName(), 'Returned file storage bucket has incorrect name!');
	}

	/**
	 * @depends testGetFileStorageBucketIfNotExists
	 */
	public function testTranslateImage() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'test_image.jpg';
		$testImageFileContent = 'test image file content';

		$fileStorageBucket = $imageTranslationSource->getFileStorageBucket();
		$bucketFileName = $testLanguage.'/'.$testImageFileName;
		$fileStorageBucket->saveFileContent($bucketFileName, $testImageFileContent);

		$translatedImageUrl = $imageTranslationSource->translate($testImageFileName,$testLanguage);
		$expectedTranslatedImageUrl = $fileStorageBucket->getFileUrl($bucketFileName);

		$this->assertEquals($expectedTranslatedImageUrl, $translatedImageUrl, 'Wrong translated image URL!');
	}

	/**
	 * @depends testTranslateImage
	 */
	public function testTranslateImageMissingTranslation() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'unexisting_test_image.jpg';

		$translatedImageUrl = $imageTranslationSource->translate($testImageFileName,$testLanguage);
		$expectedTranslatedImageUrl = $imageTranslationSource->getDefaultImageUrl($testImageFileName);

		$this->assertEquals($expectedTranslatedImageUrl, $translatedImageUrl, 'Wrong translated image URL for missing translation!');
	}

	/**
	 * @depends testGetFileStorageBucketIfNotExists
	 */
	public function testTranslationExists() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'test_image.jpg';

		$this->assertFalse($imageTranslationSource->translationExists($testImageFileName, $testLanguage), 'Missing translation considered as existing one!');

		$fileStorageBucket = $imageTranslationSource->getFileStorageBucket();
		$fileStorageBucket->copyFileIn(__FILE__, $testLanguage.'/'.$testImageFileName);

		$this->assertTrue($imageTranslationSource->translationExists($testImageFileName, $testLanguage), 'Existing translation considered as missing one!');
	}

	/**
	 * @depends testTranslationExists
	 */
	public function testSaveTranslation() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'test_image.jpg';

		$this->assertTrue($imageTranslationSource->saveTranslation(__FILE__, $testImageFileName, $testLanguage), 'Unable to save translation!');
		$this->assertTrue($imageTranslationSource->translationExists($testImageFileName, $testLanguage), 'Saved translation does not exist!');
	}
}
