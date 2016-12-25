<?php
/**
 * PhpunitModule class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpunitModule is a module that provides Web-based PHPUnit tests running.
 *
 * Note: this module is only a wrapper for the PHPUnit console utility,
 * make sure you have PHPUnit installed on your web server before running this module.
 *
 * To use PhpunitModule, you must include it as a module in the application configuration like the following:
 * <pre>
 * return array(
 *     ......
 *     'modules' => array(
 *         'phpunit' => array(
 *             'class' => 'qs.test.modules.phpunit.PhpunitModule',
 *             'password' => ***choose a password***
 *         ),
 *     ),
 * )
 * </pre>
 *
 * @see https://github.com/sebastianbergmann/phpunit
 * @see http://pear.phpunit.de/
 *
 * @property string $assetsUrl public alias of {@link _assetsUrl}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpunitModule extends CWebModule {
	/**
	 * @var string the password that can be used to access PhpunitModule.
	 * If this property is set false, then PhpunitModule can be accessed without password
	 * (DO NOT DO THIS UNLESS YOU KNOW THE CONSEQUENCE!!!)
	 */
	public $password;
	/**
	 * @var array the IP filters that specify which IP addresses are allowed to access PhpunitModule.
	 * Each array element represents a single filter. A filter can be either an IP address
	 * or an address with wildcard (e.g. 192.168.0.*) to represent a network segment.
	 * If you want to allow all IPs to access gii, you may set this property to be false
	 * (DO NOT DO THIS UNLESS YOU KNOW THE CONSEQUENCE!!!)
	 * The default value is array('127.0.0.1', '::1'), which means GiiModule can only be accessed
	 * on the localhost.
	 */
	public $ipFilters = array('127.0.0.1', '::1');
	/**
	 * @var string base URL that contains all published asset files of the module.
	 */
	protected $_assetsUrl = null;

	/**
	 * @param string $value the base URL that contains all published asset files of the module.
	 * @return boolean success
	 */
	public function setAssetsUrl($value) {
		$this->_assetsUrl = $value;
		return true;
	}

	/**
	 * @return string the base URL that contains all published asset files of the module.
	 */
	public function getAssetsUrl() {
		if ($this->_assetsUrl===null) {
			$this->initAssetsUrl();
		}
		return $this->_assetsUrl;
	}

	/**
	 * Initializes the {@link assetsUrl} value.
	 * @return boolean success.
	 */
	protected function initAssetsUrl() {
		$this->_assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias('phpunit.assets'));
		return true;
	}

	/**
	 * Initializes the module.
	 */
	public function init() {
		parent::init();
		Yii::app()->setComponents(
			array(
				'errorHandler' => array(
					'class' => 'CErrorHandler',
					'errorAction' => 'phpunit/default/error',
				),
				'user' => array(
					'class' => 'CWebUser',
					'stateKeyPrefix' => 'phpunit',
					'loginUrl' => Yii::app()->createUrl('phpunit/default/login'),
				),
			),
			false
		);
		$this->importCoreClasses();
		$this->registerCoreComponents();
	}

	/**
	 * Performs access check to gii.
	 * This method will check to see if user IP and password are correct if they attempt
	 * to access actions other than "default/login" and "default/error".
	 * @param CController $controller the controller to be accessed.
	 * @param CAction $action the action to be accessed.
	 * @return boolean whether the action should be executed.
	 */
	public function beforeControllerAction($controller, $action) {
		if (parent::beforeControllerAction($controller, $action)) {
			$route = $controller->id.'/'.$action->id;
			if (!$this->allowIp(Yii::app()->request->userHostAddress) && $route!=='default/error') {
				throw new CHttpException(403, "You are not allowed to access this page.");
			}
			$publicPages = array(
				'default/login',
				'default/error',
			);
			if ($this->password!==false && Yii::app()->getComponent('user')->getIsGuest() && !in_array($route,$publicPages)) {
				Yii::app()->getComponent('user')->loginRequired();
			} else {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks to see if the user IP is allowed by {@link ipFilters}.
	 * @param string $ip the user IP
	 * @return boolean whether the user IP is allowed by {@link ipFilters}.
	 */
	protected function allowIp($ip) {
		if (empty($this->ipFilters)) {
			return true;
		}
		foreach ($this->ipFilters as $filter) {
			if ($filter==='*' || $filter===$ip || (($pos=strpos($filter,'*'))!==false && !strncmp($ip,$filter,$pos))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Registers the core application components.
	 */
	protected function registerCoreComponents() {
		$moduleName = $this->getName();

		$components = array(
			'configManager' => array(
				'class' => "{$moduleName}.components.PhpUnitConfigManager",
			),
			'logManager' => array(
				'class' => "{$moduleName}.components.PhpUnitLogManager",
			),
			'consoleCommandManager' => array(
				'class' => "{$moduleName}.components.PhpUnitConsoleCommandManager",
			),
			'runner' => array(
				'class' => "{$moduleName}.components.PhpUnitRunner",
			),
			'fileSystemManager' => array(
				'class' => "{$moduleName}.components.PhpUnitFileSystemManager",
			),
			'selector' => array(
				'class' => "{$moduleName}.components.PhpUnitSelector",
			),
		);

		$this->setComponents($components);
	}

	/**
	 * Imports the core classes.
	 */
	protected function importCoreClasses() {
		$moduleName = $this->getName();
		Yii::import("{$moduleName}.models.*");
	}
}
