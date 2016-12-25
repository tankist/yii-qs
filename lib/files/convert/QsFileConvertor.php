<?php
/**
 * QsFileConvertor class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsFileConvertor is a base class for the file convertors.
 *
 * @property string $defaultOptions public alias of {@link _defaultOptions}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.convert
 */
abstract class QsFileConvertor extends CApplicationComponent implements IQsFileConvertor {
	/**
	 * @var array default convert options.
	 * Possible values may vary depending on particular convertor.
	 */
	protected $_defaultOptions = array();

	/**
	 * Sets up the default convert options.
	 * @param array $defaultOptions default convert options.
	 * @return boolean success.
	 */
	public function setDefaultOptions(array $defaultOptions) {
		$this->_defaultOptions = $defaultOptions;
		return true;
	}

	/**
	 * Returns the default convert options.
	 * @return array default convert options
	 */
	public function getDefaultOptions() {
		return $this->_defaultOptions;
	}

	/**
	 * Composes options, merging default ones with the given ones.
	 * @param array $options - options list.
	 * @return array - composed options.
	 */
	protected function composeOptions(array $options) {
		$options = CMap::mergeArray($this->getDefaultOptions(), $options);
		return $options;
	}

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level=CLogger::LEVEL_INFO) {
		$category = 'qs.files.convert.' . get_class($this);
		Yii::log($message, $level, $category);
		return true;
	}
}