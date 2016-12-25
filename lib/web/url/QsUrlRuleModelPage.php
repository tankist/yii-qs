<?php
/**
 * QsUrlRuleModelPage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.url.QsUrlRulePostponeInit');

/**
 * Custom URL Rule, which allows creation of shortcut links to the route, 
 * which display {@link CActiveRecord} model details.
 * This URL Rule is suitable for the static pages.
 * Example URl manager config: 
 * <code>
 * 'components' => array(
 *     ...
 *     'urlManager' => array(
 *         'urlFormat' => 'path',
 *         'showScriptName' => false,
 *         'rules' => array(
 *             '/' => 'site/index',
 *             array(
 *                 'class' => 'qs.web.url.QsUrlRuleModelPage',
 *                 'modelClassName' => 'StaticPage',
 *                 'modelUrlKeywordAttributeName' => 'url_keyword',
 *                 'pattern' => '<model:[\w-]+>',
 *                 'route' => 'page/view',
 *             ),
 *             '<controller:\w+>/<id:\d+>*' => '<controller>/view',
 *             '<controller:\w+>/<action:\w+>/<id:\d+>*' => '<controller>/<action>',
 *             '<controller:\w+>/<action:\w+>*' => '<controller>/<action>',
 *         ),
 *     )
 * ),
 * </code>
 * This URL rule allows correct URL parsing and creation: 
 * '/about' <=> 'page/view' + GET[model] = $staticPage
 * As the source of the models {@link CActiveRecord} class - {@link modelClassName} - is used.
 * If matched model has been found, it will be passed to the route {@link route} through the GET parameter {@link modelGetParamName}.
 *
 * @property string $modelClassName public alias of {@link _modelClassName}.
 * @property CModel[] $models public alias of {@link _models}.
 * @property integer $modelCacheDuration public alias of {@link _modelCacheDuration}.
 * @property string $modelUrlKeywordAttributeName public alias of {@link _modelUrlKeywordAttributeName}.
 * @property string $modelGetParamName public alias of {@link _modelGetParamName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.url
 */
class QsUrlRuleModelPage extends QsUrlRulePostponeInit {
	/**
	 * @var string regular expression used to parse a URL.
	 * Note: this pattern MUST contain {@link modelGetParamName} value.
	 * You can append additional parameters and options to his pattern.
	 * For example:
	 * '<lang:\w>/<model:[\w-]+>',
	 * '<model:[\w-]+>*'.
	 */
	public $pattern = '<model:[\w-]+>';
	/**
	 * @var string the controller/action pair.
	 */
	public $route = 'page/view';
	/**
	 * @var string name of the {@link CActiveRecord} model, which is used to
	 * find all model pages stored in database.
	 */
	protected $_modelClassName = 'StaticPage';
	/**
	 * @var CModel[] which stores all found {@link modelClassName} models
	 */
	protected $_models = array();
	/**
	 * @var integer duration of cache for models in seconds.
	 * '0' means never expire.
	 * Set this parameter to a negative integer to aviod caching.
	 */
	protected $_modelCacheDuration = 3600;
	/**
	 * @var string name of {@link CActiveRecord} model attribute, which stores model page access URL keyword.
	 */
	protected $_modelUrlKeywordAttributeName = 'url_keyword';
	/**
	 * @var string of the $_GET param, which is used to pass the matched model instance.
	 */
	protected $_modelGetParamName = 'model';

	// Set / Get :

	public function setModelClassName($modelClassName) {
		if (!is_string($modelClassName)) {
			throw new CException('"'.get_class($this).'::modelClassName" should be a string!');
		}
		$this->_modelClassName = $modelClassName;
		return true;
	}

	public function getModelClassName() {
		return $this->_modelClassName;
	}

	public function setModels(array $models) {
		$this->_models = $models;
		return true;
	}

	public function getModels() {
		return $this->_models;
	}

	public function setModelCacheDuration($modelCacheDuration) {
		if (!is_numeric($modelCacheDuration)) {
			throw new CException('"'.get_class($this).'::modelCacheDuration" should be an integer!');
		}
		$this->_modelCacheDuration = $modelCacheDuration;
		return true;
	}

	public function getModelCacheDuration() {
		return $this->_modelCacheDuration;
	}

	public function setModelUrlKeywordAttributeName($modelUrlKeywordAttributeName) {
		if (!is_string($modelUrlKeywordAttributeName)) {
			throw new CException('"'.get_class($this).'::modelUrlKeywordAttributeName" should be a string!');
		}
		$this->_modelUrlKeywordAttributeName = $modelUrlKeywordAttributeName;
		return true;
	}

	public function getModelUrlKeywordAttributeName() {
		return $this->_modelUrlKeywordAttributeName;
	}

	public function setModelGetParamName($modelGetParamName) {
		if (!is_string($modelGetParamName)) {
			throw new CException('"'.get_class($this).'::modelGetParamName" should be a string!');
		}
		$this->_modelGetParamName = $modelGetParamName;
		return true;
	}

	public function getModelGetParamName() {
		return $this->_modelGetParamName;
	}

	/**
	 * This method is invoked after {@link initRelationOnce()}.
	 * It checks the {@link pattern} value is correct.
	 * @return void
	 */
	protected function afterInitOnce() {
		if (!array_key_exists($this->getModelGetParamName(), $this->params)) {
			throw new CException('"'.get_class($this).'::pattern" should contain the reference to the page model GET param!');
		}
	}

	/**
	 * Finds the model, which is matched the URL keyword passed.
	 * @param string $urlKeyword - searched URL keyword.
	 * @return CActiveRecord matched model.
	 */
	protected function findModel($urlKeyword) {
		$this->initModels();
		if (array_key_exists($urlKeyword, $this->_models)) {
			return $this->_models[$urlKeyword];
		} else {
			return null;
		}
	}

	/**
	 * Initializes internal array {@link models} with the models found in database.
	 * @return boolean success.
	 */
	protected function initModels() {
		if (empty($this->_models)) {
			$models = $this->getModelsFromCache();
			if ($models===false) {
				$models=array();
				$dbModels = $this->findAllModels();
				$urlKeywordAttributeName = $this->getModelUrlKeywordAttributeName();
				foreach ($dbModels as $dbModel) {
					$models[$dbModel->$urlKeywordAttributeName] = $dbModel;
				}
			}
			$this->setModels($models);
			$this->setModelsToCache($models);
		}
		return true;
	}

	/**
	 * Finds all models for the class {@link modelClassName} in database.
	 * @return array list of page models.
	 */
	protected function findAllModels() {
		$modelFinder = CActiveRecord::model($this->getModelClassName());
		$models = $modelFinder->findAll();
		return $models;
	}

	/**
	 * Returns values saved in cache.
	 * @return mixed cached values.
	 */
	protected function getModelsFromCache() {
		if (Yii::app()->hasComponent('cache') && $this->getModelCacheDuration()>=0) {
			$modelFinder = CActiveRecord::model($this->getModelClassName()); // Make sure model class has been loaded.
			$cacheId = $this->getModelsCacheId();
			return Yii::app()->cache->get($cacheId);
		}
		return false;
	}

	/**
	 * Set values into the cache.
	 * @param CModels[] $models list of models.
	 * @return boolean success.
	 */
	protected function setModelsToCache($models) {
		if (Yii::app()->hasComponent('cache') && $this->getModelCacheDuration()>=0) {
			$cacheId = $this->getModelsCacheId();
			return Yii::app()->cache->set($cacheId, $models, $this->getModelCacheDuration());
		}
		return false;
	}

	/**
	 * Returns id of cache, which storing {@link models}.
	 * @return string cache id
	 */
	public function getModelsCacheId() {
		$cacheId = get_class($this).$this->getModelClassName();
		return $cacheId;
	}

	/**
	 * Clears models cache.
	 * @return boolean success
	 */
	public function clearModelsCache() {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getModelsCacheId();
			return Yii::app()->cache->delete($cacheId);
		}
		return true;
	}

	/**
	 * Creates a URL based on this rule.
	 * @param CUrlManager $manager the manager
	 * @param string $route the route
	 * @param array $params list of parameters (name=>value) associated with the route
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return mixed the constructed URL. False if this rule does not apply.
	 */
	public function createUrl($manager, $route, $params, $ampersand) {
		$this->initOnce();
		$modelGetParamName = $this->getModelGetParamName();
		if (!is_array($params) || !array_key_exists($modelGetParamName, $params)) {
			return false;
		}
		if (is_object($model = $params[$modelGetParamName])) {
			$urlKeywordAttributeName = $this->getModelUrlKeywordAttributeName();
			$params[$modelGetParamName] = $model->$urlKeywordAttributeName;
		}
		return parent::createUrl($manager, $route, $params, $ampersand);
	}

	/**
	 * Parses a URL based on this rule.
	 * @param CUrlManager $manager the URL manager
	 * @param CHttpRequest $request the request object
	 * @param string $pathInfo path info part of the URL (URL suffix is already removed based on {@link CUrlManager::urlSuffix})
	 * @param string $rawPathInfo path info that contains the potential URL suffix
	 * @return mixed the route that consists of the controller ID and action ID. False if this rule does not apply.
	 */
	public function parseUrl($manager, $request, $pathInfo, $rawPathInfo) {
		$this->initOnce();
		$getBackup = $_GET;
		$requestBackup = $_REQUEST;
		$route = parent::parseUrl($manager, $request, $pathInfo, $rawPathInfo);
		if ($route!==false) {
			$modelGetParamKey = $this->getModelGetParamName();
			$model = $this->findModel($_GET[$modelGetParamKey]);
			if (!empty($model)) {
				$_GET[$modelGetParamKey] = $model;
			} else {
				$_GET = $getBackup;
				$_REQUEST = $requestBackup;
				$route = false;
			}
		}
		return $route;
	}
}