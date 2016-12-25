<?php
/**
 * QsActionAdminInsert class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);
 
/**
 * Admin panel action, which creates a new model.
 * If creation is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 * Note: this action requires controller to provide method "newModel()",
 * which should instantiate new record model.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 *
 * @property boolean $ajaxValidationEnabled public alias of {@link _ajaxValidationEnabled}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminInsert extends QsActionAdminInternalDbTransaction {
	/**
	 * @var string name of view which will be rendered during action.
	 */
	protected $_view = 'create';
	/**
	 * @var boolean indicates if AJAX form validation is enabled.
	 */
	protected $_ajaxValidationEnabled = false;

	public function setAjaxValidationEnabled($ajaxValidationEnabled) {
		$this->_ajaxValidationEnabled = $ajaxValidationEnabled;
		return true;
	}

	public function getAjaxValidationEnabled() {
		return $this->_ajaxValidationEnabled;
	}

	/**
	 * Runs the action.
	 */
	public function run() {
		$controller = $this->getController();
		$model = $controller->newModel();

		$this->performAjaxValidation($model);

		$modelClassName = get_class($model);
		if (isset($_POST[$modelClassName])) {
			$model->attributes = $_POST[$modelClassName];
			if ($this->saveModel($model)) {
				$getParameters = $_GET;
				$controller->redirect(array_merge(array('view', 'id'=>$model->id), $getParameters));
			}
		}

		$controller->render($this->getView(), array(
			'model' => $model,
		));
	}

	/**
	 * Saves the model using transaction.
	 * Note: model will be saved
	 * @param CModel $model model to be saved
	 * @param boolean $runValidation indicates if model validation should be run.
	 * @return boolean indicates if saving was successful.
	 */
	protected function saveModel(CModel $model, $runValidation=true) {
		try {
			$this->beginInternalDbTransaction();
			$result = $model->save($runValidation);
			$this->commitInternalDbTransaction();
			return $result;
		} catch (Exception $exception) {
			$this->rollbackInternalDbTransaction();
			throw $exception;
		}
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel $model the model to be validated
	 * Set up {@link ajaxValidationEnabled} to "true" to make this method be actually performed.
	 */
	protected function performAjaxValidation($model) {
		if ( $this->getAjaxValidationEnabled() ) {
			if (isset($_POST['ajax']) && $_POST['ajax']==='model-form') {
				echo CActiveForm::validate($model);
				Yii::app()->end();
			}
		}
	}
}