<?php
/**
 * PhpUnitLoginForm class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('phpunit.components.PhpUnitUserIdentity');

/**
 * This class is a login form model for the module {@link PhpunitModule}.
 * @see PhpUnitUserIdentity
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitLoginForm extends CFormModel {
	/**
	 * @var string password value.
	 */
	public $password;
	/**
	 * @var UserIdentity user identity instance
	 */
	private $_identity;

	/**
	 * @return array validation rules.
	 */
	public function rules() {
		return array(
			array('password', 'required'),
			array('password', 'authenticate'),
		);
	}

	/**
	 * Authenticates the password.
	 * This is the 'authenticate' validator as declared in rules().
	 * @param string $attribute validated attribute name.
	 * @param array $params validation parameters.
	 */
	public function authenticate($attribute,$params) {
		$this->_identity = new PhpUnitUserIdentity('yiier',$this->password);
		if (!$this->_identity->authenticate()) {
			$this->addError($attribute,'Incorrect password.');
		}
	}

	/**
	 * Logs in the user using the given password in the model.
	 * @param boolean $runValidation
	 * @return boolean whether login is successful.
	 */
	public function login($runValidation=true) {
		if (!$runValidation || $this->validate()) {
			if ($this->_identity === null) {
				$this->_identity = new PhpUnitUserIdentity('yiier', $this->password);
				$this->_identity->authenticate();
			}
			if ($this->_identity->errorCode === CBaseUserIdentity::ERROR_NONE) {
				Yii::app()->getComponent('user')->login($this->_identity);
				return true;
			}
		}
		return false;
	}
}
