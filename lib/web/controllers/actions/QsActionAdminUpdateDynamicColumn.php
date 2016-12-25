<?php
/**
 * QsActionAdminInsertDynamicColumn class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminUpdate', true);

/**
 * Admin panel action, which updates a particular model with dynamic columns.
 * Model should has {@link QsActiveRecordBehaviorDynamicColumn} behavior attached.
 * If update is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 *
 * Example view code:
 * <code>
 * <?php foreach ($model->getColumnValueModels() as $columnName => $columnValueModel): ?>
 *   <div class="row">
 *       <?php echo $form->labelEx($columnValueModel, $model->getColumnModel($columnName)->label); ?>
 *       <?php echo $form->textField($columnValueModel,"[{$columnName}]value"); ?>
 *       <?php echo $form->error($columnValueModel,"[{$columnName}]value"); ?>
 *   </div>
 * <?php endforeach; ?>
 * </code>
 *
 * @see QsActiveRecordBehaviorDynamicColumn
 * @see QsActionAdminUpdate
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdateDynamicColumn extends QsActionAdminUpdate {
	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id=null) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);

		$this->performAjaxValidation($model);

		$modelClassName = get_class($model);
		$columnValueClassName = $model->getRelationConfigParam('class');
		if ( isset($_POST[$modelClassName]) || isset($_POST[$columnValueClassName]) ) {
			if (isset($_POST[$modelClassName])) {
				$model->attributes = $_POST[$modelClassName];
			}
			if ( isset($_POST[$columnValueClassName]) && is_array($_POST[$columnValueClassName]) ) {
				$columnValueModels = $model->getColumnValueModels();
				foreach ($columnValueModels as $columnValueModelKey => $columnValueModel) {
					if (isset($_POST[$columnValueClassName][$columnValueModelKey])) {
						$columnValueModel->attributes = $_POST[$columnValueClassName][$columnValueModelKey];
					}
				}
			}
			if ($this->saveModel($model)) {
				$getParameters = $_GET;
				unset($getParameters['id']);
				$controller->redirect( array_merge( array('view','id'=>$model->id), $getParameters ) );
			}
		}

		$controller->render($this->getView(),array(
			'model' => $model,
		));
	}
}
