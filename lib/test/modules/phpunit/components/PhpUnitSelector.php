<?php
/**
 * PhpUnitSelector class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitSelector supplies the PHPUnit test suite selection.
 * This component attempts to get all required parameter directly from http request.
 * 
 * Warning: for the file path parameters double urlencode should be performed in view files
 * in order to avoid problem if "AllowEncodedSlashes" is disabled at web server!
 *
 * @see PhpUnitFileSystemManager
 *
 * @property array $baseTestSuitePaths public alias of {@link _baseTestSuitePaths}.
 * @property string $basePath public alias of {@link _basePath}.
 * @property string $subPath public alias of {@link _subPath}.
 * @property string $basePathGetParamName public alias of {@link _basePathGetParamName}.
 * @property string $subPathGetParamName public alias of {@link _subPathGetParamName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitSelector extends CApplicationComponent {
	/**
	 * @var array list of allowed test suite paths in format: 'path_alias' => '/base/test/suite/path'.
	 * By default this parameter will be filled from application test directory.
	 */
	protected $_baseTestSuitePaths = null;
	/**
	 * @var string base path name.
	 */
	protected $_basePath = null;
	/**
	 * @var string relative path under the test suite root path.
	 */
	protected $_subPath = null;
	/**
	 * @var string name of the GET parameter, which is used to pass {@link basePath} value.
	 */
	protected $_basePathGetParamName = 'basepath';
	/**
	 * @var string name of the GET parameter, which is used to pass {@link subPath} value.
	 */
	protected $_subPathGetParamName = 'subpath';

	// Set / Get :

	public function setBaseTestSuitePaths(array $baseTestSuitePaths) {
		$this->_baseTestSuitePaths = $baseTestSuitePaths;
		return true;
	}

	public function getBaseTestSuitePaths() {
		if (!is_array($this->_baseTestSuitePaths)) {
			$this->initBaseTestSuitePaths();
		}
		return $this->_baseTestSuitePaths;
	}

	public function setBasePath($basePath) {
		$this->_basePath = $basePath;
		return true;
	}

	public function getBasePath() {
		if ($this->_basePath === null) {
			if (array_key_exists($this->getBasePathGetParamName(), $_GET)) {
				$this->_basePath = $_GET[$this->getBasePathGetParamName()];
			} else {
				$this->_basePath = '';
			}
		}
		return $this->_basePath;
	}

	public function setSubPath($subPath) {
		$this->_subPath = $subPath;
		return true;
	}

	public function getSubPath() {
		if ($this->_subPath === null) {
			if (array_key_exists($this->getSubPathGetParamName(), $_GET)) {
				/* Double urlencode should be performed in view files in order to avoid
				   problem if "AllowEncodedSlashes" is disabled at web server.
				   So additional urldecoding is required. */
				$this->_subPath = urldecode($_GET[$this->getSubPathGetParamName()]);
			} else {
				$this->_subPath = '';
			}
		}
		return $this->_subPath;
	}

	public function setBasePathGetParamName($basePathGetParamName) {
		if (!is_string($basePathGetParamName)) {
			throw new CException('"'.get_class($this).'::basePathGetParamName" should be a string');
		}
		$this->_basePathGetParamName = $basePathGetParamName;
		return true;
	}

	public function getBasePathGetParamName() {
		return $this->_basePathGetParamName;
	}

	public function setSubPathGetParamName($subPathGetParamName) {
		if (!is_string($subPathGetParamName)) {
			throw new CException('"'.get_class($this).'::subPathGetParamName" should be a string');
		}
		$this->_subPathGetParamName = $subPathGetParamName;
		return true;
	}

	public function getSubPathGetParamName() {
		return $this->_subPathGetParamName;
	}

	/**
	 * Adds the given paths to the {@link baseTestSuitePaths} value.
	 * @param array $additionalBaseTestSuitePath additional base test suite paths.
	 * @return boolean success.
	 */
	public function setAdditionalBaseTestSuitePaths(array $additionalBaseTestSuitePath) {
		return $this->setBaseTestSuitePaths(array_merge($this->getBaseTestSuitePaths(), $additionalBaseTestSuitePath));
	}

	/**
	 * Returns the file path for the given base test suite path name.
	 * @throws CException if no match base path found.
	 * @param string $basePathName base path name.
	 * @return string base test suite file path.
	 */
	public function getBaseTestSuitePath($basePathName) {
		$baseTestSuitePaths = $this->getBaseTestSuitePaths();
		if (!array_key_exists($basePathName, $baseTestSuitePaths)) {
			throw new CException("Unknown base test suite path '$basePathName'!");
		}
		return $baseTestSuitePaths[$basePathName];
	}

	/**
	 * Returns the currently selected base test suite path value.
	 * @return string base test suite file path.
	 */
	public function getCurrentBaseTestSuitePath() {
		return $this->getBaseTestSuitePath($this->getBasePath());
	}

	/**
	 * Returns the full path for the selected test suite.
	 * @return string test suite full path.
	 */
	public function getTestSuiteFullPath() {
		$basePath = $this->getBasePath();
		if (empty($basePath)) {
			return '';
		} else {
			return $this->getBaseTestSuitePath($basePath).DIRECTORY_SEPARATOR.$this->getSubPath();
		}
	}

	/**
	 * Initializes the {@link testSuitePath}.
	 * @return boolean success.
	 */
	protected function initBaseTestSuitePaths() {
		$baseTestSuitePaths = array();
		$applicationTestPath = Yii::getPathOfAlias('application.tests');
		$baseTestSuitePaths['unit'] = $applicationTestPath.DIRECTORY_SEPARATOR.'unit';
		$baseTestSuitePaths['functional'] = $applicationTestPath.DIRECTORY_SEPARATOR.'functional';
		$this->_baseTestSuitePaths = $baseTestSuitePaths;
		return true;
	}

	/**
	 * Returns the bread crumbs for the test path.
	 * @param string $route route for the file list.
	 * @return array bread crumb items.
	 */
	public function getBreadcrumbs($route=null) {
		if (empty($route)) {
			$route = '/'.Yii::app()->getController()->getRoute();
		}
		$basePathGetParamName = $this->getBasePathGetParamName();
		$subPathGetParamName = $this->getSubPathGetParamName();

		$breadCrumbItems = array(
			'Tests' => array($route)
		);

		$basePath = $this->getBasePath();
		if (!empty($basePath)) {
			$breadCrumbItems[$basePath] = array($route, $basePathGetParamName=>$basePath);

			$subPath = trim($this->getSubPath(), '/');
			if (!empty($subPath)) {
				$pathParts = explode(DIRECTORY_SEPARATOR, $subPath);
			} else {
				$pathParts = array();
			}

			$resolvedPath = '';
			foreach ($pathParts as $pathPart) {
				$resolvedPath .= '/'.$pathPart;
				$breadCrumbItemPath = trim($resolvedPath, '/');
				$breadCrumbItems[$pathPart] = array($route, $basePathGetParamName=>$basePath, $subPathGetParamName=>urlencode($breadCrumbItemPath));
			}
		}
		
		return $breadCrumbItems;
	}

	/**
	 * Creates the data provider, for the result of the {@link explore} method.
	 * @return CArrayDataProvider data provider object.
	 */
	public function createDataProvider() {
		$fileSystemUnits = $this->explore();
		$dataProviderConfig = array(
			'keyField' => 'name',
			'pagination' => false
		);
		$dataProvider = new CArrayDataProvider($fileSystemUnits, $dataProviderConfig);
		return $dataProvider;
	}

	/**
	 * Makes sure the {@link subPath} value pointing to the existing directory,
	 * which can be explored.
	 * @return boolean success.
	 */
	protected function adjustSubPathForExplore() {
		$subPath = $this->getSubPath();

		$testSuitePath = $this->getBaseTestSuitePath($this->getBasePath());

		while (true) {
			$path = rtrim($testSuitePath.DIRECTORY_SEPARATOR.$subPath, DIRECTORY_SEPARATOR);
			if (file_exists($path) && is_dir($path)) {
				break;
			} elseif (empty($subPath)) {
				break;
			}
			$subPath = dirname($subPath);
		}

		return $this->setSubPath($subPath);
	}

	/**
	 * Scans the currently selected test suite path.
	 * If no {@link basePath} is set returns the mock directory list for its selection.
	 * @return array set of file system unit objects.
	 */
	public function explore() {
		$basePath = $this->getBasePath();
		if (empty($basePath)) {
			$fileSystemUnits = array();
			$baseTestSuitePaths = $this->getBaseTestSuitePaths();
			ksort($baseTestSuitePaths);
			foreach($baseTestSuitePaths as $name => $path) {
				$fileSystemUnit = new PhpUnitFileSystemUnit($path);
				$fileSystemUnit->setName($name);
				$fileSystemUnits[] = $fileSystemUnit;
			}
			return $fileSystemUnits;
		} else {
			return $this->exploreTestSuiteFullPath();
		}
	}

	/**
	 * Scans the directory determined by (@link basePath} and {@link subPath} value.
	 * If composed path is invalid tries to go upper level.
	 * @return array set of file system unit objects.
	 */
	protected function exploreTestSuiteFullPath() {
		$this->adjustSubPathForExplore();
		$path = $this->getTestSuiteFullPath();
		$fileSystemExplorer = Yii::app()->getModule('phpunit')->getComponent('fileSystemManager');
		return $fileSystemExplorer->explore($path);
	}
}
