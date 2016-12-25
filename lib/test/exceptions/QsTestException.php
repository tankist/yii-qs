<?php
/**
 * QsTestException class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Exception class, which is used during unit testing.
 * Such exception should be thrown on calling particular functions or methods.
 * This allows to mock these methods calls.
 * Use {@link saveCallParams} to save function call arguments into the exception object.
 * <code>
 * class SomeClass {
 *     public function someFunction($someArgument) {
 *         $exception = new QsTestException();
 *         $callback = array($this, __FUNCTION__);
 *         $callbackArguments = func_get_args();
 *         $exception->saveCallParams($callback, $callbackArguments);
 *         throw $exception;
 *     }
 * }
 * </code>
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.exceptions
 */ 
class QsTestException extends Exception {
	/**
	 * @var array - set of params bound with the exception.
	 */
	protected $_params = array();

	public function __construct($message='', $params=null, $code=0) {
		parent::__construct($message, $code);
		if (is_array($params)) {
			$this->setParams($params);
		}
	}

	public function setParams(array $params) {
		$this->_params = $params;
		return true;
	}

	public function getParams() {
		return $this->_params;
	}

	/**
	 * Fills internal {@link params} with the call arguments of the method or function.
	 * @param callback $callback - callback for the function or method.
	 * @param array $callbackArguments - list of arguments, which were passed to the callback.
	 * @return boolean success.
	 */
	public function saveCallParams($callback, array $callbackArguments=array()) {
		if (is_array($callback)) {
			list($class, $method) = $callback;
			$callbackReflection = new ReflectionMethod($class, $method);
		} else {
			$callbackReflection = new ReflectionFunction($callback);
		}
		$callbackParameters = $callbackReflection->getParameters();
		if (is_array($callbackParameters)) {
			$params = array();
			foreach ($callbackParameters as $callbackParameterIndex => $callbackParameter) {
				if (array_key_exists($callbackParameterIndex, $callbackArguments)) {
					$paramValue = $callbackArguments[$callbackParameterIndex];
				} elseif ($callbackParameter->isDefaultValueAvailable()) {
					$paramValue = $callbackParameter->getDefaultValue();
				} else {
					$paramValue = null;
				}
				$params[$callbackParameter->name] = $paramValue;
			}
			return $this->setParams($params);
		}

		return false;
	}
}