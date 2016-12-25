<?php
/**
 * QsWebModuleTranslationBase class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsWebModuleTranslationBase is the base web module for the translation modules.
 *
 * @property array $accessRules public alias of {@link _accessRules}.
 * @property string $assetsUrl public alias of {@link _assetsUrl}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.base
 */
class QsWebModuleTranslationBase extends CWebModule {
	/**
	 * @var string the final layout, which should hold the controller action rendering result.
	 * You may switch layout file to the main application layout from {@link CApplication::viewPath},
	 * using '//' prefix. For example: '//layouts/main'.
	 */
	public $layout = '/layouts/default';
	/**
	 * @var array access rules for the module controller.
	 * These rules will be passed to the {@link CAccessControlFilter} filter.
	 * You can use this field to setup a restrictions for the module access.
	 * For Example:
	 * <code>
	 * array(
	 *     array(
	 *         'allow',
	 *         'roles' => array('admin')
	 *     ),
	 *     array(
	 *         'deny',
	 *         'users' => array('*'),
	 *     ),
	 * );
	 * </code>
	 */
	protected $_accessRules = array();
	/**
	 * @var string base URL that contains all published asset files of the module.
	 */
	protected $_assetsUrl = null;

	// Set / Get :

	public function setAccessRules(array $accessRules) {
		$this->_accessRules = $accessRules;
		return true;
	}

	public function getAccessRules() {
		return $this->_accessRules;
	}

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
		if ($this->_assetsUrl === null) {
			$this->initAssetsUrl();
		}
		return $this->_assetsUrl;
	}

	/**
	 * Initializes the {@link assetsUrl} value.
	 * @return boolean success.
	 */
	protected function initAssetsUrl() {
		$this->_assetsUrl = Yii::app()->getAssetManager()->publish(Yii::getPathOfAlias($this->getName() . '.assets'));
		return true;
	}

	/**
	 * Initializes the module.
	 */
	public function init() {
		parent::init();
		$this->importBaseClasses();
		$this->importCoreClasses();
		$this->registerCoreComponents();
	}

	/**
	 * Imports the base classes, which are based or necessary for any translation module.
	 */
	protected function importBaseClasses() {
		$selfPath = dirname(__FILE__);
		$baseAlias = __CLASS__;
		Yii::setPathOfAlias($baseAlias, $selfPath);
		Yii::import("{$baseAlias}.components.QsTranslationLanguageManager");
		Yii::import("{$baseAlias}.components.QsControllerTranslationBase");
	}

	/**
	 * Registers the core application components.
	 */
	protected function registerCoreComponents() {
		$components = array(
			'languageManager' => array(
				'class' => 'QsTranslationLanguageManager',
			),
		);
		$this->setComponents($components);
	}

	/**
	 * Imports the core classes.
	 */
	protected function importCoreClasses() {
		/*$moduleName = $this->getName();
		Yii::import("{$moduleName}.models.*");*/
	}
}
