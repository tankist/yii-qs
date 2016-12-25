<?php
/**
 * QsActionAdminUpdate class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInsert', true);
 
/**
 * Admin panel action, which updates a particular model.
 * If update is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 * Note: this action requires controller to provide method "loadModel(mixed $id)",
 * which should retrieve the model instance by its primary key.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdate extends QsActionAdminInsert {
	/**
	 * @var string name of view which will be rendered during action.
	 */
	protected $_view = 'update';

	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id=null) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);

		$this->performAjaxValidation($model);

		$modelClassName = get_class($model);
		if (isset($_POST[$modelClassName])) {
			$model->attributes = $_POST[$modelClassName];
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