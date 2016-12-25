<?php
/**
 * QsUrlManagerDefaultParam class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Extension of the standard Yii class {@link CUrlManager}, 
 * which allows to use default GET parameter among the all URLs.
 * Default parameter will be always append to the creating URL, if such parameter is present
 * in the current $_GET.
 * This component may be useful, while creating multilingual sites, 
 * when current site language should be determined by the URL.
 *
 * Application configuration example:
 * <code>
 * array(
 *     ...
 *     'components' => array(
 *         'urlManager' => array(
 *              'class' => 'qs.web.url.UrlManagerDefaultParam',
 *              'defaultParamName' => 'lang',
 *              'urlFormat' => 'path',
 *              'showScriptName' => false,
 *              'rules' => array(
 *                  '<lang:\w+>' => 'site/index',
 *                  '<lang:\w+>/<controller:\w+>/<id:\d+>*' => '<controller>/view',
 *                  '<lang:\w+>/<controller:\w+>/<action:\w+>/<id:\d+>*' => '<controller>/<action>',
 *                  '<lang:\w+>/<controller:\w+>' => '<controller>/index',
 *                  '<lang:\w+>/<controller:\w+>/<action:\w+>*' => '<controller>/<action>',
 *              ),
 *         )
 *     )
 *     ...
 * );
 * </code>
 *
 * Usage:
 * <code>
 * $languageCode = $_GET['lang'];
 * if (empty($languageCode)) {
 *     $_GET['lang'] = 'en';
 *     Yii::app()->urlManager->redirect(Yii::app()->controller->getRoute(), $_GET);
 * }
 * </code>
 *
 * @property string $defaultParamName public alias of {@link _defaultParamName}.
 * @property boolean $isDefaultParamPrependRoute public alias of {@link _isDefaultParamPrependRoute}.
 * @property boolean $isDefaultParamNameDisplay public alias of {@link _isDefaultParamNameDisplay}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.url
 */
class QsUrlManagerDefaultParam extends CUrlManager {
	/**
	 * @var string name of the GET parameter, which should be append to all URLs,
	 * which are created with {@link createUrl}.
	 */
	protected $_defaultParamName = null;
	/**
	 * @var boolean determines if default GET parameter should be append as the leading part of route
	 * instead of being params for the controller action.
	 */
	protected $_isDefaultParamPrependRoute = false;
	/**
	 * @var boolean indicates if name of the default GET parameter should be used,
	 * while creating route. This parameter is affected only if {@link isDefaultParamPrependRoute} is set to "true".
	 */
	protected $_isDefaultParamNameDisplay = false;

	public function setDefaultParamName($defaultParamName) {
		$this->_defaultParamName = $defaultParamName;
		return true;
	}

	public function getDefaultParamName() {
		return $this->_defaultParamName;
	}

	public function setIsDefaultParamPrependRoute($isDefaultParamPrependRoute) {
		$this->_isDefaultParamPrependRoute = $isDefaultParamPrependRoute;
		return true;
	}

	public function getIsDefaultParamPrependRoute() {
		return $this->_isDefaultParamPrependRoute;
	}

	public function setIsDefaultParamNameDisplay($isDefaultParamNameDisplay) {
		$this->_isDefaultParamNameDisplay = $isDefaultParamNameDisplay;
		return true;
	}

	public function getIsDefaultParamNameDisplay() {
		return $this->_isDefaultParamNameDisplay;
	}

	/**
	 * Constructs a URL.
	 * Appends default GET parameter to the created URL, if such parameter is present in the current $_GET.
	 * @param string $route the controller and the action (e.g. article/read)
	 * @param array $params list of GET parameters (name=>value). Both the name and value will be URL-encoded.
	 * If the name is '#', the corresponding value will be treated as an anchor
	 * and will be appended at the end of the URL. This anchor feature has been available since version 1.0.1.
	 * @param string $ampersand the token separating name-value pairs in the URL. Defaults to '&'.
	 * @return string the constructed URL
	 */
	public function createUrl($route, $params=array(), $ampersand='&') {
		if (!is_array($params)) {
			$params = array();
		}
		$defaultParams = array();
		$defaultParamName = $this->getDefaultParamName();

		if (array_key_exists($defaultParamName, $params)) {
			$defaultParams[$defaultParamName] = $params[$defaultParamName];
			unset($params[$defaultParamName]);
		} else {
			if (array_key_exists($defaultParamName, $_GET)) {
				$defaultParams[$defaultParamName] = $_GET[$defaultParamName];
			}
		}

		if ($this->getIsDefaultParamPrependRoute()) {
			if ($this->getIsDefaultParamNameDisplay()) {
				$routeLead = $this->createPathInfo($defaultParams, '/', '/');
			} else {
				$routeLead = implode('/', $defaultParams);
			}
			if (!empty($routeLead)) {
				$route = $routeLead.'/'.$route;
			}
		} else {
			$params = array_merge($defaultParams, $params);
		}

		return parent::createUrl($route,$params,$ampersand);
	}
}