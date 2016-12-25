<?php
/**
 * QsImageTranslationSourceFileSystem class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsImageTranslationSourceFileSystem is an image translation source, based on the
 * local Linux file system.
 *
 * Application configuration example:
 * <code>
 * array(
 *     'components' => array(
 *         ...
 *         'imageTranslationSource' => array(
 *             'class' => 'QsImageTranslationSourceFileSystem',
 *             'defaultBaseUrl' => 'http://www.mydomain.com/images/i18n',
 *             'defaultBasePath' => '/home/www/mydomain/images/i18n',
 *             'baseUrl' => 'http://www.mydomain.com/materials/i18n',
 *             'basePath' => '/home/www/mydomain/materials/i18n',
 *         ),
 *         ...
 *     )
 * );
 * </code>
 *
 * @see QsImageTranslationSource
 *
 * @property string $basePath public alias of {@link _basePath}.
 * @property string $baseUrl public alias of {@link _baseUrl}.
 * @property integer $filePermission public alias of {@link _filePermission}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.images
 */
class QsImageTranslationSourceFileSystem extends QsImageTranslationSource {
	/**
	 * @var string base file system path for the translation files.
	 */
	protected $_basePath = '';
	/**
	 * @var string base URL for the translation files.
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

	/**
	 * Returns the full file name for the image.
	 * @param string $imageName name of the image.
	 * @param string $language the target language.
	 * @return string full file name.
	 */
	public function getFullFileName($imageName, $language) {
		return $this->getBasePath() . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $imageName;
	}

	/**
	 * Returns the full file URL for the image.
	 * @param string $imageName name of the image.
	 * @param string $language the target language.
	 * @return string full file URL.
	 */
	public function getFullFileUrl($imageName, $language) {
		return $this->getBaseUrl() . '/' . $language . '/' . $imageName;
	}

	/**
	 * Makes sure given file path exists and writeable.
	 * Attempts to create missing path.
	 * @throws CException on failure.
	 * @param string $path file path.
	 * @return string file path.
	 */
	protected function resolvePath($path) {
		if (!file_exists($path)) {
			$oldUmask = umask(0);
			$dirPermission = $this->getFilePermission();
			@mkdir($path, $dirPermission, true);
			umask($oldUmask);
		}
		if (!file_exists($path) || !is_dir($path)) {
			throw new CException("Unable to resolve path: '{$path}'!");
		} elseif (!is_writable($path)) {
			throw new CException("Path: '{$path}' should be writeable!");
		}
		return $path;
	}

	/**
	 * Loads the image translation for the specified language.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return string the target image URL.
	 */
	protected function loadImageTranslation($imageName, $language) {
		return $this->getFullFileUrl($imageName, $language);
	}

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return boolean image translation exists.
	 */
	protected function imageTranslationExists($imageName, $language) {
		$fullFileName = $this->getFullFileName($imageName, $language);
		return file_exists($fullFileName);
	}

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @param string $srcFileName the source file name.
	 * @return boolean image translation exists.
	 */
	protected function saveImageTranslation($imageName, $language, $srcFileName) {
		$fullFileName = $this->getFullFileName($imageName, $language);
		$path = dirname($fullFileName);
		$this->resolvePath($path);
		if (file_exists($fullFileName)) {
			unlink($fullFileName);
		}
		copy($srcFileName, $fullFileName);
		chmod($fullFileName, $this->getFilePermission());
		return true;
	}
}
