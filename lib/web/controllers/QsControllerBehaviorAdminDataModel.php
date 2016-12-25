<?php
/**
 * QsControllerBehaviorAdminDataModel class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for admin panel controller, which allows to create and find data models.
 * This behavior contains model class name, search model class name and search scenario name.
 * Example:
 * <code>
 * class MyController extends CController {
 *     ...
 *     public function behaviors() {
 *         return array(
 *             'dataModelBehavior' => array(
 *                 'class' => 'qs.web.controllers.QsControllerBehaviorAdminDataModel',
 *                 'modelClassName' => 'MyActiveRecordModel',
 *                 'searchModelClassName' => 'MyFilterModel',
 *             )
 *         );
 *     }
 * }
 * </code>
 *
 * @property string $modelClassName public alias of {@link _modelClassName}.
 * @property string $searchModelClassName public alias of {@link _searchModelClassName}.
 * @property string $modelSearchScenarioName public alias of {@link _modelSearchScenarioName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers
 */
class QsControllerBehaviorAdminDataModel extends CBehavior {
	/**
	 * @var string name of model class.
	 */
	protected $_modelClassName = '';
	/**
	 * @var string name of the model class, which should be used for the search.
	 * If this field is empty the {@link modelClassName} value will be used.
	 */
	protected $_searchModelClassName = '';
	/**
	 * @var string name of model search scenario.
	 */
	protected $_modelSearchScenarioName = 'search';

	// Set / Get :

	public function setModelClassName($modelClassName) {
		$this->_modelClassName = $modelClassName;
		return true;
	}

	public function getModelClassName() {
		return $this->_modelClassName;
	}

	public function setSearchModelClassName($searchModelClassName) {
		$this->_searchModelClassName = $searchModelClassName;
		return true;
	}

	public function getSearchModelClassName() {
		if (empty($this->_searchModelClassName)) {
			$this->_searchModelClassName = $this->getModelClassName();
		}
		return $this->_searchModelClassName;
	}

	public function setModelSearchScenarioName($modelSearchScenarioName) {
		$this->_modelSearchScenarioName = $modelSearchScenarioName;
		return true;
	}

	public function getModelSearchScenarioName() {
		return $this->_modelSearchScenarioName;
	}

	/**
	 * Returns the data model based on the primary key.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param mixed $pk the primary key of the model to be loaded.
	 * @return CModel new model instance.
	 */
	public function loadModel($pk) {
		$modelClassName = $this->getModelClassName();
		$modelFinder = call_user_func(array($modelClassName, 'model'));
		$model = $modelFinder->findByPk($pk);
		if ($model===null) {
			throw new CHttpException(404, 'The requested page does not exist.');
		}
		return $model;
	}

	/**
	 * Returns the new data model, with default values.
	 * Such model should be used for the insert scenario.
	 * @return CModel new model instance.
	 */
	public function newModel() {
		$modelClassName = $this->getModelClassName();
		$model = new $modelClassName();
		return $model;
	}

	/**
	 * Returns the new data model, with default values are cleared.
	 * Such model should be used for the list scenario: filter + list of records.
	 * @return CModel search model instance.
	 */
	public function newSearchModel() {
		$modelClassName = $this->getSearchModelClassName();
		$model = new $modelClassName();
		$model->setScenario($this->getModelSearchScenarioName());
		$model->unsetAttributes();  // clear any default values
		return $model;
	}
}
