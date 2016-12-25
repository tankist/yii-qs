<?php

/**
 * Test case for the extension "qs.email.includes.storages.QsEmailPatternStorageFile".
 * @see QsEmailPatternStorageFile
 */
class QsEmailPatternStorageFileTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.storages.*');
	}

	public function setUp() {
		$testFileSourcePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'test_emails';
		mkdir($testFileSourcePath, 0777);
	}

	public function tearDown() {
		$testFileSourcePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'test_emails';
		$command = "rm -R {$testFileSourcePath}";
		exec($command);
	}

	// Tests:

	public function testCreate() {
		$emailPatternStorage = new QsEmailPatternStorageFile();
		$this->assertTrue(is_object($emailPatternStorage));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetup() {
		$emailPatternStorage = new QsEmailPatternStorageFile();

		$testSourcePath = 'test/pattern/path';
		$this->assertTrue($emailPatternStorage->setSourcePath($testSourcePath), 'Unable to set source path!');
		$this->assertEquals($emailPatternStorage->getSourcePath(), $testSourcePath, 'Unable to set source path correctly!');

		$testFillType = 'test_fill_type';
		$this->assertTrue($emailPatternStorage->setFillType($testFillType), 'Unable to set fill type!');
		$this->assertEquals($emailPatternStorage->getFillType(), $testFillType, 'Unable to set fill type correctly!');

		$testFileExtension = 'test_file_extension';
		$this->assertTrue($emailPatternStorage->setFileExtension($testFileExtension), 'Unable to set file extension!');
		$this->assertEquals($emailPatternStorage->getFileExtension(), $testFileExtension, 'Unable to set file extension correctly!');
	}

	/**
	 * @depends testSetup
	 */
	public function testGetPatternFileName() {
		$emailPatternStorage = new QsEmailPatternStorageFile();

		$emailPatternStorage->setFillType(QsEmailPatternStorageFile::FILL_TYPE_FILE_NAME);

		$testSourcePath = Yii::app()->getBasePath().'/runtime/test_emails';
		$emailPatternStorage->setSourcePath($testSourcePath);

		$testPatternName = 'test_pattern_name';
		$testPatternFilePath = $testSourcePath.'/'.$testPatternName;
		mkdir($testPatternFilePath, 0777);
		$testSubjectSourceFileName = $testPatternFilePath.'/subject.'.$emailPatternStorage->getFileExtension();
		file_put_contents($testSubjectSourceFileName, 'Test Pattern Subject');
		$testBodyHtmlSourceFileName = $testPatternFilePath.'/bodyHtml.'.$emailPatternStorage->getFileExtension();
		file_put_contents($testBodyHtmlSourceFileName, 'Test Pattern Body Html');

		$emailPattern = $emailPatternStorage->getPattern($testPatternName);
		$this->assertInstanceOf('QsEmailPattern', $emailPattern, 'Could not get email pattern object!');

		$this->assertEquals($emailPattern->subject, $testSubjectSourceFileName, 'Unable to set subject as file name!');
		$this->assertEquals($emailPattern->bodyHtml, $testBodyHtmlSourceFileName, 'Unable to set bodyHtml as file name!');
	}

	/**
	 * @depends testSetup
	 */
	public function testGetPatternContent() {
		$emailPatternStorage = new QsEmailPatternStorageFile();

		$emailPatternStorage->setFillType(QsEmailPatternStorageFile::FILL_TYPE_CONTENT);

		$testSourcePath = Yii::app()->getBasePath().'/runtime/test_emails';
		$emailPatternStorage->setSourcePath($testSourcePath);

		$testPatternName = 'test_pattern_name_content';
		$testPatternFilePath = $testSourcePath.'/'.$testPatternName;
		mkdir($testPatternFilePath, 0777);

		$testSubjectSourceFileName = $testPatternFilePath.'/subject.'.$emailPatternStorage->getFileExtension();
		$testSubjectContent = 'Test Pattern Subject Content';
		file_put_contents($testSubjectSourceFileName, $testSubjectContent);
		$testBodyHtmlSourceFileName = $testPatternFilePath.'/bodyHtml.'.$emailPatternStorage->getFileExtension();
		$testBodyHtmlContent = 'Test Pattern Body Html Content';
		file_put_contents($testBodyHtmlSourceFileName, $testBodyHtmlContent);

		$emailPattern = $emailPatternStorage->getPattern($testPatternName);
		$this->assertInstanceOf('QsEmailPattern', $emailPattern, 'Could not get email pattern object!');

		$this->assertEquals($emailPattern->subject, $testSubjectContent, 'Unable to set subject as file content!');
		$this->assertEquals($emailPattern->bodyHtml, $testBodyHtmlContent, 'Unable to set bodyHtml as file content!');
	}

	/**
	 * @depends testGetPatternFileName
	 */
	public function testGetMissingPattern() {
		$emailPatternStorage = new QsEmailPatternStorageFile();

		$testPatternName = 'unexisting_pattern_name';

		$this->setExpectedException('CException');

		$emailPattern = $emailPatternStorage->getPattern($testPatternName);
	}
}
