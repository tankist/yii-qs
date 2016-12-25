<?php
/**
 * QsActionAdminUpdateConfig class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);

/**
 * QsActionAdminUpdateConfig is an admin panel action, which updates the application configuration
 * using {@link QsConfigManager}.
 * If update is successful, the page will be refreshed with the flash message.
 * The view file for this action is supposed containing {@link CActiveForm} widget with the cycle
 * over $models, which contain config items.
 * Index of the input in the cycle must be equal to the config item id value.
 *
 * View example:
 * <code>
 * <?php $form = $this->beginWidget('CActiveForm'); ?>
 *     <?php foreach ($models as $model):?>
 *         <?php echo $form->labelEx($model, 'value'); ?>
 *         <div class="row">
 *             <?php echo $form->textField($model, '[' . $model->id . ']value'); ?>
 *         </div>
 *         <?php echo $form->error($model, 'value'); ?>
 *     <?php endforeach;?>
 * <?php $this->endWidget(); ?>
 * </code>
 *
 * @see QsConfigManager
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminUpdateConfig extends QsActionAdminInternalDbTransaction {
	/**
	 * @var string id of the config manager application component.
	 */
	public $configManagerComponentId = 'configManager';
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
	 * @throws CException on failure
	 * @return QsConfigManager config manager instance.
	 */
	public function getConfigManager() {
		$configManager = Yii::app()->getComponent($this->configManagerComponentId);
		if (!is_object($configManager)) {
			throw new CException('Component "' . $this->configManagerComponentId . '" is missing.');
		}
		return $configManager;
	}

	/**
	 * Runs the action.
	 */
	public function run() {
		$controller = $this->getController();
		$configManager = $this->getConfigManager();
		$configManager->restoreValues();
		$models = $configManager->getItems();
		if (!empty($_POST)) {
			$valid = true;
			foreach ($models as $model) {
				$modelName = CHtml::modelName($model);
				if (isset($_POST[$modelName][$model->id])) {
					$model->setAttributes($_POST[$modelName][$model->id]);
				}
				$valid = $valid && $model->validate();
			}
			if ($valid) {
				try {
					$this->beginInternalDbTransaction();
					$configManager->saveValues();
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