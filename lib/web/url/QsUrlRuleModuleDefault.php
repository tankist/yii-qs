<?php
/**
 * QsUrlRuleModuleDefault class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.url.QsUrlRulePostponeInit');

/**
 * Custom URL Rule, which automatically appends URL parsing and creation for the all
 * application modules at once.
 * Applying of this rule is equal to following set of common rules:
 * <code>
 * 'mymodule1' => 'mymodule1',
 * 'mymodule1/<controller:\w+>' => 'mymodule1/<controller>',
 * 'mymodule1/<controller:\w+>/<action:\w+>*' => 'mymodule1/<controller>/<action>',
 * 'mymodule2' => 'mymodule2',
 * 'mymodule2/<controller:\w+>' => 'mymodule2/<controller>',
 * 'mymodule2/<controller:\w+>/<action:\w+>*' => 'mymodule2/<controller>/<action>',
 * ...
 * </code>
 *
 * Module names will be found automatically from the application instance.
 *
 * Example URl manager config:
 * <code>
 * 'components' => array(
 *     ...
 *     'urlManager' => array(
 *         'urlFormat' => 'path',
 *         'showScriptName' =>false,
 *         'rules' => array(
 *             '/' => 'site/index',
 *             array(
 *                 'class' => 'qs.url.QsUrlRuleModuleDefault',
 *             ),
 *             '<controller:\w+>/<id:\d+>*' => '<controller>/view',
 *             '<controller:\w+>/<action:\w+>/<id:\d+>*' => '<controller>/<action>',
 *             '<controller:\w+>/<action:\w+>*' => '<controller>/<action>',
 *         ),
 *     ),
 * ),
 * </code>
 * Remember: you can always append your own URL rule for more specific actions of the module.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.url
 */
class QsUrlRuleModuleDefault extends QsUrlRulePostponeInit {
	/**
	 * @var string regular expression used to parse a URL.
	 * Note: this pattern MUST contain full module route as single parameter,
	 * with optional controller and action included in it.
	 * You can append additional parameters and options to his pattern.
	 * For example:
	 * '<lang:\w>/<moduleRoute:(\w+(\/\w+(\/\w+)?)?)>*'.
	 */
	public $pattern = '<moduleRoute:(\w+(\/\w+(\/\w+)?)?)>*';
	/**
	 * @var string the route.
	 */
	public $route = '<moduleRoute>';

	/**
	 * Creates a URL based on this rule.
	 * @param CUrlManager $manager the manager
	 * @param string $route the route
	 * @param array $params list of parameters (name=>value) associated with the route
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return mixed the constructed URL. False if this rule does not apply.
	 */
	public function createUrl($manager, $route, $params, $ampersand) {
		$this->initOnce();
		if (!empty($route)) {
			$routeParts = explode('/', $route);
			$moduleName = array_shift($routeParts);
			if (Yii::app()->hasModule($moduleName)) {
				$controllerName = array_shift($routeParts);
				$actionName = array_shift($routeParts);
				if (!empty($params)) {
					if (empty($controllerName)) {
						$defaultControllerName = Yii::app()->getModule($moduleName)->defaultController;
						$route .= '/'.$defaultControllerName;
					}
					if (empty($actionName)) {
						$defaultActionName = 'index';
						$route .= '/'.$defaultActionName;
					}
				}
				return parent::createUrl($manager, $route, $params, $ampersand);
			}
		}
		return false;
	}

	/**
	 * Parses a URL based on this rule.
	 * @param CUrlManager $manager the URL manager
	 * @param CHttpRequest $request the request object
	 * @param string $pathInfo path info part of the URL (URL suffix is already removed based on {@link CUrlManager::urlSuffix})
	 * @param string $rawPathInfo path info that contains the potential URL suffix
	 * @return mixed the route that consists of the controller ID and action ID. False if this rule does not apply.
	 */
	public function parseUrl($manager, $request, $pathInfo, $rawPathInfo) {
		$this->initOnce();
		$getBackup = $_GET;
		$requestBackup = $_REQUEST;
		$route = parent::parseUrl($manager, $request, $pathInfo, $rawPathInfo);
		if ($route!==false) {
			$routeParts = explode('/', $route);
			list($moduleName) = $routeParts;
			if (!Yii::app()->hasModule($moduleName)) {
				$_GET = $getBackup;
				$_REQUEST = $requestBackup;
				$route = false;
			}
		}
		return $route;
	}
}
