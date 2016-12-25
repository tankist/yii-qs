<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.components.PhpUnitLogManager".
 * @see PhpUnitLogManager
 */
class PhpUnitLogManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.test.modules.phpunit.components.PhpUnitLogManager');

		$testDirectoryName = self::getTestDirectoryName();
		if (!file_exists($testDirectoryName)) {
			mkdir($testDirectoryName, 0777, true);
		}
	}

	public static function tearDownAfterClass() {
		$testDirectoryName = self::getTestDirectoryName();
		if (file_exists($testDirectoryName)) {
			exec("rm -rf {$testDirectoryName}");
		}
	}

	/**
	 * Returns the temporary test directory name
	 * @return string temporary test directory name.
	 */
	public static function getTestDirectoryName() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.__CLASS__.getmypid();
	}

	// Tests :

	public function testSetGet() {
		$logManager = new PhpUnitLogManager();

		$testLogFileName = '/test/log/filename.xml';
		$this->assertTrue($logManager->setLogFileName($testLogFileName), 'Unable to set log file name!');
		$this->assertEquals($testLogFileName, $logManager->getLogFileName(), 'Unable to set log file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLogFileName() {
		$logManager = new PhpUnitLogManager();

		$defaultLogFileName = $logManager->getLogFileName();
		$this->assertFalse(empty($defaultLogFileName), 'Unable to get default log file name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testReadLog() {
		$logManager = new PhpUnitLogManager();

		$testLogFileName = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_log_file_name.xml';
		$testLogFileContent = 'Test Log File Content';
		file_put_contents($testLogFileName, $testLogFileContent);

		$logManager->setLogFileName($testLogFileName);

		$readLogFileContent = $logManager->readLog();
		$this->assertEquals($testLogFileContent, $readLogFileContent, 'Unable to read log file content!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testClearLog() {
		$logManager = new PhpUnitLogManager();

		$testLogFileName = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_log_file_name.xml';
		$testLogFileContent = 'Test Log File Content';
		file_put_contents($testLogFileName, $testLogFileContent);

		$logManager->setLogFileName($testLogFileName);

		$logManager->clearLog();
		$this->assertFalse(file_exists($testLogFileName), 'Unable to clear log file!');
	}
}
