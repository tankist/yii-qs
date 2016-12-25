<?php
/**
 * QsImageTranslationSourceFileStorage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsImageTranslationSourceFileStorage is an image translation source, based on the
 * file storage extension.
 *
 * Attention: this extension requires the extension "qs.files.storages" to be attached to the application!
 *
 * Application configuration example:
 * <code>
 * array(
 *     'components' => array(
 *         ...
 *         'fileStorage' => array(
 *             'class' => 'QsFileStorageFileSystem',
 *             'basePath' => '/home/www/mydomain/materials',
 *             'baseUrl' => 'http://www.mydomain.com/materials',
 *             'filePermission' => 0777,
 *         ),
 *         'imageTranslationSource' => array(
 *             'class' => 'QsImageTranslationSourceFileStorage',
 *             'defaultBaseUrl' => 'http://www.mydomain.com/images/i18n',
 *             'defaultBasePath' => '/home/www/mydomain/images/i18n',
 *         ),
 *         ...
 *     )
 * );
 * </code>
 *
 * @see QsImageTranslationSource
 * @see IQsFileStorage
 * @see IQsFileStorageBucket
 *
 * @property string $fileStorageComponentName public alias of {@link _fileStorageComponentName}.
 * @property string $fileStorageBucketName public alias of {@link _fileStorageBucketName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.images
 */
class QsImageTranslationSourceFileStorage extends QsImageTranslationSource {
	/**
	 * @var string name of the file storage application component.
	 */
	protected $_fileStorageComponentName = 'fileStorage';
	/**
	 * @var string name of the file storage bucket, which stores the related files.
	 * If empty it will be generated automatically using owner class name and {@link filePropertyName}.
	 */
	protected $_fileStorageBucketName = 'i18n';

	// Set / Get:

	public function setFileStorageComponentName($fileStorageComponentName) {
		if (!is_string($fileStorageComponentName)) {
			throw new CException('"' . get_class($this) . '::fileStorageComponentName" should be a string!');
		}
		$this->_fileStorageComponentName = $fileStorageComponentName;
		return true;
	}

	public function getFileStorageComponentName() {
		return $this->_fileStorageComponentName;
	}

	public function setFileStorageBucketName($fileStorageBucketName) {
		if (!is_string($fileStorageBucketName)) {
			throw new CException('"' . get_class($this) . '::fileStorageBucketName" should be a string!');
		}
		$this->_fileStorageBucketName = $fileStorageBucketName;
		return true;
	}

	public function getFileStorageBucketName() {
		return $this->_fileStorageBucketName;
	}

	/**
	 * Returns the file storage bucket for the files by name given with {@link fileStorageBucketName}.
	 * If no bucket exists attempts to create it.
	 * @throws CException if unable to find file storage component
	 * @return IQsFileStorageBucket file storage bucket instance.
	 */
	public function getFileStorageBucket() {
		$fileStorage = Yii::app()->getComponent($this->getFileStorageComponentName());
		if (!is_object($fileStorage)) {
			throw new CException('Unable to find file storage application component "' . $this->getFileStorageComponentName() . '"');
		}
		$bucketName = $this->getFileStorageBucketName();
		if (!$fileStorage->hasBucket($bucketName)) {
			$fileStorage->addBucket($bucketName, array());
		}
		return $fileStorage->getBucket($bucketName);
	}

	/**
	 * Composes the name of the file in the file storage bucket.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return string name of the file in storage bucket.
	 */
	protected function composeStorageBucketFileName($imageName, $language) {
		return $language . '/' . $imageName;
	}

	/**
	 * Loads the image translation for the specified language.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return string the target image URL.
	 */
	protected function loadImageTranslation($imageName, $language) {
		$fileStorageBucket = $this->getFileStorageBucket();
		$fileName = $this->composeStorageBucketFileName($imageName, $language);
		return $fileStorageBucket->getFileUrl($fileName);
	}

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return boolean image translation exists.
	 */
	protected function imageTranslationExists($imageName, $language) {
		$fileName = $this->composeStorageBucketFileName($imageName, $language);
		$fileStorageBucket = $this->getFileStorageBucket();
		return $fileStorageBucket->fileExists($fileName);
	}

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @param string $srcFileName the source file name.
	 * @return boolean image translation exists.
	 */
	protected function saveImageTranslation($imageName, $language, $srcFileName) {
		$fileName = $this->composeStorageBucketFileName($imageName, $language);
		$fileStorageBucket = $this->getFileStorageBucket();
		return $fileStorageBucket->copyFileIn($srcFileName, $fileName);
	}
}
