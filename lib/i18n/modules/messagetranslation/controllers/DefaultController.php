<?php
/**
 * DefaultController class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * This class is a default controller for the module {@link MessagetranslationModule}.
 * @see QsControllerTranslationBase
 * @see MessagetranslationModule
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class DefaultController extends QsControllerTranslationBase {
	/**
	 * Returns page title depending on current route.
	 * @return string page title
	 */
	public function getPageTitle() {
		switch ($this->action->id) {
			case 'index': {
				$actionTitle = 'Manage Message Translations';
				break;
			}
			default: {
				$actionTitle = ucfirst($this->action->id);
			}
		}
		return Yii::app()->name . ' - Message Translation - ' . $actionTitle;
	}

	/**
	 * Initializes the controller.
	 * This method is called by the application before the controller starts to execute.
	 * You may override this method to perform the needed initialization for the controller.
	 */
	public function init() {
		$this->sectionTitle = 'Message Translations';
		$this->breadcrumbs = array(
			'Message Translations' => array($this->defaultAction),
		);
	}

	/**
	 * Finds the image translation model by name.
	 * @throws CHttpException 404 error if model not found.
	 * @param string $id message id.
	 * @return MessageTranslation model instance.
	 */
	protected function loadModel($id) {
		$modelFinder = new MessageTranslation();
		$model = $modelFinder->findById($id);
		if (!is_object($model)) {
			throw new CHttpException(404, "Message translation id='{$id}' not found");
		}
		return $model;
	}

	/**
	 * Shows list of images.
	 * @return void
	 */
	public function actionIndex() {
		$model = new MessageTranslation();
		$filter = new MessageTranslationFilter();
		if (isset($_GET[get_class($filter)])) {
			$filter->attributes = $_GET[get_class($filter)];
		}
		$viewData = array(
			'model' => $model,
			'filter' => $filter,
		);
		$this->render('index', $viewData);
	}

	/**
	 * Updates the message translation.
	 * @param string $id - message id.
	 * @param string $language - translation language, if set will prepend corresponded attribute.
	 * @return void
	 */
	public function actionUpdate($id, $language=null) {
		$model = $this->loadModel($id);
		if (!empty($language)) {
			$model->language = $language;
		}
		if (array_key_exists(get_class($model), $_POST)) {
			$model->attributes = $_POST[get_class($model)];
			if ($model->save()) {
				$this->redirect(array('view', 'id' => $model->id));
			}
		}
		$viewData = array(
			'model' => $model,
		);
		$this->render('update', $viewData);
	}

	/**
	 * Displays message details.
	 * @param string $id - message id.
	 * @return void
	 */
	public function actionView($id) {
		$model = $this->loadModel($id);
		$viewData = array(
			'model' => $model,
		);
		$this->render('view', $viewData);
	}
}