<?php
/**
 * QsTestController class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.test.exceptions.*');

/**
 * This class has been created to support unit testing.
 * It provides the mock for the {@link CController}.
 * This class throws an {@link QsTestExceptionRender} exception on call of method {@link render}.
 * @see QsTestExceptionRender
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test
 */
class QsTestController extends CController {
	/**
	 * @param string $id id of this controller
	 * @param CWebModule $module the module that this controller belongs to.
	 */
	public function __construct($id='test', $module=null) {
		parent::__construct($id, $module);
	}

	/**
	 * Renders a view with a layout.
	 *
	 * Calling this method will throws and {@link QsTestExceptionRender} exception.
	 *
	 * @param string $view name of the view to be rendered. See {@link getViewFile} for details
	 * about how the view script is resolved.
	 * @param array $data data to be extracted into PHP variables and made available to the view script
	 * @param boolean $return whether the rendering result should be returned instead of being displayed to end users.
	 * @return string the rendering result. Null if the rendering result is not required.
	 * @see renderPartial
	 * @see getLayoutFile
	 */
	public function render($view, $data=null, $return=false) {
		if (true) {
			$callback = array($this, __FUNCTION__);
			$callbackArguments = func_get_args();
			throw new QsTestExceptionRender($callback, $callbackArguments);
		}
		return parent::render($view, $data, $return);
	}
}