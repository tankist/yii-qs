<?php
/**
 * QsActionAdminView class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminBase', true);
 
/**
 * Admin panel action, which displays a particular model.
 * The view file for this action is supposed containing {@link CDetailView} widget.
 * Note: this action requires controller to provide method "loadModel(mixed $id)",
 * which should retrieve the model instance by its primary key.
 * You can use {@link QsControllerBehaviorAdminDataModel} behavior with this action.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
class QsActionAdminView extends QsActionAdminBase {
	/**
	 * @var string name of view which will be rendered during action.
	 */
	protected $_view = 'view';

	/**
	 * Runs the action.
	 * @param mixed $id - model primary key
	 */
	public function run($id) {
		$controller = $this->getController();
		$model = $controller->loadModel($id);
		$controller->render($this->getView(), array(
			'model' => $model,
		));
	}
}