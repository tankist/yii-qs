<?php
/**
 * QsConfigStorageFile class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

if (!class_exists('QsConfigStorage', false)) {
	require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'QsConfigStorage.php');
}

/**
 * QsConfigStorageFile represents the configuration storage based on local files.
 *
 * @property string $fileName public alias of {@link _fileName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.config
 */
class QsConfigStorageFile extends QsConfigStorage {
	/**
	 * @var string name of the file, which should be used to store values.
	 */
	protected $_fileName;

	/**
	 * @param string $fileName
	 */
	public function setFileName($fileName) {
		$this->_fileName = $fileName;
	}

	/**
	 * @return string
	 */
	public function getFileName() {
		if (empty($this->_fileName)) {
			$this->_fileName = $this->defaultFileName();
		}
		return $this->_fileName;
	}

	/**
	 * Creates default {@link fileName} value.
	 * @return string default file name.
	 */
	protected function defaultFileName() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . get_class($this) . '_data.php';
	}

	/**
	 * Saves given values.
	 * @param array $values in format: 'id' => 'value'
	 * @return boolean success.
	 */
	public function save(array $values) {
		$this->clear();
		$bytesWritten = file_put_contents($this->getFileName(), $this->composeFileContent($values));
		return ($bytesWritten > 0);
	}

	/**
	 * Returns previously saved values.
	 * @return array values in format: 'id' => 'value'
	 */
	public function get() {
		$fileName = $this->getFileName();
		if (file_exists($fileName)) {
			return require($fileName);
		} else {
			return array();
		}
	}

	/**
	 * Clears all saved values.
	 * @return boolean success.
	 */
	public function clear() {
		$fileName = $this->getFileName();
		if (file_exists($fileName)) {
			return unlink($fileName);
		}
		return true;
	}

	/**
	 * Composes file content for the given values.
	 * @param array $values values to be saved.
	 * @return string file content.
	 */
	protected function composeFileContent(array $values) {
		$content = '<?php return ' . var_export($values, true) . ';';
		return $content;
	}
}