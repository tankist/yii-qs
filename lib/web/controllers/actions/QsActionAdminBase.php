<?php
/**
 * QsActionAdminBase class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Base class fro all admin panel actions.
 * This class contains the name of view, which will be used in action.
 * This class allows usage controller's properties and methods as own ones.
 *
 * @property string $view public alias of {@link _view}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
abstract class QsActionAdminBase extends CAction {
	/**
	 * @var string name of view which will be rendered during action.
	 */
	protected $_view = '';

	public function setView($view) {
		if (!is_string($view)) {
			return false;
		}
		$this->_view = $view;
		return true;
	}

	public function getView() {
		return $this->_view;
	}

	// Controller access extension:

	public function __set($name, $value) {
		try {
			parent::__set($name,$value);
		} catch (CException $selfException) {
			$controller = $this->getController();
			if (is_object($controller)) {
				try {
					$controller->$name = $value;
				} catch (CException $controllerException) {
					throw $selfException;
				}
			} else {
				throw $selfException;
			}
		}
	}

	public function __get($name) {
		try {
			return parent::__get($name);
		} catch (CException $selfException) {
			$controller = $this->getController();
			if (is_object($controller)) {
				try {
					return $controller->$name;
				} catch (CException $controllerException) {
					throw $selfException;
				}
			} else {
				throw $selfException;
			}
		}
	}

	public function __call($name, $parameters) {
		try {
			return parent::__call($name,$parameters);
		} catch (CException $selfException) {
			$controller = $this->getController();
			if (is_object($controller)) {
				return call_user_func_array(array($controller, $name), $parameters);
			} else {
				throw $selfException;
			}
		}
	}
}