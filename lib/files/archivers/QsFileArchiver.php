<?php
/**
 * QsFileArchiver class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsFileArchiver is a base class for the all file archivers.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
abstract class QsFileArchiver extends CApplicationComponent implements IQsFileArchiver {
	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level = CLogger::LEVEL_INFO) {
		$category = 'qs.files.archivers.' . get_class($this);
		Yii::log($message, $level, $category);
		return true;
	}
}
