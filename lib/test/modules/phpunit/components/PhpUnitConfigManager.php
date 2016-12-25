<?php
/**
 * PhpUnitConfigManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitConfigManager manages the PHPUnit configuration params.
 *
 * @property string $bootstrapFileName public alias of {@link _bootstrapFileName}.
 * @property string $originXmlConfigFileName public alias of {@link _originXmlConfigFileName}.
 * @property string $xmlConfigFileName public alias of {@link _xmlConfigFileName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitConfigManager extends CApplicationComponent {
	/**
	 * @var string PHPUnit bootstrap file name.
	 */
	protected $_bootstrapFileName = null;
	/**
	 * @var sting|null origin PHPUnit XML config file name.
	 * This file will be parsed and use as basis for the real configuration file.
	 */
	protected $_originXmlConfigFileName = null;
	/**
	 * @var string PHPUnit XML config file name.
	 */
	protected $_xmlConfigFileName = null;

	public function setBootstrapFileName($bootstrapFileName) {
		$this->_bootstrapFileName = $bootstrapFileName;
		return true;
	}

	public function getBootstrapFileName() {
		if ($this->_bootstrapFileName===null) {
			$this->initBootstrapFileName();
		}
		return $this->_bootstrapFileName;
	}

	public function setOriginXmlConfigFileName($originXmlConfigFileName) {
		$this->_originXmlConfigFileName = $originXmlConfigFileName;
		return true;
	}

	public function getOriginXmlConfigFileName() {
		if (empty($this->_originXmlConfigFileName)) {
			$this->initOriginXmlConfigFileName();
		}
		return $this->_originXmlConfigFileName;
	}

	public function setXmlConfigFileName($xmlConfigFileName) {
		$this->_xmlConfigFileName = $xmlConfigFileName;
		return true;
	}

	public function getXmlConfigFileName() {
		if ($this->_xmlConfigFileName===null) {
			$this->initXmlConfigFileName();
		}
		return $this->_xmlConfigFileName;
	}

	/**
	 * Returns the log file name.
	 * @return string log file name.
	 */
	public function getLogFileName() {
		return Yii::app()->getModule('phpunit')->getComponent('logManager')->getLogFileName();
	}

	/**
	 * Initializes the {@link bootstrapFileName}.
	 * @return boolean success.
	 */
	protected function initBootstrapFileName() {
		$this->_bootstrapFileName = Yii::getPathOfAlias('application.tests').DIRECTORY_SEPARATOR.'bootstrap.php';
		return true;
	}

	/**
	 * Initializes the {@link xmlConfigFileName}.
	 * @return boolean success.
	 */
	protected function initOriginXmlConfigFileName() {
		$this->_originXmlConfigFileName = Yii::getPathOfAlias('application.tests').DIRECTORY_SEPARATOR.'phpunit.xml';
		return true;
	}

	/**
	 * Initializes the {@link xmlConfigFileName}.
	 * @return boolean success.
	 */
	protected function initXmlConfigFileName() {
		$this->_xmlConfigFileName = Yii::getPathOfAlias('application.runtime.phpunit').DIRECTORY_SEPARATOR.'phpunit_config.xml';
		return true;
	}

	/**
	 * Composes the runtime XML configuration file.
	 * @return boolean success.
	 */
	public function composeXmlConfigFile() {
		$originXmlConfigFileName = $this->getOriginXmlConfigFileName();
		$xmlConfigFileName = $this->getXmlConfigFileName();
		if (!file_exists($originXmlConfigFileName)) {
			throw new CException("Unable to compose XML config file name: origin file '{$originXmlConfigFileName}' does not exist!");
		}
		if (file_exists($xmlConfigFileName)) {
			if (filemtime($originXmlConfigFileName) < filemtime($xmlConfigFileName)) {
				return true;
			} else {
				unlink($xmlConfigFileName);
			}
		}
		$xmlConfigFileContent = $this->composeXmlConfigContent(file_get_contents($originXmlConfigFileName));
		file_put_contents($xmlConfigFileName, $xmlConfigFileContent);
		return true;
	}

	/**
	 * Composes runtime XML config content, appending logging options.
	 * @param string $originXmlConfigContent original XML config content.
	 * @return string composed XML config content.
	 */
	protected function composeXmlConfigContent($originXmlConfigContent) {
		$xml = simplexml_load_string($originXmlConfigContent);
		if (!isset($xml->logging)) {
			$xmlElementLogging = $xml->addChild('logging');
		} else {
			$xmlElementLogging = $xml->logging;
		}
		$xmlElementLog = $xmlElementLogging->addChild('log');
		$xmlElementLog->addAttribute('type', 'junit');
		$xmlElementLog->addAttribute('target', $this->getLogFileName());
		$xmlElementLog->addAttribute('logIncompleteSkipped', 'true');

		return $xml->asXML();
	}
}
