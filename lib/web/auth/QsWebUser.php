<?php
/**
 * QsWebUser class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Extension of the standard Yii class {@link CWebUser}, 
 * which allows handlers for the following events:
 * {@link onAfterRestore}: raises after user data restoration;
 * {@link onBeforeLogin}: raises before user logs in;
 * {@link onAfterLogin}: raises after user successfully logged in;
 * {@link onBeforeLogout}: raises before user logs out;
 * {@link onAfterLogout}: raises after user successfully logged out;
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth
 */
class QsWebUser extends CWebUser {
	/**
	 * Initializes the application component.
	 * This method inherits the parent implementation of starting session,
	 * performing cookie-based authentication if enabled, and updating the flash variables.
	 * In addition the renewal of the user's states is performed.
	 */
	public function init() {
		parent::init();
		$this->afterRestore();
	}

	/**
	 * This method is invoked after a user data has been restored from any source:
	 * session, autologin cookies etc.
	 * The default implementation raises the {@link onAfterRestore} event.
	 * You may override this method to do postprocessing after component initialization.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterRestore() {
		if ($this->hasEventHandler('onAfterRestore')) {
			$this->onAfterRestore(new CEvent($this));
		}
	}

	/**
	 * This event is raised after the user data has been restored from any source:
	 * session, autologin cookies etc.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterRestore($event) {
		$this->raiseEvent('onAfterRestore', $event);
	}

	/**
	 * This method is called before logging in a user.
	 * You may override this method to provide additional security check.
	 * For example, when the login is cookie-based, you may want to verify
	 * that the user ID together with a random token in the states can be found
	 * in the database. This will prevent hackers from faking arbitrary
	 * identity cookies even if they crack down the server private key.
	 * @param mixed $id the user ID. This is the same as returned by {@link getId()}.
	 * @param array $states a set of name-value pairs that are provided by the user identity.
	 * @param boolean $fromCookie whether the login is based on cookie
	 * @return boolean whether the user should be logged in
	 * @since 1.1.3
	 */
	protected function beforeLogin($id, $states, $fromCookie) {
		$allowLogin = true;
		if ($this->hasEventHandler('onBeforeLogin')) {
			$eventParams = array(
				'allowLogin' => &$allowLogin,
				'id' => $id,
				'states' => $states,
				'fromCookie' => $fromCookie
			);
			$this->onBeforeLogin(new CEvent($this, $eventParams));
		}
		return $allowLogin;
	}

	/**
	 * This event is raised before logging in a user.
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeLogin($event) {
		$this->raiseEvent('onBeforeLogin', $event);
	}

	/**
	 * This method is called after the user is successfully logged in.
	 * You may override this method to do some postprocessing (e.g. log the user
	 * login IP and time; load the user permission information).
	 * @param boolean $fromCookie whether the login is based on cookie.
	 * @since 1.1.3
	 */
	protected function afterLogin($fromCookie) {
		if ($this->hasEventHandler('onAfterLogin')) {
			$eventParams = array(
				'fromCookie' => $fromCookie
			);
			$this->onAfterLogin(new CEvent($this, $eventParams));
		}
	}

	/**
	 * This event is raised after the user is successfully logged in.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterLogin($event) {
		$this->raiseEvent('onAfterLogin', $event);
	}

	/**
	 * This method is invoked when calling {@link logout} to log out a user.
	 * If this method return false, the logout action will be cancelled.
	 * You may override this method to provide additional check before
	 * logging out a user.
	 * @return boolean whether to log out the user
	 * @since 1.1.3
	 */
	protected function beforeLogout() {
		$allowLogout = true;
		if ($this->hasEventHandler('onBeforeLogout')) {
			$eventParams = array(
				'allowLogout' => &$allowLogout
			);
			$this->onBeforeLogout(new CEvent($this, $eventParams));
		}
		return $allowLogout;
	}

	/**
	 * This event is raised before logging out a user.
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeLogout($event) {
		$this->raiseEvent('onBeforeLogout', $event);
	}

	/**
	 * This method is invoked right after a user is logged out.
	 * You may override this method to do some extra cleanup work for the user.
	 * @since 1.1.3
	 */
	protected function afterLogout() {
		if ($this->hasEventHandler('onAfterLogout')) {
			$this->onAfterLogout(new CEvent($this));
		}
	}

	/**
	 * This event is raised after a user is logged out.
	 * @param CEvent $event the event parameter
	 */
	public function onAfterLogout($event) {
		$this->raiseEvent('onAfterLogout', $event);
	}
}