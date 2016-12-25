<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.components.PhpUnitConfigManager".
 * @see PhpUnitConfigManager
 */
class PhpUnitConfigManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.test.modules.phpunit.components.PhpUnitConfigManager');

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

	// Tests:

	public function testSetGet() {
		$configManager = new PhpUnitConfigManager();

		$testBootstrapFileName = '/test/bootstrap/file/name.php';
		$this->assertTrue($configManager->setBootstrapFileName($testBootstrapFileName), 'Unable to set bootstrap file name!');
		$this->assertEquals($testBootstrapFileName, $configManager->getBootstrapFileName(), 'Unable to set bootstrap file name correctly!');

		$testOriginXmlConfigFileName = '/test/origin/xml/config/file/name.xml';
		$this->assertTrue($configManager->setOriginXmlConfigFileName($testOriginXmlConfigFileName), 'Unable to set origin xml config file name!');
		$this->assertEquals($testOriginXmlConfigFileName, $configManager->getOriginXmlConfigFileName(), 'Unable to set origin xml config file name correctly!');

		$testXmlConfigFileName = '/test/xml/config/file/name.xml';
		$this->assertTrue($configManager->setXmlConfigFileName($testXmlConfigFileName), 'Unable to set xml config file name!');
		$this->assertEquals($testXmlConfigFileName, $configManager->getXmlConfigFileName(), 'Unable to set xml config file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultBootstrapFileName() {
		$configManager = new PhpUnitConfigManager();

		$defaultBootstrapFileName = $configManager->getBootstrapFileName();
		$this->assertFalse(empty($defaultBootstrapFileName), 'Unable to get default bootstrap file name!');

		$expectedBootstrapFileName = Yii::getPathOfAlias('application.tests').DIRECTORY_SEPARATOR.'bootstrap.php';
		$this->assertEquals($expectedBootstrapFileName, $defaultBootstrapFileName, 'Unable to get default bootstrap file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultOriginXmlConfigFileName() {
		$configManager = new PhpUnitConfigManager();

		$defaultOriginXmlConfigFileName = $configManager->getOriginXmlConfigFileName();
		$this->assertFalse(empty($defaultOriginXmlConfigFileName), 'Unable to get default origin xml config file name!');

		$expectedOriginXmlConfigFileName = Yii::getPathOfAlias('application.tests').DIRECTORY_SEPARATOR.'phpunit.xml';
		$this->assertEquals($expectedOriginXmlConfigFileName, $defaultOriginXmlConfigFileName, 'Unable to get default origin xml config file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultXmlConfigFileName() {
		$configManager = new PhpUnitConfigManager();

		$defaultXmlConfigFileName = $configManager->getXmlConfigFileName();
		$this->assertFalse(empty($defaultXmlConfigFileName), 'Unable to get default xml config file name!');

		$expectedXmlConfigFileName = Yii::getPathOfAlias('application.runtime.phpunit').DIRECTORY_SEPARATOR.'phpunit_config.xml';
		$this->assertEquals($expectedXmlConfigFileName, $defaultXmlConfigFileName, 'Unable to get default xml config file name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testComposeXmlConfigFile() {
		$testLogFileName = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_log.xml';

		$configManager = $this->getMock('PhpUnitConfigManager', array('getLogFileName'));
		$configManager->expects($this->any())->method('getLogFileName')->will($this->returnValue($testLogFileName));

		$testOriginXmlConfigFileName = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_origin.xml';
		file_put_contents($testOriginXmlConfigFileName, '<phpunit></phpunit>');
		$configManager->setOriginXmlConfigFileName($testOriginXmlConfigFileName);

		$testXmlConfigFileName = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_runtime.xml';
		$configManager->setXmlConfigFileName($testXmlConfigFileName);

		$this->assertTrue($configManager->composeXmlConfigFile(), 'Unable to compose xml config file!');
		$this->assertTrue(file_exists($testXmlConfigFileName), 'Unable to create runtime xml config file!');

		$xmlConfigContent = file_get_contents($testXmlConfigFileName);
		$this->assertContains($testLogFileName, $xmlConfigContent, 'Log file name has not been append to the xml config file!');
	}
}
