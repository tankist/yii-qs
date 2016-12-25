<?php
/**
 * QsEmailPatternStorageFile class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternStorageFile is an explicit implementation of {@link QsEmailPatternStorageBase}, 
 * which use files to store email patterns.
 * Each pattern should be present as separated folder at {@link sourcePath}. 
 * This folder should contain files each per each email pattern attribute.
 * For example for the pattern named "contact" there should be the following files:
 * - "protected/views/emails/contact/fromName.php"
 * - "protected/views/emails/contact/fromEmail.php"
 * - "protected/views/emails/contact/subject.php"
 * - "protected/views/emails/contact/bodyHtml.php"
 * Attributes "subject" and "bodyHtml" are mandatory and should be always present.
 * Any other attribute files may be not defined.
 * While filling up the {@link QsEmailPattern} instance there are two options:
 * - fill the attributes with the full source file names.
 * - fill the attributes with the source file contents.
 * Use {@link fillType} to switch these options.
 * This storage is the best to use with the {@link QsEmailPatternComposerFile}.
 *
 * @see QsEmailPatternComposerFile
 *
 * @property string $sourcePath public alias of {@link _sourcePath}.
 * @property string $fileExtension public alias of {@link _fileExtension}.
 * @property string $fillType public alias of {@link _fillType}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.storages
 */
class QsEmailPatternStorageFile extends QsEmailPatternStorageBase {
	const FILL_TYPE_FILE_NAME = 'file_name';
	const FILL_TYPE_CONTENT = 'content';
	/**
	 * @var string path to the directory, which cotains pattern source files.
	 * By default will be set to <code>Yii::app()->getBasePath().'/views/emails'</code>
	 */
	protected $_sourcePath = 'emails';
	/**
	 * @var string extension of the email pattern source files.
	 */
	protected $_fileExtension = 'php';
	/**
	 * @var string determines type of the data, which fills the email pattern.
	 */
	protected $_fillType = self::FILL_TYPE_FILE_NAME;

	/**
	 * Class constructor.
	 * Sets up default {@link sourcePath}.
	 */
	public function __construct() {
		$this->setSourcePath(Yii::app()->getBasePath() . '/views/emails');
	}

	// Property Access:
	
	public function setSourcePath($sourcePath) {
		if (!is_string($sourcePath)) {
			return false;
		}
		$this->_sourcePath = $sourcePath;
		return true;
	}

	public function getSourcePath() {
		return $this->_sourcePath;
	}

	public function setFileExtension($fileExtension) {
		if (!is_string($fileExtension)) {
			return false;
		}
		$this->_fileExtension = $fileExtension;
		return true;
	}

	public function getFileExtension() {
		return $this->_fileExtension;
	}

	public function setFillType($fillType) {
		$this->_fillType = $fillType;
		return true;
	}

	public function getFillType() {
		return $this->_fillType;
	}

	/**
	 * Initializes email pattern instance, filling up its attributes with values
	 * found in storage.
	 * @throws Exception on fail.
	 * @param QsEmailPattern $patternInstance email pattern instance.
	 * @return boolean success.
	 */
	protected function initEmailPatternInstance(QsEmailPattern $patternInstance) {
		$this->setEmailPatternInstanceAttribute($patternInstance, 'from_email');
		$this->setEmailPatternInstanceAttribute($patternInstance, 'from_name');
		$this->setEmailPatternInstanceAttribute($patternInstance, 'subject', true);
		$this->setEmailPatternInstanceAttribute($patternInstance, 'bodyHtml', true);
		$this->setEmailPatternInstanceAttribute($patternInstance, 'bodyText');
		return true;
	}

	/**
	 * Sets up the attribute value of the particular email pattern from the file.
	 * @param QsEmailPattern $patternInstance - email pattern instance
	 * @param string $attributeName - name of searched attribute
	 * @param boolean $mandatory - indicates if attribute value is critical (throw exception on missing file).
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	protected function setEmailPatternInstanceAttribute(QsEmailPattern $patternInstance, $attributeName, $mandatory=false) {
		$fileName = $this->getEmailPatternAttributeFileName($patternInstance, $attributeName);
		if (!file_exists($fileName)) {
			if ($mandatory) {
				throw new CException("'" . get_class($this) . "' storage fails: file '{$fileName}' does not exists!");
			}
			return false;
		} else {
			switch ($this->getFillType()) {
				case self::FILL_TYPE_FILE_NAME: {
					$attributeValue = $fileName;
					break;
				}
				case self::FILL_TYPE_CONTENT: {
					$attributeValue = file_get_contents($fileName);
					break;
				}
				default: {
					throw new CException("Unknown fill type '" . $this->getFillType() . "' has been passed to the '" . get_class($this) . "'!");
				}
			}
			$patternInstance->__set($attributeName, $attributeValue);
			return true;
		}
	}

	/**
	 * Returns name of the file, which should contain value of the particular email pattern attribute.
	 * @param QsEmailPattern $patternInstance - email pattern instance
	 * @param string $attributeName - name of searched attribute
	 * @return string file name.
	 */
	protected function getEmailPatternAttributeFileName(QsEmailPattern $patternInstance, $attributeName) {
		$fileName = $this->getSourcePath() . DIRECTORY_SEPARATOR . $patternInstance->getId() . DIRECTORY_SEPARATOR . $attributeName . '.' . $this->getFileExtension();
		return $fileName;
	}
}