<?php
/**
 * PhpUnitRunner class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * PhpUnitRunner runs the PHPUnit test, applying all run settings.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitRunner extends CApplicationComponent {
	/**
	 * @return PhpUnitLogManager log manager.
	 */
	public function getLogManager() {
		return Yii::app()->getModule('phpunit')->getComponent('logManager');
	}

	/**
	 * @return PhpUnitFileSystemManager file system manager.
	 */
	public function getFileSystemManager() {
		return Yii::app()->getModule('phpunit')->getComponent('fileSystemManager');
	}

	/**
	 * @return PhpUnitConfigManager config manager.
	 */
	public function getConfigManager() {
		return Yii::app()->getModule('phpunit')->getComponent('configManager');
	}

	/**
	 * @return PhpUnitConsoleCommandManager console command manager.
	 */
	public function getConsoleCommandManager() {
		return Yii::app()->getModule('phpunit')->getComponent('consoleCommandManager');
	}

	/**
	 * @return PhpUnitSelector selector.
	 */
	public function getSelector() {
		return Yii::app()->getModule('phpunit')->getComponent('selector');
	}

	/**
	 * Clears test results.
	 * @return boolean success.
	 */
	public function clear() {
		$this->getLogManager()->clearLog();
		$this->getConsoleCommandManager()->clearShellCommandOutput();
		return true;
	}

	/**
	 * Makes preparations for the test running.
	 * @return boolean success.
	 */
	public function prepare() {
		$fileSystemManager = $this->getFileSystemManager();

		$logManager = $this->getLogManager();
		$fileSystemManager->resolvePath(dirname($logManager->getLogFileName()), 'Log file');

		$configManager = $this->getConfigManager();
		$fileSystemManager->resolvePath(dirname($configManager->getXmlConfigFileName()), 'Runtime XML config');
		$configManager->composeXmlConfigFile();

		return $this->clear();
	}

	/**
	 * @param string $testSuiteName test suite name.
	 * @return boolean success.
	 */
	public function runTest($testSuiteName=null) {
		$this->prepare();
		if ($testSuiteName!==null) {
			$this->getSelector()->setSubPath($testSuiteName);
		}

		$testSuiteFullName = $this->getSelector()->getTestSuiteFullPath();
		$configFileName = $this->getConfigManager()->getXmlConfigFileName();
		$bootstrapFileName = $this->getConfigManager()->getBootstrapFileName();

		return $this->getConsoleCommandManager()->runPhpUnit($testSuiteFullName, $configFileName, $bootstrapFileName);
	}
}
