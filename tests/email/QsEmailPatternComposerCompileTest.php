<?php

/**
 * Test case for the extension "qs.email.includes.composers.QsEmailPatternComposerCompile".
 * @see QsEmailPatternComposerCompile
 */
class QsEmailPatternComposerCompileTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.composers.*');
	}

	public function setUp() {
		$testEmailFilePath = self::getTestEmailFilePath();
		mkdir($testEmailFilePath, 0777);
	}

	public function tearDown() {
		$testEmailFilePath = self::getTestEmailFilePath();
		if (file_exists($testEmailFilePath)) {
			CFileHelper::removeDirectory($testEmailFilePath);
		}
	}

	/**
	 * @return string test email compile file path.
	 */
	protected static function getTestEmailFilePath() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . '_' . getmypid();
	}

	/**
	 * @return QsEmailPatternComposerCompile test email pattern composer instance.
	 */
	protected function createTestEmailPatternComposer() {
		$emailComposer = new QsEmailPatternComposerCompile();
		$emailComposer->setCompilePath(self::getTestEmailFilePath());
		return $emailComposer;
	}

	// Tests:

	public function testCreate() {
		$emailComposer = new QsEmailPatternComposerCompile();
		$this->assertTrue(is_object($emailComposer));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGetCompile() {
		$emailComposer = new QsEmailPatternComposerCompile();

		$testCompilePath = 'test_compile_dir';
		$this->assertTrue($emailComposer->setCompilePath($testCompilePath), 'Can not set compilePath!');
		$this->assertEquals($emailComposer->getCompilePath(), $testCompilePath, 'Can not set compilePath correctly!');

		$testFilePermissions = 0777;
		$this->assertTrue($emailComposer->setFilePermission($testFilePermissions), 'Can not set file permissions!');
		$this->assertEquals($emailComposer->getFilePermission(), $testFilePermissions, 'Can not set file permissions correctly!');

		$testLeftDelimiter = 'test_left_delimiter';
		$this->assertTrue($emailComposer->setLeftDelimiter($testLeftDelimiter), 'Can not set leftDelimiter!');
		$this->assertEquals($emailComposer->getLeftDelimiter(), $testLeftDelimiter, 'Can not set leftDelimiter correctly!');

		$testRightDelimiter = 'test_right_delimiter';
		$this->assertTrue($emailComposer->setRightDelimiter($testRightDelimiter), 'Can not set rightDelimiter!');
		$this->assertEquals($emailComposer->getRightDelimiter(), $testRightDelimiter, 'Can not set rightDelimiter correctly!');
	}

	/**
	 * @depends testSetGetCompile
	 */
	public function testCompileText() {
		$emailComposer = new QsEmailPatternComposerCompile();

		$testVarName = 'test_var_name';
		$testPlaceholder = $emailComposer->getLeftDelimiter().$testVarName.$emailComposer->getRightDelimiter();
		$testText = 'Some text '.$testPlaceholder.' ends.';
		//$testText = $testPlaceholder;
		$compiledText = $emailComposer->compileText($testText);

		$expectedCompiledTag = '<?php echo nl2br(htmlspecialchars($'.$testVarName.')); ?>';
		$expectedCompiledText = str_replace($testPlaceholder, $expectedCompiledTag, $testText);

		$this->assertEquals($compiledText, $expectedCompiledText, 'Unable to compile text with single variable placeholder!');
	}

	/**
	 * @depends testCompileText
	 */
	public function testCompileUnsafeText() {
		$emailComposer = new QsEmailPatternComposerCompile();

		$varPrefix = '$';
		$testVarName = 'test_var_name';
		$testPlaceholder = $emailComposer->getLeftDelimiter().$varPrefix.$testVarName.$emailComposer->getRightDelimiter();
		$testText = 'Some text '.$testPlaceholder.' ends.';
		//$testText = $testPlaceholder;
		$compiledText = $emailComposer->compileText($testText);

		$expectedCompiledTag = '<?php echo $'.$testVarName.'; ?>';
		$expectedCompiledText = str_replace($testPlaceholder, $expectedCompiledTag, $testText);

		$this->assertEquals($compiledText, $expectedCompiledText, 'Unable to compile text with single variable placeholder!');
	}

	/**
	 * @depends testCompileText
	 */
	public function testCompileEmailPattern() {
		$emailComposer = $this->createTestEmailPatternComposer();

		$testEmailPattern = new QsEmailPattern();
		$testEmailPatternId = 'test_email_pattern_id';
		$testEmailPattern->setId($testEmailPatternId);
		$testBodyHtml = '<html>Test Body Html with {name}</html>';
		$testEmailPattern->setBodyHtml($testBodyHtml);

		$this->assertTrue($emailComposer->compilePattern($testEmailPattern), 'Unable to compile email pattern!');
	}

	/**
	 * @depends testCompileEmailPattern
	 */
	public function testComposeEmailPattern() {
		$emailComposer = $this->createTestEmailPatternComposer();

		$testEmailPattern = new QsEmailPattern();
		$testEmailPatternId = 'test_email_pattern_compose_id';
		$testEmailPattern->setId($testEmailPatternId);

		$testSubjectVarName = 'test_subject_var_name';
		$testSubjectVarValue = 'test subject var value';
		$testSubjectPlaceholder = $emailComposer->getLeftDelimiter().$testSubjectVarName.$emailComposer->getRightDelimiter();
		$testSubject = 'Test subject with '.$testSubjectPlaceholder.' placeholder';
		$testEmailPattern->setSubject($testSubject);
		$expectedComposedSubject = str_replace($testSubjectPlaceholder, $testSubjectVarValue, $testSubject);

		$testFromVarName = 'test_from_var_name';
		$testFromVarValue = 'test from var value';
		$testFromPlaceholder = $emailComposer->getLeftDelimiter().$testFromVarName.$emailComposer->getRightDelimiter();
		$testFrom = 'Test From <'.$testFromPlaceholder.'>';
		$testEmailPattern->setFrom($testFrom);
		$expectedComposedFrom = str_replace($testFromPlaceholder, $testFromVarValue, $testFrom);

		$testBodyVarName = 'test_body_var_name';
		$testBodyVarValue = 'test body var value';
		$testBodyPlaceholder = $emailComposer->getLeftDelimiter().$testBodyVarName.$emailComposer->getRightDelimiter();
		$testBodyHtml = 'Test Body Html with '.$testBodyPlaceholder.' value.';
		$testEmailPattern->setBodyHtml($testBodyHtml);
		$expectedComposedBodyHtml = str_replace($testBodyPlaceholder, $testBodyVarValue, $testBodyHtml);

		$testBodyText = 'Test Body text with '.$testBodyPlaceholder.' value.';
		$testEmailPattern->setBodyText($testBodyText);
		$expectedComposedBodyText = str_replace($testBodyPlaceholder, $testBodyVarValue, $testBodyText);

		$testData = array(
			$testSubjectVarName => $testSubjectVarValue,
			$testFromVarName => $testFromVarValue,
			$testBodyVarName => $testBodyVarValue
		);
		$composedEmailPattern = $emailComposer->compose($testEmailPattern, $testData);

		$this->assertEquals($expectedComposedSubject, $composedEmailPattern->getSubject(), 'Unable to compose email pattern subject with compile!');
		$this->assertEquals($expectedComposedFrom, $composedEmailPattern->getFrom(), 'Unable to compose email pattern from with compile!');

		$composedBodyHtml = $composedEmailPattern->getBodyHtml();
		$this->assertFalse(empty($composedBodyHtml), 'Composed by compile bodyHtml is empty!');
		$this->assertContains($expectedComposedBodyHtml, $composedEmailPattern->getBodyHtml(), 'Unable to compose email pattern bodyHtml with compile!');

		$this->assertContains($expectedComposedBodyText, $composedEmailPattern->getBodyText(), 'Unable to compose email pattern bodyText with compile!');
	}
}
