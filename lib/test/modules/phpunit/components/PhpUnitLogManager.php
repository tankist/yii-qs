<?php
/**
 * PhpUnitLogManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitLogManager manages the output PHPUnit XML log.
 *
 * @property string $logFileName public alias of {@link _logFileName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitLogManager extends CApplicationComponent {
	/**
	 * @var string PHPUnit XML log file location.
	 */
	protected $_logFileName = null;

	// Set / Get:

	public function setLogFileName($logFileName) {
		$this->_logFileName = $logFileName;
		return true;
	}

	public function getLogFileName() {
		if ($this->_logFileName===null) {
			$this->initLogFileName();
		}
		return $this->_logFileName;
	}
	
	/**
	 * Initializes the {@link logFileName}.
	 * @return boolean success.
	 */
	protected function initLogFileName() {
		$logFilePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'phpunit';
		$this->_logFileName = $logFilePath.DIRECTORY_SEPARATOR.'phpunit_log.xml';
		return true;
	}

	/**
	 * Parses the PHPUnit XML log file.
	 * @return string - XML log content.
	 */
	public function readLog() {
		$logFileName = $this->getLogFileName();
		if (!file_exists($logFileName)) {
			//throw new CException("Unable to parse xml log file '{$logFileName}'");
			return '';
		} else {
			$xmlContent = file_get_contents($logFileName);
			return $xmlContent;
		}
	}

	/**
	 * Deletes the PHPUnit log file.
	 * @return boolean success.
	 */
	public function clearLog() {
		$logFileName = $this->getLogFileName();
		if (file_exists($logFileName)) {
			unlink($logFileName);
		}
		return true;
	}
}
