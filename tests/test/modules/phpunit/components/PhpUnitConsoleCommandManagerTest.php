<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.components.PhpUnitConsoleCommandManager".
 * @see PhpUnitConsoleCommandManager
 */
class PhpUnitConsoleCommandManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.test.modules.phpunit.components.PhpUnitConsoleCommandManager');
	}

	// Tests:

	public function testSetGet() {
		$consoleCommandManager = new PhpUnitConsoleCommandManager();

		$testPhpUnitBinPath = '/test/phpunit/bin/path';
		$this->assertTrue($consoleCommandManager->setPhpUnitBinPath($testPhpUnitBinPath), 'Unable to set php unit bin path!');
		$this->assertEquals($testPhpUnitBinPath, $consoleCommandManager->getPhpUnitBinPath(), 'Unable to set php unit bin path correctly!');

		$testConsoleCommandOutput = 'test console command output';
		$this->assertTrue($consoleCommandManager->setConsoleCommandOutput($testConsoleCommandOutput), 'Unable to set console command output!');
		$this->assertEquals($testConsoleCommandOutput, $consoleCommandManager->getConsoleCommandOutput(), 'Unable to set console command output correctly!');

		$testAutoProcessIsolationMinTestSuiteCount = rand(50, 100);
		$this->assertTrue($consoleCommandManager->setAutoProcessIsolationMinTestSuiteCount($testAutoProcessIsolationMinTestSuiteCount), 'Unable to set auto process isolation min test suite count!');
		$this->assertEquals($testAutoProcessIsolationMinTestSuiteCount, $consoleCommandManager->getAutoProcessIsolationMinTestSuiteCount(), 'Unable to set auto process isolation min test suite count correctly!');
	}

}
