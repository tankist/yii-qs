<?php
/**
 * QsFileArchiverHub class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsFileArchiverHub introduces the complex file archiver, which combines
 * several different file archivers in the single facade.
 * The particular file archiver will be determined using the archive file extension
 * according to the {@link archivers} value.
 *
 * @property IQsFileArchiver[] $archivers public alias of {@link _archivers}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
class QsFileArchiverHub extends QsFileArchiver {
	/**
	 * @var IQsFileArchiver[] list of file archivers in format 'file extension' => 'file archiver'.
	 * Note: if no matching archiver will be found the first one in this list will be used.
	 */
	protected $_archivers = array(
		'tar' => array(
			'class' => 'QsFileArchiverTar'
		),
		'tbz' => array(
			'class' => 'QsFileArchiverTar'
		),
		'zip' => array(
			'class' => 'QsFileArchiverZip'
		),
		'rar' => array(
			'class' => 'QsFileArchiverRar'
		),
	);

	/**
	 * Creates file archiver instance based on the configuration array.
	 * @param array $archiverConfig - configuration array for the queue.
	 * @return IQsFileArchiver file archiver instance.
	 */
	protected function createArchiverInstance(array $archiverConfig) {
		return Yii::createComponent($archiverConfig);
	}

	/**
	 * Sets the list of available file archivers.
	 * @param array $archivers - set of file archiver instances or file archiver configurations.
	 * @return boolean success.
	 */
	public function setArchivers(array $archivers) {
		$this->_archivers = array();
		foreach ($archivers as $archiverKey => $archiverValue) {
			if (is_numeric($archiverKey) && is_string($archiverValue)) {
				$archiverFileExtension = $archiverValue;
				$archiverData = array();
			} else {
				$archiverFileExtension = $archiverKey;
				$archiverData = $archiverValue;
			}
			$this->addArchiver($archiverFileExtension, $archiverData);
		}
		return true;
	}

	/**
	 * Gets the list of available file archiver instances.
	 * @return array set of file archiver instances.
	 */
	public function getArchivers() {
		$result = array();
		foreach ($this->_archivers as $archiverFileExtension => $archiverData) {
			$result[$archiverFileExtension] = $this->getArchiver($archiverFileExtension);
		}
		return $result;
	}

	/**
	 * Gets the file archiver intance by name.
	 * @param string $archiverFileExtension - the file extension, which should be handled by the archiver.
	 * @return array set of archiver instances.
	 */
	public function getArchiver($archiverFileExtension) {
		if (!array_key_exists($archiverFileExtension, $this->_archivers)) {
			throw new CException("Archiver named '{$archiverFileExtension}' does not exists in the file archiver hub '".get_class($this)."'");
		}
		$archiverData = $this->_archivers[$archiverFileExtension];
		if (is_object($archiverData)) {
			$archiverInstance = $archiverData;
		} else {
			$archiverInstance = $this->createArchiverInstance($archiverData);
			$this->_archivers[$archiverFileExtension] = $archiverInstance;
		}
		return $archiverInstance;
	}

	/**
	 * Adds the archiver to the archivers list.
	 * @param string $archiverFileExtension - the file extension, which should be handled by the archiver.
	 * @param mixed $archiverData - archiver instance or configuration array.
	 * @return boolean success.
	 */
	public function addArchiver($archiverFileExtension, $archiverData = array()) {
		if (!is_string($archiverFileExtension)) {
			throw new CException('Name of the archiver should be a string!');
		}
		if (is_scalar($archiverData) || empty($archiverData)) {
			throw new CException('Data of the archiver should be an file archiver object or configuration array!');
		}
		$this->_archivers[$archiverFileExtension] = $archiverData;
		return true;
	}

	/**
	 * Indicates if the archiver has been set up in the archiver hub.
	 * @param string $archiverFileExtension - the file extension, which should be handled by the archiver.
	 * @return boolean success.
	 */
	public function hasArchiver($archiverFileExtension) {
		return array_key_exists($archiverFileExtension, $this->_archivers);
	}

	/**
	 * Returns the default file archiver, meaning the first one in the
	 * {@link archivers} list.
	 * @return IQsFileArchiver file archiver instance.
	 */
	protected function getDefaultArchiver() {
		$archiversList = $this->_archivers;
		$defaultArchiverName = array_shift(array_keys($archiversList));
		if (empty($defaultArchiverName)) {
			throw new CException('Unable to determine default archiver in the hub!');
		}
		$archiver = $this->getArchiver($defaultArchiverName);
		return $archiver;
	}

	/**
	 * Returns the extension of the given file.
	 * @param string $fileName - file name.
	 * @return string file extension
	 */
	protected function getFileExtension($fileName) {
		$fileExtension = CFileHelper::getExtension($fileName);
		return $fileExtension;
	}

	/**
	 * Returns the particular archiver to process the given file.
	 * @param string $archiveFileName - archive file name.
	 * @return IQsFileArchiver file archiver instance.
	 */
	protected function chooseArchiver($archiveFileName) {
		$fileExtension = $this->getFileExtension($archiveFileName);
		if ($this->hasArchiver($fileExtension)) {
			return $this->getArchiver($fileExtension);
		} else {
			return $this->getDefaultArchiver();
		}
	}

	/**
	 * Packs the files at the given path into the
	 * single archive file.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return boolean success.
	 */
	public function pack($sourcePath, $outputFileName) {
		$archiver = $this->chooseArchiver($outputFileName);
		return $archiver->pack($sourcePath, $outputFileName);
	}

	/**
	 * Unpacks the given archive file to the specified path.
	 * @param string $sourceFileName - source archive file name
	 * @param string $outputPath - output files path.
	 * @return boolean success.
	 */
	public function unpack($sourceFileName, $outputPath) {
		$archiver = $this->chooseArchiver($sourceFileName);
		return $archiver->unpack($sourceFileName, $outputPath);
	}
}
