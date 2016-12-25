<?php
/**
 * QsFilterRedirectNotGuest class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Filter, which will redirects authenticated (not guest) user to the specific URL,
 * instead of running action.
 *
 * @property mixed $redirectUrl public alias of {@link _redirectUrl}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.filters
 */
class QsFilterRedirectNotGuest extends CFilter {
	/**
	 * @var mixed URL for redirect.
	 */
	protected $_redirectUrl = array('account/');

	// Set / Get:
	
	public function setRedirectUrl($redirectUrl) {
		$this->_redirectUrl = $redirectUrl;
		return true;
	}

	public function getRedirectUrl() {
		return $this->_redirectUrl;
	}

	/**
	 * Performs the pre-action filtering.
	 * Redirects authenticated user to the {@link redirectUrl}.
	 * @param CFilterChain $filterChain the filter chain that the filter is on.
	 * @return boolean whether the filtering process should continue and the action
	 * should be executed.
	 */
	protected function preFilter($filterChain) {
		if (!Yii::app()->getComponent('user')->getIsGuest()) {
			$controller = $filterChain->controller;
			$controller->redirect($this->getRedirectUrl());
			return false;
		}
		return true;
	}
}