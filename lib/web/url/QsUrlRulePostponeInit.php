<?php
/**
 * QsUrlRuleModuleDefault class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsUrlRulePostponeInit is a base class for the URL rules, which are similar to {@link CUrlRule},
 * but require postpone initialization to be configurable as common component.
 * This class introduces {@link initRelationOnce()} method, which should be called manually
 * by the descendant class.
 *
 * @see CUrlRule
 *
 * @property boolean $isInitialized public alias of {@link _isInitialized}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.url
 */
abstract class QsUrlRulePostponeInit extends CUrlRule {
	/**
	 * @var boolean indicates if URL rule has been initialized.
	 */
	protected $_isInitialized = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Override parent constructor, postponing the initialization.
	}

	/**
	 * @param boolean $isInitialized is initialized.
	 * @return boolean success.
	 */
	public function setIsInitialized($isInitialized) {
		$this->_isInitialized = $isInitialized;
		return true;
	}

	/**
	 * @return boolean is initialized.
	 */
	public function getIsInitialized() {
		return $this->_isInitialized;
	}

	/**
	 * Initializes internal parameters, making them available for quick parse/create URL.
	 * @return boolean success.
	 */
	protected function initOnce() {
		if (!$this->_isInitialized) {
			parent::__construct($this->route, $this->pattern);
			$this->_isInitialized = true;
			$this->afterInitOnce();
		}
		return true;
	}

	/**
	 * This method is invoked after {@link initRelationOnce()}.
	 * This method can be overridden in order to perform additional initialization.
	 * @return void
	 */
	protected function afterInitOnce() {
		// blank
	}
}
