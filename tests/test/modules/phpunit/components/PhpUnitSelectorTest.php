<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.components.PhpUnitSelector".
 * @see PhpUnitSelector
 */
class PhpUnitSelectorTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.test.modules.phpunit.components.PhpUnitSelector');
		Yii::import('qs.test.modules.phpunit.models.*');
	}

	// Tests :

	public function testSetGet() {
		$selector = new PhpUnitSelector();

		$baseTestSuitePaths = array(
			'base1' => '/base/test/suite/path/1',
			'base2' => '/base/test/suite/path/2',
		);
		$this->assertTrue($selector->setBaseTestSuitePaths($baseTestSuitePaths), 'Unable to set base test suite paths!');
		$this->assertEquals($baseTestSuitePaths, $selector->getBaseTestSuitePaths(), 'Unable to set base test suite paths correctly!');

		$testBasePath = 'test_base_path';
		$this->assertTrue($selector->setBasePath($testBasePath), 'Unable to set base path!');
		$this->assertEquals($testBasePath, $selector->getBasePath(), 'Unable to set base path correctly!');

		$testSubPath = 'test/sub/path';
		$this->assertTrue($selector->setSubPath($testSubPath), 'Unable to set sub path!');
		$this->assertEquals($testSubPath, $selector->getSubPath(), 'Unable to set sub path correctly!');

		$testBasePathGetParamName = 'test_base_path_get_param_name';
		$this->assertTrue($selector->setBasePathGetParamName($testBasePathGetParamName), 'Unable to set base path get param name!');
		$this->assertEquals($testBasePathGetParamName, $selector->getBasePathGetParamName(), 'Unable to set base path get param name!');

		$testSubPathGetParamName = 'test_sub_path_get_param_name';
		$this->assertTrue($selector->setSubPathGetParamName($testSubPathGetParamName), 'Unable to set sub path get param name!');
		$this->assertEquals($testSubPathGetParamName, $selector->getSubPathGetParamName(), 'Unable to set sub path get param name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetAdditionalBaseTestSuitePaths() {
		$selector = new PhpUnitSelector();

		$baseTestSuitePaths = array(
			'base1' => '/base/test/suite/path/1',
			'base2' => '/base/test/suite/path/2',
		);
		$selector->setBaseTestSuitePaths($baseTestSuitePaths);

		$additionalBaseTestSuitePath = array(
			'additional1' => '/additional/base/test/suite/path/1',
			'additional2' => '/additional/base/test/suite/path/2',
		);
		$this->assertTrue($selector->setAdditionalBaseTestSuitePaths($additionalBaseTestSuitePath), 'Unable to set additional base test suite paths!');

		$this->assertEquals(array_merge($baseTestSuitePaths, $additionalBaseTestSuitePath), $selector->getBaseTestSuitePaths(), 'Additional base test suite paths have not been merged correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetSubPathFromGet() {
		$selector = new PhpUnitSelector();

		$testSubPath = 'test/sub/path';
		$_GET[$selector->getSubPathGetParamName()] = $testSubPath;

		$this->assertEquals($testSubPath, $selector->getSubPath(), 'Unable to get sub path from GET!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetBasePathFromGet() {
		$selector = new PhpUnitSelector();

		$testBasePath = 'test_base_path';
		$_GET[$selector->getBasePathGetParamName()] = $testBasePath;

		$this->assertEquals($testBasePath, $selector->getBasePath(), 'Unable to get base path from GET!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetBaseTestSuitePath() {
		$selector = new PhpUnitSelector();

		$baseTestSuitePathName = 'base_test_suite_path';
		$baseTestSuitePath = '/base/test/suite/path';
		$selector->setBaseTestSuitePaths(array($baseTestSuitePathName=>$baseTestSuitePath));

		$this->assertEquals($baseTestSuitePath, $selector->getBaseTestSuitePath($baseTestSuitePathName), 'Unable to get base test suite path by name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetTestSuiteFullPath() {
		$selector = new PhpUnitSelector();

		$baseTestSuitePathName = 'base_test_suite_path';
		$baseTestSuitePath = '/base/test/suite/path';
		$selector->setBaseTestSuitePaths(array($baseTestSuitePathName=>$baseTestSuitePath));
		$selector->setBasePath($baseTestSuitePathName);
		$testSubPath = 'test_suite_name.php';
		$selector->setSubPath($testSubPath);

		$expectedTestSuiteFullName = $baseTestSuitePath.DIRECTORY_SEPARATOR.$testSubPath;
		$this->assertEquals($expectedTestSuiteFullName, $selector->getTestSuiteFullPath(), 'Unable to get test suite full path correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testExploreNoBasePath() {
		$selector = new PhpUnitSelector();

		$baseTestSuitePathName = 'base_test_suite_path';
		$baseTestSuitePath = Yii::getPathOfAlias('application.runtime');
		$selector->setBaseTestSuitePaths(array($baseTestSuitePathName=>$baseTestSuitePath));

		$fileSystemUnits = $selector->explore();
		$this->assertFalse(empty($fileSystemUnits), 'Unable to explore test suite if no base path is specified!');
	}
}
