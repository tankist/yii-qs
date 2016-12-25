<?php
/**
 * QsTestExceptionRedirect class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Exception class extending {@link QsTestException}. 
 * This exception is created for the classification purposes.
 * It supposed to be thrown on the page redirect function.
 * @see QsTestException
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.exceptions
 */
class QsTestExceptionRedirect extends QsTestException {
	public function __construct($callback, $callbackArguments=array(), $message='redirect', $code=0) {
		parent::__construct($message, null, $code);
		$this->saveCallParams($callback, $callbackArguments);
	}
}