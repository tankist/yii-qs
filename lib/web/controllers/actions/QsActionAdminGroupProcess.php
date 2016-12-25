<?php
/**
 * QsActionAdminGroupProcess class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);

/**
 * Admin panel action, which performs group processing of the models.
 * This action expects the group process name and the list of model keys be passed via HTTP request.
 * Action will try to invoke controller method named "groupProcess*" to handle data set.
 * For example:
 * <code>
 * class SomeController {
 *     public function actions() {
 *         return array(
 *             'groupprocess' => array(
 *                 'class' => 'qs.web.controllers.actions.QsActionAdminGroupProcess',
 *             ),
 *         );
 *     }
 *     ...
 *     public function groupProcessDelete(array $models) {
 *         foreach ($models as $model) {
 *             $model->delete();
 *         }
 *     }
 * }
 * </code>
 * If no match method exists in the controller, this action may invoke the model method, which name matches
 * the given group process name (@see allowedModelMethods).
 *
 * @property string modelClassName public alias of {@link _modelClassName}.
 *
 * @see QsGridView
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminGroupProcess extends QsActionAdminInternalDbTransaction {
	/**
	 * @var string name of the request parameter, which should specify the list of model primary keys.
	 */
	public $modelKeysParamName = 'row_keys';
	/**
	 * @var string name of the request parameter, which should specify particular name of the group process.
	 */
	public $groupProcessNameParamName = 'group_process';
	/**
	 * @var array|string list of model methods, which are allowed to be invoked for group process.
	 * This parameter may be an array of allowed methods:
	 * <code>
	 * array('delete','activate');
	 * </code>
	 * or a string, which contains method names separated by comma:
	 * <code>
	 * 'delete, activate'
	 * </code>
	 * If this value set to '*', action will attempt to invoke any model method, which is given in request.
	 * Warning: do not set this parameter to '*' unless you sure no fraud actions will be performed!
	 */
	public $allowedModelMethods = array();
	/**
	 * @var string model class name.
	 * You can leave this field blank in case the owner controller provides method "getModelClassName()",
	 * which should return the model class name.
	 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
	 */
	protected $_modelClassName = '';

	public function setModelClassName($modelClassName) {
		$this->_modelClassName = $modelClassName;
		return true;
	}

	public function getModelClassName() {
		if (empty($this->_modelClassName)) {
			$this->initModelClassName();
		}
		return $this->_modelClassName;
	}

	/**
	 * Initializes the {@link modelClassName} value using the owner controller.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	protected function initModelClassName() {
		try {
			$controller = $this->getController();
			$this->_modelClassName = $controller->getModelClassName();
		} catch (CException $exception) {
			throw new CException('Unable to automatically determine model class name: '.$exception->getMessage());
		}
		return true;
	}

	/**
	 * Find the set of models by given set of keys.
	 * @param array $modelKeys model keys.
	 * @return array found models.
	 */
	protected function findModels(array $modelKeys) {
		$modelFinder = CActiveRecord::model($this->getModelClassName());
		return $modelFinder->findAllByPk($modelKeys);
	}

	/**
	 * Runs the action.
	 */
	public function run() {
		$controller = $this->getController();

		$groupProcessName = $_REQUEST[$this->groupProcessNameParamName];
		$modelKeys = $_REQUEST[$this->modelKeysParamName];

		if (!empty($groupProcessName) && is_array($modelKeys)) {
			$models = $this->findModels($modelKeys);
			if (is_array($models)) {
				try {
					$this->beginInternalDbTransaction();
					$controllerGroupProcessMethodName = 'groupProcess'.$groupProcessName;
					if (method_exists($controller,$controllerGroupProcessMethodName)) {
						$controller->$controllerGroupProcessMethodName($models);
					} elseif ($this->isModelMethodAllowed($groupProcessName)) {
						foreach ($models as $model) {
							$model->$groupProcessName();
						}
					} else {
						throw new CHttpException(400, 'Invalid request. Please do not repeat this request again.');
					}
					$this->commitInternalDbTransaction();
				} catch (Exception $exception) {
					$this->rollbackInternalDbTransaction();
					throw $exception;
				}
			}
		}

		$getParameters = $_GET;
		unset($getParameters[$this->groupProcessNameParamName]);
		unset($getParameters[$this->modelKeysParamName]);
		$controller->redirect(array_merge(array('index'), $getParameters));
	}

	/**
	 * Checks if given model method name is allowed for the group processing.
	 * @param string $methodName model group process method name.
	 * @return boolean is model method allowed.
	 */
	protected function isModelMethodAllowed($methodName) {
		if (!is_array($this->allowedModelMethods)) {
			if (!is_string($this->allowedModelMethods)) {
				throw new CException('"'.get_class($this).'::allowedModelMethods" should be array or a string!');
			} else {
				if (trim($this->allowedModelMethods) === '*') {
					return true;
				} else {
					$allowedMethods = explode(',', $this->allowedModelMethods);
					$allowedMethods = array_map('trim', $allowedMethods);
					$this->allowedModelMethods = $allowedMethods;
				}
			}
		}
		return in_array($methodName, $this->allowedModelMethods, true);
	}
}
