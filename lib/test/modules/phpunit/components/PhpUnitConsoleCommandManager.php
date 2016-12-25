<?php
/**
 * PhpUnitConsoleCommandManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * PhpUnitConsoleCommandManager runs the PHPUnit console command.
 *
 * @property string $phpUnitBinPath public alias of {@link _phpUnitBinPath}.
 * @property string $consoleCommandOutput public alias of {@link _consoleCommandOutput}.
 * @property integer $autoProcessIsolationMinTestSuiteCount public alias of {@link _autoProcessIsolationMinTestSuiteCount}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitConsoleCommandManager extends CApplicationComponent {
	/**
	 * @var string path to the PHPUnit run test command.
	 * For example: '/usr/local/php/pear/phpunit'.
	 * This path will be used to compose console command name.
	 */
	protected $_phpUnitBinPath = 'phpunit';
	/**
	 * @var string PHPUnit shell command output.
	 */
	protected $_consoleCommandOutput = '';
	/**
	 * @var int minimum amount of test suites, which should be run in process isolated mode.
	 * If param value set to zero, process isolated mode will never be triggered.
	 */
	protected $_autoProcessIsolationMinTestSuiteCount = 20;

	public function setPhpUnitBinPath($phpUnitBinPath) {
		$this->_phpUnitBinPath = $phpUnitBinPath;
		return true;
	}

	public function getPhpUnitBinPath() {
		return $this->_phpUnitBinPath;
	}

	public function setConsoleCommandOutput($consoleCommandOutput) {
		if (is_array($consoleCommandOutput)) {
			$consoleCommandOutput = implode("\n", $consoleCommandOutput);
		}
		$this->_consoleCommandOutput = $consoleCommandOutput;
		return true;
	}

	public function getConsoleCommandOutput() {
		return $this->_consoleCommandOutput;
	}

	public function setAutoProcessIsolationMinTestSuiteCount($autoProcessIsolationMinTestSuiteCount) {
		$this->_autoProcessIsolationMinTestSuiteCount = $autoProcessIsolationMinTestSuiteCount;
		return true;
	}

	public function getAutoProcessIsolationMinTestSuiteCount() {
		return $this->_autoProcessIsolationMinTestSuiteCount;
	}

	/**
	 * Clears the {@link shellCommandOutput}.
	 * @return boolean success.
	 */
	public function clearShellCommandOutput() {
		return $this->setConsoleCommandOutput('');
	}

	/**
	 * Runs the PHP unit command.
	 * @param string $testSuitePath test suite full path.
	 * @param string $configFileName XML config full file name.
	 * @param string|null $bootstrapFileName bootstrap full file name.
	 * @return string console command output.
	 */
	public function runPhpUnit($testSuitePath, $configFileName, $bootstrapFileName=null) {
		if (empty($testSuitePath)) {
			throw new CException('Test suite name can not be empty!');
		}

		$consoleCommandParams = array(
			'configuration' => $configFileName,
		);
		if (!empty($bootstrapFileName)) {
			$consoleCommandParams['bootstrap'] = $bootstrapFileName;
		}

		$autoProcessIsolationMinTestSuiteCount = $this->getAutoProcessIsolationMinTestSuiteCount();
		if ($autoProcessIsolationMinTestSuiteCount>0) {
			$testSuitesCount = $this->countTestSuiteFiles($testSuitePath);
			if ($testSuitesCount>=$autoProcessIsolationMinTestSuiteCount) {
				$consoleCommandParams[] = '--process-isolation';
			}
		}

		$consoleCommandParams[] = escapeshellarg($testSuitePath);
		return $this->executeConsoleCommand($consoleCommandParams);
	}

	/**
	 * Executes the PHPUnit shell command.
	 * @param array $params set of command params.
	 * @return string console command output.
	 */
	protected function executeConsoleCommand(array $params=array()) {
		$consoleCommandString = $this->composeConsoleCommand($params);
		exec($consoleCommandString, $output);
		$this->setConsoleCommandOutput($output);
		return $this->getConsoleCommandOutput();
	}

	/**
	 * Composes PHPUnit shell console command string.
	 * @param array $params - console command parameters.
	 * @return string - command string.
	 */
	protected function composeConsoleCommand(array $params=array()) {
		$commandFullName = rtrim( $this->getPhpUnitBinPath(), DIRECTORY_SEPARATOR );
		$consoleCommandString = $commandFullName.' '.$this->composeConsoleCommandParams($params);
		return $consoleCommandString;
	}

	/**
	 * Composes console command params into a string,
	 * which is suitable to be passed to console.
	 * @param array $params - console command parameters.
	 * @return string - command params part.
	 */
	protected function composeConsoleCommandParams(array $params) {
		$consoleCommandParts = array();
		foreach ($params as $paramKey=>$paramValue) {
			if (is_numeric($paramKey)) {
				$consoleCommandParts[] = $paramValue;
			} else {
				$consoleCommandParts[] = "--{$paramKey} ".escapeshellarg($paramValue);
			}
		}
		return implode(' ', $consoleCommandParts);
	}

	/**
	 * Calculates the number of test suite files under the given path.
	 * @param string $path path to the files.
	 * @return int files count.
	 */
	protected function countTestSuiteFiles($path) {
		if (!file_exists($path)) {
			return 0;
		}
		if (!is_dir($path)) {
			return 1;
		}
		$files = CFileHelper::findFiles($path);
		return count($files);
	}
}
