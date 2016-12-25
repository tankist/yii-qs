<?php
 
/**
 * Test case for the extension "qs.i18n.images.QsImageTranslationSource".
 * @see QsImageTranslationSource
 */
class QsImageTranslationSourceTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.images.*');
	}

	/**
	 * Creates the test image translation source instance
	 * @return QsImageTranslationSource image translation source instance
	 */
	protected function createImageTranslationSource($loadImageUrlResult=null) {
		$methodList = array(
			'loadImageTranslation',
			'imageTranslationExists',
			'saveImageTranslation',
		);
		$imageTranslationSource = $this->getMock('QsImageTranslationSource', $methodList);
		$imageTranslationSource->expects($this->any())->method('loadImageTranslation')->will($this->returnValue($loadImageUrlResult));
		return $imageTranslationSource;
	}

	public function testLanguageSetup() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testLanguage = 'test';
		$this->assertTrue($imageTranslationSource->setLanguage($testLanguage), 'Unable to set language!');
		$this->assertEquals($testLanguage, $imageTranslationSource->getLanguage(), 'Unable to set language correctly!');
	}

	/**
	 * @depends testLanguageSetup
	 */
	public function testGetDefaultLanguage() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$defaultLanguage = $imageTranslationSource->getLanguage();
		$this->assertFalse(empty($defaultLanguage), 'Unable to get default language!');

		$expectedDefaultLanguage = Yii::app()->sourceLanguage;
		$this->assertEquals($expectedDefaultLanguage, $defaultLanguage, 'Unable to get default language correctly!');
	}

	public function testGetNormalizedDefaultBaseUrlFromRelativeUrl() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testDefaultBaseUrl = 'test/images';
		$imageTranslationSource->defaultBaseUrl = $testDefaultBaseUrl;

		$normalizedDefaultBaseUrl = $imageTranslationSource->getNormalizedDefaultBaseUrl();
		$expectedNormalizedDefaultBaseUrl = Yii::app()->request->getBaseUrl().'/'.$testDefaultBaseUrl;

		$this->assertEquals($expectedNormalizedDefaultBaseUrl, $normalizedDefaultBaseUrl, 'Base URL normalized from relative URL is wrong!');
	}

	public function testGetNormalizedDefaultBaseUrlFromAbsoluteUrl() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testDefaultBaseUrl = 'http://testdomain.com/test/images';
		$imageTranslationSource->defaultBaseUrl = $testDefaultBaseUrl;

		$normalizedDefaultBaseUrl = $imageTranslationSource->getNormalizedDefaultBaseUrl();
		$this->assertEquals($testDefaultBaseUrl, $normalizedDefaultBaseUrl, 'Base URL normalized from absolute URL is wrong!');
	}

	public function testGetNormalizedDefaultBasePathFromRelativePath() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testDefaultBasePath = 'test/images';
		$imageTranslationSource->defaultBasePath = $testDefaultBasePath;

		$normalizedDefaultBasePath = $imageTranslationSource->getNormalizedDefaultBasePath();
		$expectedNormalizedDefaultBasePath = Yii::getPathOfAlias('webroot').DIRECTORY_SEPARATOR.$testDefaultBasePath;

		$this->assertEquals($expectedNormalizedDefaultBasePath, $normalizedDefaultBasePath, 'Base path normalized from relative path is wrong!');
	}

	public function testGetNormalizedDefaultBasePathFromAbsolutePath() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$testDefaultBasePath = '/home/test/images';
		$imageTranslationSource->defaultBasePath = $testDefaultBasePath;

		$normalizedDefaultBasePath = $imageTranslationSource->getNormalizedDefaultBasePath();
		$this->assertEquals($testDefaultBasePath, $normalizedDefaultBasePath, 'Base path normalized from absolute path is wrong!');
	}

	/**
	 * @depends testGetNormalizedDefaultBaseUrlFromRelativeUrl
	 * @depends testGetNormalizedDefaultBaseUrlFromAbsoluteUrl
	 */
	public function testGetDefaultImageUrl() {
		$imageTranslationSource = $this->createImageTranslationSource();

		$defaultBaseUrl = 'http://testdomain.com/test/images';
		$imageTranslationSource->defaultBaseUrl = $defaultBaseUrl;

		$testImageName = 'test_image.jpg';

		$defaultImageUrl = $imageTranslationSource->getDefaultImageUrl($testImageName);
		$expectedDefaultImageUrl = $defaultBaseUrl.'/'.$testImageName;

		$this->assertEquals($expectedDefaultImageUrl, $defaultImageUrl, 'Wrong default image URL!');
	}

	/**
	 * @depends testGetDefaultImageUrl
	 */
	public function testTranslateImage() {
		$testImageUrl = 'http://testdoamain.com/test_image.jpg';
		$imageTranslationSource = $this->createImageTranslationSource($testImageUrl);
		$imageTranslationSource->forceTranslation = true;
		$imageTranslationSource->checkTranslationExists = false;

		$testImageName = 'some_test_image.jpg';

		$returnedImageUrl = $imageTranslationSource->translate($testImageName);

		$this->assertEquals($testImageUrl, $returnedImageUrl, 'Unable to translate image!');
	}

	/**
	 * @depends testTranslateImage
	 */
	public function testMissingTranslation() {
		$imageTranslationSource = $this->createImageTranslationSource(null);
		$imageTranslationSource->forceTranslation = true;
		$imageTranslationSource->checkTranslationExists = true;

		$testImageName = 'some_test_image.jpg';

		$returnedImageUrl = $imageTranslationSource->translate($testImageName);
		$defaultImageUrl = $imageTranslationSource->getDefaultImageUrl($testImageName);

		$this->assertEquals($defaultImageUrl, $returnedImageUrl, 'Unable to translate image, while translation is missing!');
	}
}
