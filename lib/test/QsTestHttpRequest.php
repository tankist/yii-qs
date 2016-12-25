<?php
/**
 * QsTestHttpRequest class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
Yii::import('qs.test.exceptions.*');

/**
 * This class has been created to support unit testing.
 * It provides the mock for the {@link CHttpRequest}.
 * This class throws an {@link QsTestExceptionRedirect} exception on call of method {@link redirect}.
 * @see QsTestExceptionRedirect
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test
 */
class QsTestHttpRequest extends CHttpRequest {

	/**
	 * Constructor.
	 * Tries to fill the object with the data from the current
	 * application {@link CHttpRequest} instance.
	 */
	public function __construct() {
		if (Yii::app()->hasComponent('request') ) {
			$currentRequest = Yii::app()->getComponent('request');
			$this->setHostInfo($currentRequest->getHostInfo());
			$this->setBaseUrl($currentRequest->getBaseUrl());
			$this->setScriptUrl($currentRequest->getScriptUrl());
		}
	}

	/**
	 * Redirects the browser to the specified URL.
	 * Calling this method will throws and {@link QsTestExceptionRedirect} exception.
	 * @param string $url URL to be redirected to. If the URL is a relative one, the base URL of
	 * the application will be inserted at the beginning.
	 * @param boolean $terminate whether to terminate the current application
	 * @param integer $statusCode the HTTP status code. Defaults to 302. See {@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html}
	 * for details about HTTP status code. This parameter has been available since version 1.0.4.
	 */
	public function redirect($url, $terminate=true, $statusCode=302) {
		if (true) {
			$callback = array($this, __FUNCTION__);
			$callbackArguments = func_get_args();
			throw new QsTestExceptionRedirect($callback, $callbackArguments);
		}
		parent::redirect($url,$terminate,$statusCode);
	}

	/**
	 * Returns the request URI portion for the currently requested URL.
	 * This refers to the portion that is after the {@link hostInfo host info} part.
	 * It includes the {@link queryString query string} part if any.
	 * This method will mock up the request URI if application is not running in the http mode.
	 * @return string the request URI portion for the currently requested URL.
	 * @throws CException if the request URI cannot be determined due to improper server configuration
	 */
	public function getRequestUri() {
		$serverBackup = $_SERVER;
		if (!array_key_exists('REQUEST_URI',$_SERVER)) {
			$result = $this->getScriptUrl();
		} elseif (!array_key_exists('HTTP_HOST',$_SERVER)) {
			$_SERVER['HTTP_HOST'] = $this->getHostInfo();
			$result = parent::getRequestUri();
		}
		$_SERVER = $serverBackup;
		return $result;
	}

	/**
	 * Returns the path info of the currently requested URL.
	 * This refers to the part that is after the entry script and before the question mark.
	 * The starting and ending slashes are stripped off.
	 * @return string part of the request URL that is after the entry script and before the question mark.
	 * Note, the returned pathinfo is decoded starting from 1.1.4.
	 * Prior to 1.1.4, whether it is decoded or not depends on the server configuration
	 * (in most cases it is not decoded).
	 * @throws CException if the request URI cannot be determined due to improper server configuration
	 */
	public function getPathInfo() {
		$serverBackup = $_SERVER;
		if (!array_key_exists('REQUEST_URI',$_SERVER)) {
			$result = $this->getRequestUri();
		} else {
			$_SERVER['PHP_SELF'] = $this->getScriptUrl();
			$result = parent::getPathInfo();
		}
		$_SERVER = $serverBackup;
		return $result;
	}
}