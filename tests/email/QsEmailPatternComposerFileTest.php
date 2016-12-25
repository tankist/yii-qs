<?php

/**
 * Test case for the extension "qs.email.includes.composers.QsEmailPatternComposerFile".
 * @see QsEmailPatternComposerFile
 */
class QsEmailPatternComposerFileTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.composers.*');
	}

	public function setUp() {
		$testFileSourcePath = Yii::getPathOfAlias('application').'/runtime';
		mkdir("{$testFileSourcePath}/test_emails", 0777);
	}

	public function tearDown() {
		$testFileSourcePath = Yii::getPathOfAlias('application').'/runtime';
		$command = "rm -R {$testFileSourcePath}/test_emails";
		exec($command);
	}

	public function testCreate() {
		$emailComposer = new QsEmailPatternComposerFile();
		$this->assertTrue( is_object($emailComposer) );
	}

	/**
	 * @depends testCreate
	 */
	public function testCompose() {
		$emailComposer = new QsEmailPatternComposerFile();

		$testEmailPattern = new QsEmailPattern();

		$testSourcePath = Yii::app()->getBasePath().'/runtime/test_emails';

		$testPatternName = 'test_pattern_name';
		$testPatternFilePath = $testSourcePath.'/'.$testPatternName;
		mkdir($testPatternFilePath, 0777);

		$testFromNameSourceFileName = $testPatternFilePath.'/fromName.php';
		$testFromNameContent = 'Test Pattern FromName Content';
		file_put_contents($testFromNameSourceFileName, $testFromNameContent);
		$testEmailPattern->setFromName($testFromNameSourceFileName);

		$testFromEmailSourceFileName = $testPatternFilePath.'/fromEmail.php';
		$testFromEmailContent = 'Test Pattern FromEmail Content';
		file_put_contents($testFromEmailSourceFileName, $testFromEmailContent);
		$testEmailPattern->setFromEmail($testFromEmailSourceFileName);

		$testSubjectSourceFileName = $testPatternFilePath.'/subject.php';
		$testSubjectContent = 'Test Pattern Subject Content';
		file_put_contents($testSubjectSourceFileName, $testSubjectContent);
		$testEmailPattern->setSubject($testSubjectSourceFileName);

		$testBodyHtmlSourceFileName = $testPatternFilePath.'/bodyHtml.php';
		$testBodyHtmlContent = 'Test Pattern Body Html Content';
		file_put_contents($testBodyHtmlSourceFileName, $testBodyHtmlContent);
		$testEmailPattern->setBodyHtml($testBodyHtmlSourceFileName);

		$composedEmailPattern = $emailComposer->compose($testEmailPattern);

		$this->assertEquals($composedEmailPattern->getFromName(), $testFromNameContent, 'Unable to compose fromName!');
		$this->assertEquals($composedEmailPattern->getFromEmail(), $testFromEmailContent, 'Unable to compose fromEmail!');
		$this->assertEquals($composedEmailPattern->getSubject(), $testSubjectContent, 'Unable to compose subject!');

		$this->assertContains($testBodyHtmlContent, $composedEmailPattern->getBodyHtml(), 'Composed bodyHtml does not content original bodyHtml!');
	}
}
