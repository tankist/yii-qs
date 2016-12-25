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
 * This class is a default controller for the module {@link ImagetranslationModule}.
 * @see QsControllerTranslationBase
 * @see ImagetranslationModule
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.imagetranslation
 */
class DefaultController extends QsControllerTranslationBase {
	/**
	 * Returns page title depending on current route.
	 * @return string page title
	 */
	public function getPageTitle() {
		switch ($this->action->id) {
			case 'index': {
				$actionTitle = 'Manage Image Translations';
				break;
			}
			default: {
				$actionTitle = ucfirst($this->action->id);
			}
		}
		return Yii::app()->name . ' - Image Translation - ' . $actionTitle;
	}

	/**
	 * Initializes the controller.
	 * This method is called by the application before the controller starts to execute.
	 * You may override this method to perform the needed initialization for the controller.
	 */
	public function init() {
		$this->sectionTitle = 'Image Translations';
		$this->breadcrumbs = array(
			'Image Translations' => array($this->defaultAction),
		);
	}

	/**
	 * Finds the image translation model by name.
	 * @throws CHttpException 404 error if model not found.
	 * @param string $name image name.
	 * @return ImageTranslation model instance.
	 */
	protected function loadModel($name) {
		$model = ImageTranslation::model()->findByName($name);
		if (!is_object($model)) {
			throw new CHttpException(404, "Image '{$name}' not found");
		}
		return $model;
	}

	/**
	 * This method is invoked right before an action is to be executed (after all possible filters.)
	 * You may override this method to do last-minute preparation for the action.
	 * @param CAction $action the action to be executed.
	 * @return boolean whether the action should be executed.
	 */
	protected function beforeAction($action) {
		/* Double urlencode should be performed in view files in order to avoid
		   problem if "AllowEncodedSlashes" is disabled at web server.
		   So additional urldecoding is required. */
		if (array_key_exists('name', $_GET)) {
			$_GET['name'] = urldecode($_GET['name']);
		}
		return true;
	}

	/**
	 * Shows list of images.
	 * @return void
	 */
	public function actionIndex() {
		$model = new ImageTranslation();
		$filter = new ImageTranslationFilter();

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
	 * Updates the image.
	 * @param string $name - image name.
	 * @param string $language - translation language, if set will prepend corresponded attribute.
	 */
	public function actionUpdate($name, $language=null) {
		$model = $this->loadModel($name);

		if (!empty($language)) {
			$model->language = $language;
		}

		if (array_key_exists(get_class($model), $_POST)) {
			$model->attributes = $_POST[get_class($model)];
			if ($model->save()) {
				$this->redirect(array('view', 'name' => urlencode($model->name)));
			}
		}

		$viewData = array(
			'model' => $model,
		);
		$this->render('update', $viewData);
	}

	/**
	 * Displays image details.
	 * @param string $name - image name.
	 * @return void
	 */
	public function actionView($name) {
		$model = $this->loadModel($name);
		$viewData = array(
			'model' => $model,
		);
		$this->render('view', $viewData);
	}
}