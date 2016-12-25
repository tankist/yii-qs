<?php

/**
 * Test case for the extension "qs.email.includes.composers.QsEmailPatternComposerEval".
 * @see QsEmailPatternComposerEval
 */
class QsEmailPatternComposerEvalTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.composers.*');
	}

	public function testCreate() {
		$emailComposer = new QsEmailPatternComposerEval();
		$this->assertTrue(is_object($emailComposer));
	}

	public function testComposePlainHtml() {
		$emailComposer = new QsEmailPatternComposerEval();

		$testEmailPattern = new QsEmailPattern();

		$composedEmailPattern = $emailComposer->compose($testEmailPattern);
		$this->assertInstanceOf('QsEmailPattern', $composedEmailPattern, 'Unable to compose EmailPattern!');

		$testBodyHtml = 'Test body Html';
		$testEmailPattern->setBodyHtml($testBodyHtml);

		$composedEmailPattern = $emailComposer->compose($testEmailPattern);

		$composedBodyHtml = $composedEmailPattern->getBodyHtml();
		$this->assertFalse(empty($composedBodyHtml), 'Empty composed HTML!');
		
		$this->assertContains($testEmailPattern->bodyHtml, $composedEmailPattern->bodyHtml, 'Simple text has been lost while compose!');
	}

	public function testComposeHtmlWithPhpCode() {
		$emailComposer = new QsEmailPatternComposerEval();

		$testEmailPattern = new QsEmailPattern();

		$testBodyPattern = 'Test var = "{value}"';
		$testValue = 'Test value';
		$testBodyWithPhpCode = str_replace('{value}', '<?php echo("'.$testValue.'"); ?>', $testBodyPattern);

		$testEmailPattern->setBodyHtml($testBodyWithPhpCode);
		$composedEmailPattern = $emailComposer->compose($testEmailPattern);

		$expectedComposedFragment = str_replace('{value}', $testValue, $testBodyPattern);

		$this->assertContains($expectedComposedFragment, $composedEmailPattern->bodyHtml, 'Unable to compose the bodyHtml with PHP code!');
	}

	public function testComposeHtmlWithParams() {
		$emailComposer = new QsEmailPatternComposerEval();

		$testEmailPattern = new QsEmailPattern();

		$testVarName = 'testVarName';
		$testVarValue = 'Test value';
		$testBodyPattern = 'Test var = "'.$testVarName.'"';
		$testBodyWithPhpCode = str_replace($testVarName, '<?php echo($'.$testVarName.'); ?>', $testBodyPattern);

		$testEmailPattern->setBodyHtml($testBodyWithPhpCode);
		$composedEmailPattern = $emailComposer->compose($testEmailPattern, array($testVarName=>$testVarValue));

		$expectedComposedFragment = str_replace($testVarName, $testVarValue, $testBodyPattern);

		$this->assertContains($expectedComposedFragment, $composedEmailPattern->bodyHtml, 'Unable to compose the bodyHtml with PHP code with params!');
	}
}
