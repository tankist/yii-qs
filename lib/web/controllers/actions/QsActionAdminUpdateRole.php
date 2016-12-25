<?php
/**
 * QsActionAdminUpdateRole class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminUpdate', true);
 
/**
 * Admin panel action, which updates a particular model.
 * Model should has {@link QsActiveRecordBehaviorRole} behavior attached.
 * If update is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 * @see QsActiveRecordBehaviorRole
 * @see QsActionAdminUpdate
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdateRole extends QsActionAdminUpdate {
	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id=null) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);

		$modelClassName = get_class($model);

		$roleRelationName = $model->getRelationName();
		$subModelClassName = $model->getRelationConfigParam('class');

		$this->performAjaxValidation($model);

		if (isset($_POST[$modelClassName]) && isset($_POST[$subModelClassName])) {
			$model->attributes = $_POST[$modelClassName];
			$model->$roleRelationName->attributes = $_POST[$subModelClassName];
			if ($this->saveModel($model)) {
				$getParameters = $_GET;
				unset($getParameters['id']);
				$controller->redirect(array_merge(array('view', 'id'=>$model->id), $getParameters));
			}
		}

		$controller->render($this->getView(), array(
			'model' => $model,
		));
	}
}