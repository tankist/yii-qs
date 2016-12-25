<?php
/**
 * QsControllerBehaviorAdminDataModelContext class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.QsControllerBehaviorAdminDataModel');
 
/**
 * Behavior for admin panel controller, which extends {@link QsControllerBehaviorAdminDataModel}.
 * This behavior allows management in some filtering context. 
 * For example: items per specific category, comments by particular user etc.
 * This behavior finds and creates models including possible filtering context.
 * Example:
 * <code>
 * class MyController extends CController {
 *     ...
 *     public function behaviors() {
 *         return array(
 *             'dataModelBehavior' => array(
 *                 'class' => 'qs.web.controllers.QsControllerBehaviorAdminDataModelContext',
 *                 'modelClassName' => 'ItemModel',
 *                 'contexts' => array(
 *                     'category' => array(
 *                         'class' => 'CategoryModel',
 *                         'foreignKeyName' => 'category_id',
 *                         'controllerId' => 'category',
 *                         'controllerTitle' => 'Categories'
 *                     ),
 *                 ),
 *             )
 *         );
 *     }
 * }
 * </code>
 * 
 * @see QsControllerBehaviorAdminDataModel
 *
 * @property array $contexts public alias of {@link _contexts}.
 * @property array $activeContexts public alias of {@link _activeContexts}.
 * @property boolean $initialized public alias of {@link _initialized}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers
 */
class QsControllerBehaviorAdminDataModelContext extends QsControllerBehaviorAdminDataModel {
	/**
	 * @var array specifies possible contexts.
	 * The array key is considered as context name, value - as context config.
	 * Config should contain following keys:
	 * 'class' - class name of context model,
	 * 'foreignKeyName' - name of model attribute, which refers to the context model primary key,
	 * 'controllerId' - id of controller, which manage context models.
	 * For example:
	 * <code>
	 * array(
	 *     'user' => array(
	 *         'class' => 'User',
	 *         'foreignKeyName' => 'user_id',
	 *         'controllerId' => 'user',
	 *     )
	 * );
	 * </code>
	 */
	protected $_contexts = array();
	/**
	 * @var array stores the active context, which means the ones, which passed through the GET.
	 * Content of this array will be similar to {@link contexts}, but each value will contains
	 * key 'model'. This key contains the instance of the context model.
	 */
	protected $_activeContexts = array();
	/**
	 * @var boolean indicates if {@link activeContexts} have been initialized.
	 */
	protected $_initialized = false;

	// Set / Get:

	public function setInitialized($initialized) {
		$this->_initialized = $initialized;
		return true;
	}

	public function getInitialized() {
		return $this->_initialized;
	}

	// Contexts:

	public function setContexts(array $contexts) {
		$this->_contexts = array();
		foreach ($contexts as $contextName => $contextConfig) {
			$this->addContext($contextName, $contextConfig);
		}
		return true;
	}

	public function getContexts() {
		return $this->_contexts;
	}

	public function addContext($name, array $config) {
		if (!array_key_exists('class', $config)) {
			throw new CException( '"'.get_class($this).'::'.__FUNCTION__.'" fails: parameter "class" has not been specified!' );
		}
		if (!array_key_exists('foreignKeyName', $config)) {
			$config['foreignKeyName'] = strtolower($config['class']).'_id';
		}
		if (!array_key_exists('controllerId', $config)) {
			$config['controllerId'] = strtolower($config['class']);
		}

		$this->_contexts[$name] = $config;
		return true;
	}

	public function getContext($name=null) {
		if ($name===null) {
			reset($this->_contexts);
			return current($this->_contexts);
		} else {
			return $this->_contexts[$name];
		}
	}

	// Active contexts:

	public function setActiveContexts(array $activeContexts) {
		$this->_activeContexts = $activeContexts;
		return true;
	}

	public function getActiveContexts() {
		$this->initOnce();
		return $this->_activeContexts;
	}

	public function addActiveContext($name, array $config) {
		$this->_activeContexts[$name] = $config;
		return true;
	}

	public function getActiveContext($name=null) {
		$this->initOnce();
		if ($name===null) {
			reset($this->_activeContexts);
			return current($this->_activeContexts);
		} else {
			return $this->_activeContexts[$name];
		}
	}

	/**
	 * Initializes all active contexts.
	 * @return boolean success.
	 */
	protected function initActiveContexts() {
		foreach ($this->_contexts as $contextName => $contextConfig) {
			$foreignKeyName = $contextConfig['foreignKeyName'];
			if (array_key_exists($foreignKeyName, $_GET)) {
				$this->initActiveContext($contextName, $contextConfig, $_GET[$foreignKeyName]);
			}
		}
		return true;
	}

	/**
	 * Initializes a particular active context.
	 * @param string $contextName context name.
	 * @param array $contextConfig context configuration.
	 * @param mixed $primaryKey context model primary key value.
	 * @return boolean success.
	 */
	protected function initActiveContext($contextName, array $contextConfig, $primaryKey) {
		$className = $contextConfig['class'];
		$modelFinder = call_user_func(array($className, 'model'));
		$model = $modelFinder->findByPk($primaryKey);
		if (empty($model)) {
			throw new CHttpException(404,'The requested page does not exist.');
		}
		$contextConfig['model'] = $model;
		return $this->addActiveContext($contextName, $contextConfig);
	}

	/**
	 * Initializes all internal data once.
	 * @return boolean success.
	 */
	protected function initOnce() {
		if (!$this->getInitialized()) {
			$this->initActiveContexts();
			$this->setInitialized(true);
		}
		return true;
	}

	/**
	 * Returns model's foreign key attribute values for the active contexts.
	 * @param string $name if specified filters result for the single context.
	 * @return array active context model attributes.
	 */
	public function getActiveContextModelAttributes($name=null) {
		$result = array();
		if (!empty($name)) {
			$activeContexts = array(
				$this->getActiveContext($name)
			);
		} else {
			$activeContexts = $this->getActiveContexts();
		}
		foreach ($activeContexts as $activeContext) {
			$result[$activeContext['foreignKeyName']] = $activeContext['model']->getPrimaryKey();
		}
		return $result;
	}

	/**
	 * Returns the data model based on the primary key.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param mixed $pk the primary key of the model to be loaded.
	 * @return CModel new model instance.
	 */
	public function loadModel($pk) {
		$model = parent::loadModel($pk);
		$activeContexts = $this->getActiveContexts();
		foreach ($activeContexts as $activeContext) {
			$foreignKeyName = $activeContext['foreignKeyName'];
			if ($model->$foreignKeyName != $activeContext['model']->getPrimaryKey()) {
				throw new CHttpException(404,'The requested page does not exist.');
			}
		}

		return $model;
	}

	/**
	 * Returns the new data model, with default values.
	 * Such model should be used for the insert scenario.
	 * Context foreign keys will be set to this model.
	 * @return CModel new model instance.
	 */
	public function newModel() {
		$model = parent::newModel();
		$activeContexts = $this->getActiveContexts();
		foreach ($activeContexts as $activeContext) {
			$foreignKeyName = $activeContext['foreignKeyName'];
			$model->$foreignKeyName = $activeContext['model']->getPrimaryKey();
		}
		return $model;
	}

	/**
	 * Returns the new data model, with default values are cleared.
	 * Such model should be used for the list scenario: filter + list of records.
	 * Context foreign keys will be set to this model.
	 * @return CModel search model instance.
	 */
	public function newSearchModel() {
		$model = parent::newSearchModel();
		$activeContexts = $this->getActiveContexts();
		foreach ($activeContexts as $activeContext) {
			$foreignKeyName = $activeContext['foreignKeyName'];
			$model->$foreignKeyName = $activeContext['model']->getPrimaryKey();
		}
		return $model;
	}
}
