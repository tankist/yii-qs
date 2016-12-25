<?php
/**
 * QsTestExceptionRender class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Exception class extending {@link QsTestException}. 
 * This exception is created for the classification purposes.
 * It supposed to be thrown on the call of view render method.
 * @see QsTestException
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.exceptions
 */
class QsTestExceptionRender extends QsTestException {
	public function __construct($callback, $callbackArguments=array(), $message='render', $code=0) {
		parent::__construct($message, null, $code);
		$this->saveCallParams($callback, $callbackArguments);
	}
}