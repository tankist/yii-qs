<?php
/**
 * QsFilterSecureConnection class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Filter, which allows access to the action using only 
 * certain connection protocol (http or https).
 * In case request is made with wrong protocol, it will
 * be redirected to the correct one, keeping route and GET parameters.
 *
 * @property boolean $useSecureConnection public alias of {@link _useSecureConnection}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.filters
 */
class QsFilterSecureConnection extends CFilter {
	/**
	 * @var boolean determines should secure or not secure protocol be applied.
	 */
	protected $_useSecureConnection = true;

	// Set / Get:
	
	public function setUseSecureConnection($useSecureConnection) {
		$this->_useSecureConnection = $useSecureConnection;
		return true;
	}

	public function getUseSecureConnection() {
		return $this->_useSecureConnection;
	}

	/**
	 * Performs the pre-action filtering.
	 * Redirects to secure or not secure protocol depending on {@link useSecureConnection} setting.
	 * @param CFilterChain $filterChain the filter chain that the filter is on.
	 * @return boolean whether the filtering process should continue and the action
	 * should be executed.
	 */
	protected function preFilter($filterChain) {
		if (Yii::app()->getComponent('request')->isSecureConnection) {
			if (!$this->_useSecureConnection) {
				$schema = 'http';
				$this->redirectToSchema($filterChain, $schema);
				return false;
			}
		} else {
			if ($this->_useSecureConnection) {
				$schema = 'https';
				$this->redirectToSchema($filterChain, $schema);
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Redirects to the requested URL with specified schema.
	 * @param CFilterChain $filterChain filter chain.
	 * @param string $schema schema to use (e.g. http, https).
	 */
	protected function redirectToSchema(CFilterChain $filterChain, $schema) {
		$controller = $filterChain->controller;
		$action = $filterChain->action;
		$route = "{$controller->id}/{$action->id}";
		$params = $_GET;
		$controller->redirect($controller->createAbsoluteUrl($route, $params, $schema));
		return true;
	}
}