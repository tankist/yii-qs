<?php
 
/**
 * Test case for the extension "qs.i18n.images.QsImageTranslationSourceFileSystem".
 * @see QsImageTranslationSourceFileSystem
 */
class QsImageTranslationSourceFileSystemTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.images.*');
	}

	public function tearDown() {
		$path = $this->getTestImageTranslationSourceBasePath();
		if (file_exists($path)) {
			$command = "rm -rf {$path}";
			exec($command);
		}
	}

	/**
	 * Creates the test image translation source instance
	 * @return QsImageTranslationSourceFileSystem image translation source instance
	 */
	protected function createImageTranslationSource() {
		$imageTranslationSourceConfig = array(
			'class' => 'QsImageTranslationSourceFileSystem',
			'basePath' => $this->getTestImageTranslationSourceBasePath(),
			'baseUrl' => 'http://test/base/url'
		);
		$imageTranslationSource = Yii::createComponent($imageTranslationSourceConfig);
		return $imageTranslationSource;
	}

	/**
	 * Returns test image translation base path.
	 * @return string image translation base path.
	 */
	protected function getTestImageTranslationSourceBasePath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.get_class($this);
	}

	// Tests:

	public function testSetGet() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testBasePath = '/test/base/path';
		$this->assertTrue($imageTranslationSource->setBasePath($testBasePath), 'Unable to set base path!');
		$this->assertEquals($testBasePath, $imageTranslationSource->getBasePath(), 'Unable to set base path correctly!');

		$testBaseUrl = 'http://test/base/url';
		$this->assertTrue($imageTranslationSource->setBaseUrl($testBaseUrl), 'Unable to set base URL!');
		$this->assertEquals($imageTranslationSource->getBaseUrl(), $testBaseUrl, 'Unable to set base URL correctly!');

		$testFilePermission = 0777;
		$this->assertTrue($imageTranslationSource->setFilePermission($testFilePermission), 'Unable to set file permission!');
		$this->assertEquals($imageTranslationSource->getFilePermission(), $testFilePermission, 'Unable to set file permission correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetFullFileName() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testBasePath = '/test/base/path';
		$imageTranslationSource->setBasePath($testBasePath);

		$testFileName = 'test_file.jpg';
		$testLanguage = 'test_lang';
		$fullFileName = $imageTranslationSource->getFullFileName($testFileName, $testLanguage);

		$this->assertFalse(empty($fullFileName), 'Unable to get full file name!');

		$expectedFullFileName = $testBasePath.DIRECTORY_SEPARATOR.$testLanguage.DIRECTORY_SEPARATOR.$testFileName;
		$this->assertEquals($expectedFullFileName, $fullFileName, 'Unable to get full file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetFullFileUrl() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testBaseUrl = 'http://test/base/path';
		$imageTranslationSource->setBaseUrl($testBaseUrl);

		$testFileName = 'test_file.jpg';
		$testLanguage = 'test_lang';
		$fullFileUrl = $imageTranslationSource->getFullFileUrl($testFileName, $testLanguage);

		$this->assertFalse(empty($fullFileUrl), 'Unable to get full file URL!');

		$expectedFullFileUrl = $testBaseUrl.'/'.$testLanguage.'/'.$testFileName;
		$this->assertEquals($expectedFullFileUrl, $fullFileUrl, 'Unable ti get full file URL!');
	}

	/**
	 * @depends testGetFullFileName
	 * @depends testGetFullFileUrl
	 */
	public function testTranslateImage() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'test_image.jpg';
		$testImageFileContent = 'test image file content';

		$path = $this->getTestImageTranslationSourceBasePath().DIRECTORY_SEPARATOR.$testLanguage;
		if (!file_exists($path)) {
			mkdir($path,0777,true);
		}
		$fileName = $path.DIRECTORY_SEPARATOR.$testImageFileName;
		file_put_contents($fileName, $testImageFileContent);

		$translatedImageUrl = $imageTranslationSource->translate($testImageFileName, $testLanguage);
		$expectedTranslatedImageUrl = $imageTranslationSource->getFullFileUrl($testImageFileName, $testLanguage);

		$this->assertEquals($expectedTranslatedImageUrl, $translatedImageUrl, 'Wrong translated image URL!');
	}

	/**
	 * @depends testTranslateImage
	 */
	public function testTranslateImageMissingTranslation() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'unexisting_test_image.jpg';

		$translatedImageUrl = $imageTranslationSource->translate($testImageFileName, $testLanguage);
		$expectedTranslatedImageUrl = $imageTranslationSource->getDefaultImageUrl($testImageFileName);

		$this->assertEquals($expectedTranslatedImageUrl, $translatedImageUrl, 'Wrong translated image URL for missing translation!');
	}

	/**
	 * @depends testGetFullFileName
	 * @depends testGetFullFileUrl
	 */
	public function testTranslationExists() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test_lang';
		$testImageFileName = 'test_image.jpg';

		$this->assertFalse($imageTranslationSource->translationExists($testImageFileName, $testLanguage), 'Missing translation considered as existing one!');

		$testImageFileContent = '';
		$path = $this->getTestImageTranslationSourceBasePath().DIRECTORY_SEPARATOR.$testLanguage;
		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		$fileName = $path.DIRECTORY_SEPARATOR.$testImageFileName;
		file_put_contents($fileName, $testImageFileContent);

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
