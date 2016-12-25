<?php
/**
 * QsActiveRecordBehaviorFileTransform class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.db.ar.QsActiveRecordBehaviorFile');
 
/**
 * Extension of the {@link QsActiveRecordBehaviorFile} - behavior for the {@link CActiveRecord}.
 * QsActiveRecordBehaviorFileTransform is developed for the managing files, which require some post processing.
 * Behavior allows to set up several different transformations for the file, so actually several files will be related to the one record in the database table.
 * You can set up the {@link transformCallback} in order to specify transformation method(s).
 *
 * Note: you can always use {@link saveFile} method to attach any file (not just uploaded one) to the model.
 *
 * Attention: this extension requires the extension "qs.files.storages" to be attached to the application!
 * Files will be saved using file storage component.
 * @see QsActiveRecordBehaviorFile
 * @see IQsFileStorage
 * @see IQsFileStorageBucket
 *
 * @property array $fileTransforms public alias of {@link _fileTransforms}.
 * @property callback $transformCallback public alias of {@link _transformCallback}.
 * @property string|array $defaultFileUrl public alias of {@link _defaultFileUrl}.
 * @property string $defaultFileTransformName public alias of {@link _defaultFileTransformName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorFileTransform extends QsActiveRecordBehaviorFile {
	/**
	 * @var array, which determines all possible file transformations.
	 * The key of array element is the name of transformation and will be used to create file name.
	 * The value is an array of parameters for transformation. Its value depends on which {@link transformCallback} you are using.
	 * If you wish to save original file without transformation, specify a key without value.
	 * For example:
	 * <code>
	 * array(
	 *     'origin',
	 *     'main' => array(...),
	 *     'light' => array(...),
	 * );
	 * </code>
	 */
	protected $_fileTransforms = array();
	/**
	 * @var callback which will be called while file transforming.
	 * This should be a valid PHP callback.
	 */
	protected $_transformCallback = null;
	/**
	 * @var string|array URL(s), which is used to set up web links, which will be returned if requested file does not exists.
	 * If may specify this parameter as string it will be considered as web link and will be used for all transformations.
	 * For example:
	 * 'http://www.myproject.com/materials/default/image.jpg'
	 * If you specify this parameter as an array, its key will be considered as transformation name, while value - as web link.
	 * For example:
	 * array(
	 *     'full'=> 'http://www.myproject.com/materials/default/full.jpg',
	 *     'thumbnail'=> 'http://www.myproject.com/materials/default/thumbnail.jpg',
	 * )
	 */
	protected $_defaultFileUrl = array();
	/**
	 * @var string name of the file transformation, which should be used by default,
	 * if no specific transformation name given.
	 */
	protected $_defaultFileTransformName = '';

	public function setFileTransforms(array $fileTransforms) {
		$this->_fileTransforms = $fileTransforms;
		return true;
	}

	public function getFileTransforms() {
		return $this->_fileTransforms;
	}

	public function setTransformCallback($transformCallback) {
		if (!is_callable($transformCallback, true)) {
			throw new CException('"' . get_class($this) . '::transformCallback" should be a valid callback, "' . gettype($transformCallback) . '" is given.');
		}
		$this->_transformCallback = $transformCallback;
		return true;
	}

	public function getTransformCallback() {
		return $this->_transformCallback;
	}

	public function setDefaultFileTransformName($defaultFileTransformName) {
		$this->_defaultFileTransformName = $defaultFileTransformName;
		return true;
	}

	public function getDefaultFileTransformName() {
		if (empty($this->_defaultFileTransformName)) {
			$this->initDefaultFileTransformName();
		}
		return $this->_defaultFileTransformName;
	}

	/**
	 * Returns the default file URL.
	 * @param string $name file transformation name.
	 * @return string default file URL.
	 */
	public function getDefaultFileUrl($name = null) {
		if (is_array($this->_defaultFileUrl)) {
			if (!empty($name)) {
				return $this->_defaultFileUrl[$name];
			} else {
				reset($this->_defaultFileUrl);
				return current($this->_defaultFileUrl);
			}
		} else {
			return $this->_defaultFileUrl;
		}
	}

	/**
	 * Creates file itself name (without path) including version and extension.
	 * This method overrides parent implementation in order to include transformation name.
	 * @param string $fileTransformName image transformation name.
	 * @param integer $fileVersion file version number.
	 * @param string $fileExtension file extension.
	 * @return string file self name.
	 */
	public function getFileSelfName($fileTransformName = null, $fileVersion = null, $fileExtension = null) {
		$owner = $this->getOwner();
		$fileTransformName = $this->fetchFileTransformName($fileTransformName);
		$fileNamePrefix = '_' . $fileTransformName;
		if (is_null($fileVersion)) {
			$fileVersion = $this->getFileVersionCurrent();
		}
		if (is_null($fileExtension)) {
			$fileExtension = $owner->getAttribute($this->getFileExtensionAttributeName());
		}
		return $this->getFileBaseName() . $fileNamePrefix . '_' . $fileVersion . '.' . $fileExtension;
	}

	/**
	 * Creates the file name in the file storage.
	 * This name contains the sub directory, resolved by {@link subDirTemplate}.
	 * @param string $fileTransformName file transformation name.
	 * @param integer $fileVersion file version number.
	 * @param string $fileExtension file extension.
	 * @return string file full name.
	 */
	public function getFileFullName($fileTransformName = null, $fileVersion = null, $fileExtension = null) {
		$fileName = $this->getFileSelfName($fileTransformName, $fileVersion,$fileExtension);
		$subDir = $this->getActualSubDir();
		if (!empty($subDir)) {
			$fileName = $subDir . DIRECTORY_SEPARATOR . $fileName;
		}
		return $fileName;
	}

	/**
	 * Fetches the value of file transform name.
	 * Returns default file transform name if null incoming one is given.
	 * @param string|null $fileTransformName file transforms name.
	 * @return string actual file transform name.
	 */
	protected function fetchFileTransformName($fileTransformName = null) {
		if (is_null($fileTransformName)) {
			$fileTransformName = $this->getDefaultFileTransformName();
		}
		return $fileTransformName;
	}

	/**
	 * Initializes the default {@link defaultFileTransform} value.
	 * @return boolean success.
	 */
	protected function initDefaultFileTransformName() {
		$fileTransforms = $this->ensureFileTransforms();
		if (isset($fileTransforms[0])) {
			$defaultFileTransformName = $fileTransforms[0];
		} else {
			$transformNames = array_keys($fileTransforms);
			$defaultFileTransformName = array_shift($transformNames);
		}
		$this->_defaultFileTransformName = $defaultFileTransformName;
		return true;
	}

	/**
	 * Returns the {@link fileTransforms} value, making sure it is valid.
	 * @throws CException if file transforms value is invalid.
	 * @return array file transforms.
	 */
	protected function ensureFileTransforms() {
		$fileTransforms = $this->getFileTransforms();
		if (empty($fileTransforms)) {
			throw new CException('File transformations list is empty.');
		}
		return $fileTransforms;
	}

	/**
	 * Overridden.
	 * Creates the file for the model from the source file.
	 * File version and extension are passed to this method.
	 * Parent method is overridden in order to save several different files
	 * per one particular model.
	 * @param string $sourceFileName - source full file name.
	 * @param integer $fileVersion - file version number.
	 * @param string $fileExtension - file extension.
	 * @return boolean success.
	 */
	protected function newFile($sourceFileName, $fileVersion, $fileExtension) {
		$fileTransforms = $this->ensureFileTransforms();

		$fileStorageBucket = $this->getFileStorageBucket();
		$result = true;
		foreach ($fileTransforms as $fileTransformName => $fileTransform) {
			if (!is_array($fileTransform) && is_numeric($fileTransformName)) {
				$fileTransformName = $fileTransform;
			}

			$fileFullName = $this->getFileFullName($fileTransformName, $fileVersion, $fileExtension);

			if (is_array($fileTransform)) {
				$transformTempFilePath = $this->resolveTransformTempFilePath();
				$tempTransformFileName = basename($fileFullName);
				$tempTransformFileName = uniqid(rand()) . '_' . $tempTransformFileName;
				$tempTransformFileName = $transformTempFilePath . DIRECTORY_SEPARATOR . $tempTransformFileName;
				$resizeResult = $this->transformFile($sourceFileName, $tempTransformFileName, $fileTransform);
				if ($resizeResult) {
					$copyResult = $fileStorageBucket->copyFileIn($tempTransformFileName, $fileFullName);
					$result = $result && $copyResult;
				} else {
					$result = $result && $resizeResult;
				}
				if (file_exists($tempTransformFileName)) {
					unlink($tempTransformFileName);
				}
			} else {
				$copyResult = $fileStorageBucket->copyFileIn($sourceFileName, $fileFullName);
				$result = $result && $copyResult;
			}
		}
		return $result;
	}

	/**
	 * Generates the temporary file path for the file transformations
	 * and makes sure it exists.
	 * @throws CException if fails.
	 * @return string temporary full file path.
	 */
	protected function resolveTransformTempFilePath() {
		$filePath = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . get_class($this) . DIRECTORY_SEPARATOR . get_class($this->getOwner());
		if (!file_exists($filePath)) {
			$oldUmask = umask(0);
			@mkdir($filePath, 0777, true);
			umask($oldUmask);
		}
		if (!file_exists($filePath) || !is_dir($filePath)) {
			throw new CException("Unable to resolve temporary file path: '{$filePath}'!");
		} elseif (!is_writable($filePath)) {
			throw new CException("Path: '{$filePath}' should be writeable!");
		}
		return $filePath;
	}

	/**
	 * Overridden.
	 * Deletes file associated with the model without any checks.
	 * This method is overridden because single model can have several files.
	 * @return boolean success.
	 */
	protected function unlinkFile() {
		$fileTransforms = $this->ensureFileTransforms();
		$result = true;
		$fileStorageBucket = $this->getFileStorageBucket();
		foreach ($fileTransforms as $fileTransformName => $fileTransform) {
			if (!is_array($fileTransform) && is_numeric($fileTransformName)) {
				$fileTransformName = $fileTransform;
			}
			$fileName = $this->getFileFullName($fileTransformName);
			if ($fileStorageBucket->fileExists($fileName)) {
				$fileDeleteResult = $fileStorageBucket->deleteFile($fileName);
				$result = $result && $fileDeleteResult;
			}
		}
		return $result;
	}

	/**
	 * Transforms source file to destination file according to the transformation settings.
	 * @param string $sourceFileName is the full source file system name.
	 * @param string $destinationFileName is the full destination file system name.
	 * @param mixed $transformSettings is the transform settings data, its value is retrieved from {@link fileTransforms}
	 * @return boolean success.
	 */
	protected function transformFile($sourceFileName, $destinationFileName, $transformSettings) {
		$arguments = func_get_args();
		return call_user_func_array($this->getTransformCallback(), $arguments);
	}

	// File Interface Function Shortcuts:

	/**
	 * Checks if file related to the model exists.
	 * @param string $name transformation name
	 * @return boolean file exists.
	 */
	public function fileExists($name=null) {
		$fileStorageBucket = $this->getFileStorageBucket();
		return $fileStorageBucket->fileExists($this->getFileFullName($name));
	}

	/**
	 * Returns the content of the model related file.
	 * @param string $name transformation name
	 * @return string file content.
	 */
	public function getFileContent($name=null) {
		$fileStorageBucket = $this->getFileStorageBucket();
		return $fileStorageBucket->getFileContent($this->getFileFullName($name));
	}

	/**
	 * Returns full web link to the model's file.
	 * @param string $name transformation name
	 * @return string web link to file.
	 */
	public function getFileUrl($name=null) {
		$fileStorageBucket = $this->getFileStorageBucket();
		$fileFullName = $this->getFileFullName($name);
		$defaultFileUrl = $this->getDefaultFileUrl($name);
		if (!empty($defaultFileUrl)) {
			if (!$fileStorageBucket->fileExists($fileFullName)) {
				return $defaultFileUrl;
			}
		}
		/***** Tur Feature ****/
		if (substr($fileFullName, strrpos($fileFullName, '.') + 1) == 'bmp') {
			$this->saveFile($fileStorageBucket->getFullFileName($this->getFileFullName($name)));
			$fileFullName = $this->getFileFullName($name);
		}
		if (!$fileStorageBucket->fileExists($fileFullName) && $name != 'origin') {
			$sourceFileName = $fileStorageBucket->getFullFileName($this->getFileFullName('origin'));
			$fileTransforms = $this->ensureFileTransforms();
			if (!empty($fileTransforms[$name])) {
				$fileTransform = $fileTransforms[$name];
				$fileStorageBucket = $this->getFileStorageBucket();
				if (is_array($fileTransform)) {
					$transformTempFilePath = $this->resolveTransformTempFilePath();
					$tempTransformFileName = basename($fileFullName);
					$tempTransformFileName = uniqid(rand()) . '_' . $tempTransformFileName;
					$tempTransformFileName = $transformTempFilePath . DIRECTORY_SEPARATOR . $tempTransformFileName;
					$resizeResult = $this->transformFile($sourceFileName, $tempTransformFileName, $fileTransform);
					if ($resizeResult) {
						$copyResult = $fileStorageBucket->copyFileIn($tempTransformFileName, $fileFullName);
					}
					if (file_exists($tempTransformFileName)) {
						unlink($tempTransformFileName);
					}
				} else {
					$copyResult = $fileStorageBucket->copyFileIn($sourceFileName, $fileFullName);
				}
			}
		}
		/***** Tur Feature ****/

		return $fileStorageBucket->getFileUrl($fileFullName);
	}
}
