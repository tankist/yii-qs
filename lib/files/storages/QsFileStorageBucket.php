<?php
/**
 * QsFileStorageBucket class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsFileStorageBucket is a base class for the file storage buckets.
 *
 * @property string $name public alias of {@link _name}.
 * @property IQsFileStorage $storage public alias of {@link _storage}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.storages
 */
abstract class QsFileStorageBucket extends CComponent implements IQsFileStorageBucket {
	/**
	 * @var string bucket name.
	 */
	protected $_name = '';
	/**
	 * @var IQsFileStorage file storage, which owns the bucket.
	 */
	protected $_storage = null;

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level = CLogger::LEVEL_INFO) {
		$category = 'qs.files.storages.' . get_class($this);
		$message = 'Bucket "' . $this->getName() . '": ' . $message;
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Sets bucket name.
	 * @param string $name - bucket name.
	 * @return boolean success.
	 */
	public function setName($name) {
		if (!is_string($name)) {
			throw new CException('"' . get_class($this) . '::name" should be a string!');
		}
		$this->_name = $name;
		return true;
	}

	/**
	 * Gets current bucket name.
	 * @return string $name - bucket name.
	 */
	public function getName() {
		return $this->_name;
	}

	/**
	 * Sets bucket file storage.
	 * @param IQsFileStorage $storage - file storage.
	 * @return boolean success.
	 */
	public function setStorage(IQsFileStorage $storage) {
		$this->_storage = $storage;
		return true;
	}

	/**
	 * Gets bucket file storage.
	 * @return IQsFileStorage - bucket file storage.
	 */
	public function getStorage() {
		return $this->_storage;
	}
}