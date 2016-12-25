<?php
/**
 * QsActionAdminUpdateSetting class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);
 
/**
 * Admin panel action, which updates a batch of settings, stored in the database table with pair "name, value".
 * If update is successful, the page will be refreshed with the flash message.
 * The view file for this action is supposed containing {@link CActiveForm} widget with the cycle.
 * Index of the record in the cycle must be equal to the record primary key value.
 *
 * View example:
 * <code>
 * <?php $form = $this->beginWidget('CActiveForm'); ?>
 *     <?php foreach ($models as $i => $model):?>
 *         <?php echo CHtml::label("{$model->title}:", false, array('required'=>$model->is_required) ); ?>
 *         <div class="row">
 *             <?php echo $form->textField($model, '['.$model->getPrimaryKey().']value', array('size'=>80,'maxlength'=>255)); ?>
 *         </div>
 *         <?php echo $form->error($model, "value"); ?>
 *     <?php endforeach;?>
 * <?php $this->endWidget(); ?>
 * </code>
 * Note: this action requires controller to provide method "getModelClassName()",
 * which should return the name of the model to be used.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdateSetting extends QsActionAdminInternalDbTransaction {
	/**
	 * @var string name of view which will be rendered during action.
	 */
	protected $_view = 'batch_update';
	/**
	 * @var string name of the user flash message, which should display the action success result.
	 */
	public $flashMessageKey = 'success';
	/**
	 * @var string content of the user flash message, which should be displayed if action is successful.
	 */
	public $flashMessage = 'Settings have been updated.';

	/**
	 * Runs the action.
	 */
	public function run() {
		$controller = $this->getController();

		$modelClassName = $controller->getModelClassName();

		$modelFinder = CActiveRecord::model($modelClassName);
		$models = $modelFinder->findAll();

		if (isset($_POST[$modelClassName])) {
			$valid = true;
			foreach ($models as $model) {
				if (isset($_POST[$modelClassName][$model->getPrimaryKey()])) {
					$model->attributes = $_POST[$modelClassName][$model->getPrimaryKey()];
				}
				$valid = $valid && $model->validate();
			}
			if ($valid) {
				try {
					$this->beginInternalDbTransaction();
					foreach ($models as $model) {
						$model->save();
					}
					$this->commitInternalDbTransaction();
				} catch (Exception $exception) {
					$this->rollbackInternalDbTransaction();
					throw $exception;
				}

				$this->flashResult();
				$controller->refresh();
			}

		}
		$controller->render($this->getView(), array(
			'models' => $models,
		));
	}

	/**
	 * Sets the user flash message with key {@link flashMessageKey} and
	 * content {@link flashMessage}.
	 * If any of the message parameters is empty no message will be set.
	 * @return boolean success.
	 */
	protected function flashResult() {
		$flashMessageKey = $this->flashMessageKey;
		$flashMessageContent = $this->flashMessage;
		if (!empty($flashMessageKey) && !empty($flashMessageContent)) {
			Yii::app()->getComponent('user')->setFlash($flashMessageKey, $flashMessageContent);
		}
		return true;
	}
}