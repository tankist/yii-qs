<?php
/**
 * PhpUnitFileSystemManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitFileSystemManager allows to explore the specified directory, fetching its content.
 *
 * @see PhpUnitFileSystemUnit
 *
 * @property array $excludeNames public alias of {@link _excludeNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitFileSystemManager extends CApplicationComponent {
	/**
	 * @var array list of file system object names, which should be excluded from the exploration.
	 */
	protected $_excludeNames = array(
		'.htaccess',
		'CVS',
		'.cvsignore',
		'.svn',
		'.git',
		'.gitignore',
		'.gitkeep',
	);

	// Set / Get :

	public function setExcludeNames(array $excludeNames) {
		$this->_excludeNames = $excludeNames;
		return true;
	}

	public function getExcludeNames() {
		return $this->_excludeNames;
	}

	/**
	 * Checks if the object name is among the excluded names.
	 * @param string $name - file system object name.
	 * @return boolean name is an exclude one.
	 */
	protected function isExcludeName($name) {
		return ( in_array($name, array('.', '..')) || in_array($name, $this->getExcludeNames(), true) );
	}

	/**
	 * Scans the specified directory and composes the result as list of
	 * {@link PhpUnitFileSystemManager} models.
	 * @param string $dirName - full directory name.
	 * @return array set of file system unit objects.
	 */
	public function explore($dirName) {
		if ( !file_exists($dirName) || !is_dir($dirName) ) {
			throw new CException('"'.$dirName.'" is not a valid directory name!');
		}
		$fileSystemItems = scandir($dirName);

		$files = array();
		$directories = array();
		if (is_array($fileSystemItems)) {
			foreach ($fileSystemItems as $fileSystemItemName) {
				if ( $this->isExcludeName($fileSystemItemName) ) {
					continue;
				}
				$fileSystemUnit = new PhpUnitFileSystemUnit($dirName.DIRECTORY_SEPARATOR.$fileSystemItemName);
				if ($fileSystemUnit->getIsDir()) {
					$directories[] = $fileSystemUnit;
				} else {
					$files[] = $fileSystemUnit;
				}
			}
		}

		$fileSystemUnits = array_merge($directories, $files);
		return $fileSystemUnits;
	}

	/**
	 * Makes sure the given path exists and is writeable.
	 * @param string $path - path to be resolved.
	 * @param string $pathName - path name, which will be used to generate error messages.
	 * @throws CException if fails.
	 * @return boolean success.
	 */
	public function resolvePath($path, $pathName='Internal') {
		if (!file_exists($path)) {
			$dirPermission = 0777;
			$oldUmask = umask(0);
			@mkdir($path, $dirPermission, true);
			umask($oldUmask);
		}
		if (!file_exists($path)) {
			throw new CException("Unable to resolve {$pathName} path '{$path}'!");
		} elseif (!is_dir($path)) {
			throw new CException("{$pathName} path '{$path}' is not a directory!");
		} elseif (!is_writeable($path)) {
			throw new CException("{$pathName} path '{$path}' is not a writeable!");
		}
		return true;
	}
}
