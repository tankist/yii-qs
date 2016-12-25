<?php

Yii::import('qs.utils.QsCronJob', true);

/**
 * Test case for the extension "qs.utils.QsCronJob".
 * @see QsCronJob
 */
class QsCronJobTest extends CTestCase {
	/**
	 * Data provider for {@link testValidate}.
	 * @return array test data.
	 */
	public function dataProviderValidate() {
		return array(
			array(
				array(
					'min' => '*',
					'hour' => '*',
					'day' => '*',
					'month' => '*',
					'weekDay' => '*',
					'command' => 'ls',
				),
				true
			),
			array(
				array(
					'min' => '',
					'hour' => '',
					'day' => '',
					'month' => '',
					'weekDay' => '',
					'command' => '',
				),
				false
			),
			array(
				array(
					'min' => '/2',
					'hour' => '/2',
					'day' => '/2',
					'month' => '/2',
					'weekDay' => '/2',
					'command' => 'some',
				),
				true
			),
			array(
				array(
					'min' => '0',
					'hour' => '0',
					'day' => '0',
					'month' => '0',
					'weekDay' => '0',
					'command' => 'some',
				),
				true
			),
		);
	}

	/**
	 * @dataProvider dataProviderValidate
	 *
	 * @param array $attributes
	 * @param boolean $isValid
	 */
	public function testValidate(array $attributes, $isValid) {
		$cronJob = new QsCronJob();
		$cronJob->setAttributes($attributes);
		$this->assertEquals($isValid, $cronJob->validate());
	}

	public function testComposeLine() {
		$cronJob = new QsCronJob();
		$cronJob->min = '0';
		$cronJob->hour = '1';
		$cronJob->day = '2';
		$cronJob->month = '3';
		$cronJob->weekDay = '*';
		$cronJob->command = 'some';

		$line = $cronJob->getLine();
		$expectedLine = "$cronJob->min $cronJob->hour $cronJob->day $cronJob->month $cronJob->weekDay $cronJob->command";
		$this->assertEquals($expectedLine, $line, 'Wrong line composed!');
	}

	public function testParseLine() {
		$attributes = array(
			'min' => '0',
			'hour' => '1',
			'day' => '2',
			'month' => '3',
			'weekDay' => '*',
			'year' => null,
			'command' => 'some',
		);
		$line = "{$attributes['min']} {$attributes['hour']} {$attributes['day']} {$attributes['month']} {$attributes['weekDay']} {$attributes['command']}";

		$cronJob = new QsCronJob();
		$cronJob->setLine($line);

		$this->assertEquals($attributes, $cronJob->getAttributes(), 'Unable to parse line!');
	}
}