<?php
/**
 * QsActionAdminInsertRole class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInsert', true);
 
/**
 * Admin panel action, which creates a new model.
 * Model should has {@link QsActiveRecordBehaviorRole} behavior attached.
 * If creation is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 * 
 * @see QsActiveRecordBehaviorRole
 * @see QsActionAdminInsert
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminInsertRole extends QsActionAdminInsert {
	/**
	 * Runs the action.
	 */
	public function run() {
		$controller = $this->getController();

		$model = $controller->newModel();

		$modelClassName = get_class($model);

		$roleRelationName = $model->getRelationName();
		$subModelPostName = $model->getRelationConfigParam('class');

		$this->performAjaxValidation($model);

		if (isset($_POST[$modelClassName]) && isset($_POST[$subModelPostName])) {
			$model->attributes = $_POST[$modelClassName];
			$model->$roleRelationName->attributes = $_POST[$subModelPostName];
			if ($this->saveModel($model)) {
				$getParameters = $_GET;
				$controller->redirect(array_merge(array('view', 'id'=>$model->id), $getParameters));
			}
		}

		$controller->render($this->getView(), array(
			'model' => $model,
		));
	}
}