<?php
/**
 * QsActionAdminDelete class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminInternalDbTransaction', true);
 
/**
 * Admin panel action, which deletes a particular model.
 * If deletion is successful, the browser will be redirected to the 'index' page.
 * Note: this action requires controller to provide method "loadModel(mixed $id)",
 * which should retrieve the model instance by its primary key.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminDelete extends QsActionAdminInternalDbTransaction {
	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id) {
		$controller = $this->getController();

		$model = $controller->loadModel($id);

		try {
			$this->beginInternalDbTransaction();
			$model->delete();
			$this->commitInternalDbTransaction();
		} catch (Exception $exception) {
			$this->rollbackInternalDbTransaction();
			throw $exception;
		}

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if (!isset($_GET['ajax'])) {
			if (isset($_POST['returnUrl'])) {
				$redirectUrl = $_POST['returnUrl'];
			} else {
				$getParams = $_GET;
				unset($getParams['id']);
				$redirectUrl = array_merge(array('index'), $getParams);
			}
			$controller->redirect($redirectUrl);
		}
	}
}