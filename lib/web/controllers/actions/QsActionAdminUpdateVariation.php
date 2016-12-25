<?php
/**
 * QsActionAdminUpdateVariation class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminUpdate', true);
 
/**
 * Admin panel action, which updates a particular model with variations.
 * Model should has {@link QsActiveRecordBehaviorVariation} behavior attached.
 * If update is successful, the browser will be redirected to the 'view' page.
 * The view file for this action is supposed containing {@link CActiveForm} widget.
 *
 * Example view code:
 * <code>
 * ...
 * <?php foreach ($model->getVariationModels() as $variationKey => $variationModel): ?>
 *   <div class="view">
 *       <p class="note"><b>Data for <?php echo $variationModel->language->name; ?>:</b></p>
 *       <div class="row">
 *           <?php echo $form->labelEx($variationModel, "[{$variationKey}]title"); ?>
 *           <?php echo $form->textField($variationModel, "[{$variationKey}]title", array('size'=>60,'maxlength'=>128)); ?>
 *           <?php echo $form->error($variationModel, "[{$variationKey}]title"); ?>
 *       </div>
 *       <div class="row">
 *           <?php echo $form->labelEx($variationModel, "[{$variationKey}]meta_description"); ?>
 *           <?php echo $form->textArea($variationModel, "[{$variationKey}]meta_description", array('cols'=>60,'rows'=>3)); ?>
 *           <?php echo $form->error($variationModel, "[{$variationKey}]meta_description"); ?>
 *       </div>
 *       <div class="row">
 *           <?php echo $form->labelEx($variationModel, "[{$variationKey}]content"); ?>
 *           <?php echo $form->textArea($variationModel, "[{$variationKey}]content", array('cols'=>60,'rows'=>10)); ?>
 *           <?php echo $form->error($variationModel, "[{$variationKey}]content"); ?>
 *       </div>
 *   </div>
 * <?php endforeach; ?>
 * ...
 * </code>
 *
 * @see QsActiveRecordBehaviorVariation
 * @see QsActionAdminUpdate
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdateVariation extends QsActionAdminUpdate {
	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id=null) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);

		$this->performAjaxValidation($model);

		$modelClassName = get_class($model);
		$variationClassName = $model->getRelationConfigParam('class');
		if (isset($_POST[$modelClassName]) || isset($_POST[$variationClassName])) {
			if (isset($_POST[$modelClassName])) {
				$model->attributes = $_POST[$modelClassName];
			}
			if (isset($_POST[$variationClassName]) && is_array($_POST[$variationClassName])) {
				$variationModels = $model->getVariationModels();
				foreach ($variationModels as $variationModelKey => $variationModel) {
					if (isset($_POST[$variationClassName][$variationModelKey])) {
						$variationModel->attributes = $_POST[$variationClassName][$variationModelKey];
					}
				}
			}
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