<?php

/**
 * Test case for the extension "qs.files.convert.QsFileConvertorImageMagick".
 * @see QsFileConvertorImageMagick
 */
class QsFileConvertorImageMagickTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.convert.*');
	}

	public function setUp() {
		$testFilePath = $this->getTestFilePath();
		if (!file_exists($testFilePath)) {
			mkdir($testFilePath, 0777, true);
		}
	}

	public function tearDown() {
		$testFilePath = $this->getTestFilePath();
		if (file_exists($testFilePath)) {
			exec("rm -rf {$testFilePath}");
		}
	}

	protected function getTestFilePath() {
		$testFilePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.get_class($this);
		return $testFilePath;
	}

	// Tests:

	public function testSetGet() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testBinPath = '/test/bin/path';
		$this->assertTrue($fileConvertor->setBinPath($testBinPath), 'Unable to set bin path!');
		$this->assertEquals($fileConvertor->getBinPath(), $testBinPath, 'Unable to set bin path correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetFileInfo() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testFileName = Yii::getPathOfAlias('system.gii.assets.images').DIRECTORY_SEPARATOR.'logo.png';
		//$testFileName = Yii::getPathOfAlias('system.zii.widgets.assets.gridview').DIRECTORY_SEPARATOR.'bg.gif';

		$fileInfo = $fileConvertor->getFileInfo($testFileName);

		$this->assertTrue(is_array($fileInfo), 'Unable to get file info!');
		$this->assertTrue(array_key_exists('imageSize', $fileInfo), 'File info does not contain image size!');
	}

	/**
	 * @depends testGetFileInfo
	 */
	public function testGetFileInfoError() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testFileName = __FILE__;

		$this->setExpectedException('CException');

		$fileInfo = $fileConvertor->getFileInfo($testFileName);
	}

	/**
	 * @depends testGetFileInfo
	 */
	public function testConvertFile() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testSrcFileName = Yii::getPathOfAlias('system.gii.assets.images').DIRECTORY_SEPARATOR.'logo.png';
		$testOutputFileName = $this->getTestFilePath().DIRECTORY_SEPARATOR.'test.jpg';

		$this->assertTrue($fileConvertor->convert($testSrcFileName, $testOutputFileName), 'Unable to convert file!');
		$this->assertTrue(file_exists($testOutputFileName), 'Output file does not exist!');

		$outputFileInfo = $fileConvertor->getFileInfo($testOutputFileName);
		$this->assertTrue(is_array($outputFileInfo), 'Unable to get info from the output file!');
	}

	/**
	 * @depends testConvertFile
	 */
	public function testResize() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testSrcFileName = Yii::getPathOfAlias('system.gii.assets.images').DIRECTORY_SEPARATOR.'logo.png';
		$testOutputFileName = $this->getTestFilePath().DIRECTORY_SEPARATOR.'test.jpg';

		$srcFileInfo = $fileConvertor->getFileInfo($testSrcFileName);
		$srcImageWidth = $srcFileInfo['imageSize']['width'];
		$srcImageHeight = $srcFileInfo['imageSize']['height'];

		$outputImageWidth = ceil($srcImageWidth/2);
		$outputImageHeight = ceil($srcImageHeight/2);

		$testConvertOptions = array(
			'resize' => array($outputImageWidth, $outputImageHeight)
		);

		$this->assertTrue($fileConvertor->convert($testSrcFileName, $testOutputFileName, $testConvertOptions), 'Unable to convert file with resize option!');
		$this->assertTrue(file_exists($testOutputFileName), 'Output file does not exist!');

		$outputFileInfo = $fileConvertor->getFileInfo($testOutputFileName);
		$actualOutputFileImageWidth = $outputFileInfo['imageSize']['width'];
		$actualOutputFileImageHeight = $outputFileInfo['imageSize']['height'];
		$this->assertEquals($outputImageWidth, $actualOutputFileImageWidth, 'Output image has wrong width!');
		$this->assertEquals($outputImageHeight, $actualOutputFileImageHeight, 'Output image has wrong height!');
	}

	/**
	 * @depends testResize
	 */
	public function testResizeShortcutMethod() {
		$fileConvertor = new QsFileConvertorImageMagick();

		$testSrcFileName = Yii::getPathOfAlias('system.gii.assets.images').DIRECTORY_SEPARATOR.'logo.png';
		$testOutputFileName = $this->getTestFilePath().DIRECTORY_SEPARATOR.'test.jpg';

		$srcFileInfo = $fileConvertor->getFileInfo($testSrcFileName);
		$srcImageWidth = $srcFileInfo['imageSize']['width'];
		$srcImageHeight = $srcFileInfo['imageSize']['height'];

		$outputImageWidth = ceil($srcImageWidth/2);
		$outputImageHeight = ceil($srcImageHeight/2);

		$this->assertTrue($fileConvertor->resize($testSrcFileName, $testOutputFileName, $outputImageWidth, $outputImageHeight), 'Unable to resize image file!');
		$this->assertTrue(file_exists($testOutputFileName), 'Output file does not exist!');

		$outputFileInfo = $fileConvertor->getFileInfo($testOutputFileName);
		$actualOutputFileImageWidth = $outputFileInfo['imageSize']['width'];
		$actualOutputFileImageHeight = $outputFileInfo['imageSize']['height'];
		$this->assertEquals($outputImageWidth, $actualOutputFileImageWidth, 'Output image has wrong width!');
		$this->assertEquals($outputImageHeight, $actualOutputFileImageHeight, 'Output image has wrong height!');
	}
}
