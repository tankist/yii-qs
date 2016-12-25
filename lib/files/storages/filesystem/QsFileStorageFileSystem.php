<?php
/**
 * QsFileStorageFileSystem class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * QsFileStorageFileSystem introduces the file storage based simply
 * on the OS file system.
 *
 * Configuration example:
 * <code>
 * 'fileStorage' => array(
 *     'class' => 'QsFileStorageFileSystem',
 *     'basePath' => '/home/www/files',
 *     'baseUrl' => 'http://www.mydomain.com/files',
 *     'filePermission' => 0777,
 *     'buckets' => array(
 *         'tempFiles' => array(
 *             'baseSubPath' => 'temp',
 *             'fileSubDirTemplate' => '{^name}/{^^name}',
 *         ),
 *         'imageFiles' => array(
 *             'baseSubPath' => 'image',
 *             'fileSubDirTemplate' => '{ext}/{^name}/{^^name}',
 *         ),
 *     )
 * )
 * </code>
 *
 * @see QsFileStorageBucketFileSystem
 *
 * @property string $basePath public alias of {@link _basePath}.
 * @property string $baseUrl public alias of {@link _baseUrl}.
 * @property integer $filePermission public alias of {@link _filePermission}.
 * @method QsFileStorageBucketFileSystem getBucket($bucketName)
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.storages.filesystem
 */
class QsFileStorageFileSystem extends QsFileStorage {
	/**
	 * @var string name of the bucket class.
	 */
	protected $_bucketClassName = 'QsFileStorageBucketFileSystem';
	/**
	 * @var string file system path, which is basic for all buckets.
	 */
	protected $_basePath = '';
	/**
	 * @var string web URL, which is basic for all buckets.
	 */
	protected $_baseUrl = '';
	/**
	 * @var integer the chmod permission for directories and files,
	 * created in the process. Defaults to 0755 (owner rwx, group rx and others rx).
	 */
	protected $_filePermission = 0755;

	// Set / Get :
	
	public function setBasePath($basePath) {
		if (!is_string($basePath)) {
			throw new CException('"' . get_class($this) . '::basePath" should be a string!');
		}
		$this->_basePath = $basePath;
		return true;
	}

	public function getBasePath() {
		return $this->_basePath;
	}

	public function setBaseUrl($baseUrl) {
		if (!is_string($baseUrl)) {
			throw new CException('"' . get_class($this) . '::baseUrl" should be a string!');
		}
		$this->_baseUrl = $baseUrl;
		return true;
	}

	public function getBaseUrl() {
		return $this->_baseUrl;
	}

	public function setFilePermission($filePermission) {
		$this->_filePermission = $filePermission;
		return true;
	}

	public function getFilePermission() {
		return $this->_filePermission;
	}
}