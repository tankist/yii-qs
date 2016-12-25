<?php
/**
 * QsFilterGetParamRequired class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsFilterGetParamRequired is a filter, which allows checking if controller action
 * has been requested with all necessary GET parameters.
 * Note: this filter only checks if parameter is present in the GET, but does not
 * perform any format checking.
 *
 * @property array $getParamNames public alias of {@link _getParamNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.filters
 */
class QsFilterGetParamRequired extends CFilter {
	/**
	 * @var array list of GET parameter names, which are required by this filter.
	 */
	protected $_getParamNames = array();

	// Set / Get :

	public function setGetParamNames(array $getParamNames) {
		$this->_getParamNames = $getParamNames;
		return true;
	}

	public function getGetParamNames() {
		return $this->_getParamNames;
	}

	/**
	 * Performs the pre-action filtering.
	 * @throws CHttpException if GET does not contain required parameters.
	 * @param CFilterChain $filterChain the filter chain that the filter is on.
	 * @return boolean whether the filtering process should continue and the action
	 * should be executed.
	 */
	protected function preFilter($filterChain) {
		foreach ($this->getGetParamNames() as $getParamName) {
			if (!array_key_exists($getParamName, $_GET)) {
				throw new CHttpException(400, 'Invalid request. Some mandatory parameters are missing.');
			}
		}
		return true;
	}
}
