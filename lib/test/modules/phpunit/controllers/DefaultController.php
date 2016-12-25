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
 * This class is a default controller for the module {@link PhpunitModule}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class DefaultController extends CController {
	/**
	 * @var string layout view name.
	 */
	public $layout = '/layouts/main';

	/**
	 * Returns page title depending on current route.
	 * @return string page title
	 */
	public function getPageTitle() {
		switch ($this->action->id) {
			case 'index': {
				$actionTitle = 'Select test';
				break;
			}
			default: {
				$actionTitle = ucfirst($this->action->id);
			}
		}
		return Yii::app()->name.' - PHPUnit - '.$actionTitle;
	}

	/**
	 * Displays the error page
	 */
	public function actionError() {
		if ($error=Yii::app()->errorHandler->error) {
			if (Yii::app()->request->isAjaxRequest) {
				echo $error['message'];
			} else {
				$this->render('error', $error);
			}
		}
	}

	/**
	 * Displays the login page
	 */
	public function actionLogin() {
		$model = Yii::createComponent('phpunit.models.PhpUnitLoginForm');
		if (isset($_POST[get_class($model)])) {
			$model->attributes = $_POST[get_class($model)];
			if ($model->login()) {
				$this->redirect( Yii::app()->getComponent('user')->getReturnUrl( array('default/index') ) );
			}
		}
		$this->render('login',array('model'=>$model));
	}

	/**
	 * Logs out the current user and redirect to homepage.
	 */
	public function actionLogout() {
		Yii::app()->getComponent('user')->logout(false);
		$this->redirect(array('index'));
	}

	/**
	 * Provide the test selection.
	 * @return void
	 */
	public function actionIndex() {
		$selector = $this->getModule()->getComponent('selector');
		$viewData = array(
			'selector' => $selector,
		);
		$this->render('index', $viewData);
	}

	/**
	 * Runs the unit test, specified by path parameter.
	 */
	public function actionRun() {
		$selector = $this->getModule()->getComponent('selector');
		$basePath = $selector->getBasePath();
		if (empty($basePath)) {
			throw new CHttpException(400, 'Test has not been selected correctly');
		} else {
			$runner = $this->getModule()->getComponent('runner');
			$runner->runTest();
			$viewData = array(
				'log' => $runner->getLogManager()->readLog(),
				'consoleCommandOutput' => $runner->getConsoleCommandManager()->getConsoleCommandOutput(),
			);
			$this->render('run', $viewData);
		}
	}
}