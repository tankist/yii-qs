<?php

/**
 * Test case for the extension "qs.utils.QsFormatter".
 * @see QsFormatter
 */
class QsFormatterTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.utils.QsFormatter');
	}

	public function testFormatEval() {
		$formatter = new QsFormatter();

		$phpCode = "trim(' test ');";

		$formattedValue = $formatter->formatEval($phpCode);
		$expectedFormattedValue = eval('return '.$phpCode);

		$this->assertEquals($formattedValue, $expectedFormattedValue, 'Unable to perform format eval!');
	}

	public function testFormatEvalView() {
		$formatter = new QsFormatter();

		$testExpressionPattern = '<h1>Test var = "{value}"</h1>';
		$testValue = 'Test value';
		$testExpression = str_replace('{value}', '<?php echo("'.$testValue.'"); ?>', $testExpressionPattern);

		$expectedFormatResult = str_replace('{value}', $testValue, $testExpressionPattern);

		$formatResult = $formatter->formatEvalView($testExpression);

		$this->assertEquals($expectedFormatResult, $formatResult, 'Unable to perform format eval of view!');
	}
}
