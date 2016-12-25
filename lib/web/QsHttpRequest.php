<?php
/**
 * QsHttpRequest class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsHttpRequest extends {@link CHttpRequest} allowing to preserve the original value of $_GET and $_REQUEST,
 * which can be overridden in case of using "friendly URLs" feature.
 * This class also provides the ability to clean up some request arrays.
 *
 * @property array $actualGet public alias of {@link _actualGet}.
 * @property array $actualRequest public alias of {@link _actualRequest}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web
 */
class QsHttpRequest extends CHttpRequest {
	/**
	 * @var array actual GET parameters from query string (separated by "?").
	 * Usage of this field make sense if you are using "friendly URLs".
	 */
	protected $_actualGet = array();
	/**
	 * @var array actual $_REQUEST value.
	 * Usage of this field make sense if you are using "friendly URLs".
	 */
	protected $_actualRequest = array();

	/**
	 * @return array
	 */
	public function getActualGet() {
		return $this->_actualGet;
	}

	/**
	 * @param array $actualGet
	 */
	public function setActualGet(array $actualGet) {
		$this->_actualGet = $actualGet;
	}

	/**
	 * @return array
	 */
	public function getActualRequest() {
		return $this->_actualRequest;
	}

	/**
	 * @param array $actualRequest
	 */
	public function setActualRequest($actualRequest) {
		$this->_actualRequest = $actualRequest;
	}

	/**
	 * Return the GET values, where {@link actualGet} values have priority above $_GET.
	 * @return array GET values.
	 */
	public function getMergedGet() {
		return CMap::mergeArray($_GET, $this->getActualGet());
	}

	/**
	 * Initializes the application component.
	 * This method overrides the parent implementation by saving initial $_GET value.
	 */
	public function init() {
		parent::init();
		$this->setActualGet($_GET);
		$this->setActualRequest($_REQUEST);
	}

	/**
	 * Cleans up the GET, removing empty entries.
	 * This method can be invoked before the view rendering to simplify the created URLs.
	 * Note: clean up make break some program logic, use this method with caution!
	 * @return QsHttpRequest self reference.
	 */
	public function cleanupGet() {
		$_GET = $this->cleanupArray($_GET);
		$this->setActualGet($this->cleanupArray($this->getActualGet()));
		return $this;
	}

	/**
	 * Cleans up the given array, removing keys with empty values.
	 * Note: for scalar types value will be considered as empty if it string length is zero.
	 * @param array $array array to be cleaned up.
	 * @return array cleaned up array.
	 */
	protected function cleanupArray(array $array) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$cleanedValue = $this->cleanupArray($value);
				if (empty($cleanedValue)) {
					unset($array[$key]);
				} else {
					$array[$key] = $cleanedValue;
				}
			} elseif (!is_object($value)) {
				if (strlen($value)==0) {
					unset($array[$key]);
				}
			}
		}
		return $array;
	}
}
