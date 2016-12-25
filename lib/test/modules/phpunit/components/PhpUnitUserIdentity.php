<?php
/**
 * PhpUnitUserIdentity class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * User identity for the module {@link PhpunitModule}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitUserIdentity extends CUserIdentity {
	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate() {
		$password = Yii::app()->getModule('phpunit')->password;
		if ($password === null) {
			throw new CException('Please configure the "password" property of the "phpunit" module.');
		} elseif ($password === false || $password === $this->password) {
			$this->errorCode = self::ERROR_NONE;
		} else {
			$this->errorCode = self::ERROR_UNKNOWN_IDENTITY;
		}
		return !$this->errorCode;
	}
}