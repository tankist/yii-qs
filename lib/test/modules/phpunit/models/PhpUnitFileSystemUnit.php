<?php
/**
 * PhpUnitFileSystemUnit class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitFileSystemUnit is a model of the particular file system unit: directory or file.
 *
 * @property string $name public alias of {@link _name}.
 * @property string $path public alias of {@link _path}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitFileSystemUnit extends CModel {
	/**
	 * @var string file system unit name.
	 * By default it will be setup as base name of the {@link path}
	 */
	protected $_name = '';
	/**
	 * @var string file path to the file system unit.
	 */
	protected $_path = '';

	/**
	 * Constructor.
	 * @param string $path path to the directory, which contains the file system unit
	 */
	public function __construct($path='') {
		$this->setPath($path);
	}

	// Set / Get :
	
	public function setName($name) {
		$this->_name = $name;
		return true;
	}

	public function getName() {
		if (empty($this->_name)) {
			$this->_name = basename($this->getPath());
		}
		return $this->_name;
	}

	public function setPath($path) {
		$this->_path = $path;
		return true;
	}

	public function getPath() {
		return $this->_path;
	}

	/**
	 * Returns the full file system unit name.
	 * @return string full file name.
	 */
	public function getDirName() {
		return dirname($this->getPath());
	}

	/**
	 * Checks if file system unit is a directory.
	 * @return boolean success.
	 */
	public function getIsDir() {
		$fullName = $this->getPath();
		return is_dir($fullName);
	}

	/**
	 * Checks if file system unit is a file.
	 * @return boolean success.
	 */
	public function getIsFile() {
		$fullName = $this->getPath();
		return is_file($fullName);
	}

	/**
	 * Returns the extension of the file.
	 * @return string file extension.
	 */
	public function getExtension() {
		return CFileHelper::getExtension($this->getName());
	}

	/**
	 * Returns the verbose unit type name.
	 * @return string file extension.
	 */
	public function getType() {
		if ($this->getIsDir()) {
			return '<DIR>';
		} else {
			return $this->getExtension().' file';
		}
	}

	/**
	 * Returns the relative path to the unit from the specified based path.
	 * @param string $basePath base path.
	 * @return string relative path.
	 */
	public function getRelativePath($basePath) {
		if (!is_string($basePath)) {
			throw new CException('Base path should be a string!');
		}
		$selfPath = $this->getPath();
		$relativePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $selfPath);
		return $relativePath;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		return array(
			'name',
			'path',
		);
	}
}
