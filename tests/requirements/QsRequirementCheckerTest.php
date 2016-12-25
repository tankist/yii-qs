<?php

require_once( realpath(dirname(__FILE__).'/../../lib/requirements/QsRequirementChecker.php') );

/**
 * Test case for the extension "qs.requirements.QsRequirementChecker".
 * @see QsRequirementChecker
 */
class QsRequirementCheckerTest extends CTestCase {
	/**
	 * Creates requirements checker with the empty default requirements list.
	 * @return QsRequirementChecker requirements checker instance.
	 */
	protected function createRequirementsCheckerWithEmptyDefaults() {
		$requirementsChecker = $this->getMock('QsRequirementChecker', array('getDefaultRequirements'));
		$requirementsChecker->expects($this->any())->method('getDefaultRequirements')->will($this->returnValue(array()));
		return $requirementsChecker;
	}

	// Tests:

	public function testCheck() {
		$requirementsChecker = $this->createRequirementsCheckerWithEmptyDefaults();

		$requirements = array(
			'requirementPass' => array(
				'name' => 'Requirement 1',
				'mandatory' => true,
				'condition' => true,
				'by' => 'Requirement 1',
				'memo' => 'Requirement 1',
			),
			'requirementError' => array(
				'name' => 'Requirement 2',
				'mandatory' => true,
				'condition' => false,
				'by' => 'Requirement 2',
				'memo' => 'Requirement 2',
			),
			'requirementWarning' => array(
				'name' => 'Requirement 3',
				'mandatory' => false,
				'condition' => false,
				'by' => 'Requirement 3',
				'memo' => 'Requirement 3',
			),
		);

		$checkResult = $requirementsChecker->check($requirements)->getResult();
		$summary = $checkResult['summary'];

		$this->assertEquals(count($requirements), $summary['total'], 'Wrong summary total!');
		$this->assertEquals(1, $summary['errors'], 'Wrong summary errors!');
		$this->assertEquals(1, $summary['warnings'], 'Wrong summary warnings!');

		$checkedRequirements = $checkResult['requirements'];
		$requirementsKeys = array_flip(array_keys($requirements));

		$this->assertEquals(false, $checkedRequirements[$requirementsKeys['requirementPass']]['error'], 'Passed requirement has an error!');
		$this->assertEquals(false, $checkedRequirements[$requirementsKeys['requirementPass']]['warning'], 'Passed requirement has a warning!');

		$this->assertEquals(true, $checkedRequirements[$requirementsKeys['requirementError']]['error'], 'Error requirement has no error!');

		$this->assertEquals(false, $checkedRequirements[$requirementsKeys['requirementWarning']]['error'], 'Error requirement has an error!');
		$this->assertEquals(true, $checkedRequirements[$requirementsKeys['requirementWarning']]['warning'], 'Error requirement has no warning!');
	}

	public function testCheckEval() {
		$requirementsChecker = $this->createRequirementsCheckerWithEmptyDefaults();

		$requirements = array(
			'requirementPass' => array(
				'name' => 'Requirement 1',
				'mandatory' => true,
				'condition' => 'eval:2>1',
				'by' => 'Requirement 1',
				'memo' => 'Requirement 1',
			),
			'requirementError' => array(
				'name' => 'Requirement 2',
				'mandatory' => true,
				'condition' => 'eval:2<1',
				'by' => 'Requirement 2',
				'memo' => 'Requirement 2',
			),
		);

		$checkResult = $requirementsChecker->check($requirements)->getResult();
		$checkedRequirements = $checkResult['requirements'];
		$requirementsKeys = array_flip(array_keys($requirements));

		$this->assertEquals(false, $checkedRequirements[$requirementsKeys['requirementPass']]['error'], 'Passed requirement has an error!');
		$this->assertEquals(false, $checkedRequirements[$requirementsKeys['requirementPass']]['warning'], 'Passed requirement has a warning!');

		$this->assertEquals(true, $checkedRequirements[$requirementsKeys['requirementError']]['error'], 'Error requirement has no error!');
	}

	public function testCheckPhpExtensionVersion() {
		$requirementsChecker = new QsRequirementChecker();

		$this->assertFalse($requirementsChecker->checkPhpExtensionVersion('some_unexisting_php_extension', '0.1'), 'No fail while checking unexisting extension!');

		$this->assertTrue($requirementsChecker->checkPhpExtensionVersion('pdo', '1.0'), 'Unable to check PDO version!');
	}

	/**
	 * Data provider for {@link testGetByteSize()}.
	 * @return array
	 */
	public function dataProviderGetByteSize() {
		return array(
			array('456', 456),
			array('5K', 5*1024),
			array('16KB', 16*1024),
			array('4M', 4*1024*1024),
			array('14MB', 14*1024*1024),
			array('7G', 7*1024*1024*1024),
			array('12GB', 12*1024*1024*1024),
		);
	}

	/**
	 * @dataProvider dataProviderGetByteSize
	 *
	 * @param string $verboseValue verbose value.
	 * @param integer $expectedByteSize expected byte size.
	 */
	public function testGetByteSize($verboseValue, $expectedByteSize) {
		$requirementsChecker = new QsRequirementChecker();

		$this->assertEquals($expectedByteSize, $requirementsChecker->getByteSize($verboseValue), "Wrong byte size for '{$verboseValue}'!");
	}

	public function dataProviderCompareByteSize() {
		return array(
			array('2M', '2K', '>', true),
			array('2M', '2K', '>=', true),
			array('1K', '1024', '==', true),
			array('10M', '11M', '<', true),
			array('10M', '11M', '<=', true),
		);
	}

	/**
	 * @depends testGetByteSize
	 * @dataProvider dataProviderCompareByteSize
	 *
	 * @param string $a first value.
	 * @param string $b second value.
	 * @param string $compare comparison.
	 * @param boolean $expectedComparisonResult expected comparison result.
	 */
	public function testCompareByteSize($a, $b, $compare, $expectedComparisonResult) {
		$requirementsChecker = new QsRequirementChecker();
		$this->assertEquals($expectedComparisonResult, $requirementsChecker->compareByteSize($a, $b, $compare), "Wrong compare '{$a}{$compare}{$b}'");
	}

	public function testCheckShellCommandAvailable() {
		$requirementsChecker = new QsRequirementChecker();

		$this->assertTrue($requirementsChecker->checkShellCommandAvailable('ls'), 'Existing shell command check failed!');
		$this->assertFalse($requirementsChecker->checkShellCommandAvailable('test_unexisting_shell_command'), 'Unexisting shell command check failed!');
	}
}
