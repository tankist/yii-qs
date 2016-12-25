<?php
/**
 * QsAuthExternalUserIdentity class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalUserIdentity user identity, which provides authentication via external service.
 *
 * Example:
 * <code>
 * $userIdentity = new QsAuthExternalUserIdentity();
 * $service = new QsAuthExternalServiceSome();
 * $userIdentity->setService($service);
 * if ($userIdentity->authenticate()) {
 *     Yii::app()->getComponent('user')->login($userIdentity);
 * }
 * </code>
 *
 * @see QsAuthExternalService
 *
 * @property QsAuthExternalService $service public alias of {@link _service}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
class QsAuthExternalUserIdentity extends CBaseUserIdentity {
	const ERROR_EXTERNAL_SERVICE_AUTH_FAILED = 3;
	/**
	 * @var QsAuthExternalService external auth service instance.
	 */
	protected $_service;

	/**
	 * @param QsAuthExternalService $service external auth service.
	 * @return QsAuthExternalUserIdentity self instance.
	 */
	public function setService(QsAuthExternalService $service) {
		$this->_service = $service;
		return $this;
	}

	/**
	 * @return QsAuthExternalService external auth service.
	 */
	public function getService() {
		return $this->_service;
	}

	/**
	 * Returns the unique identifier for the identity.
	 * @return string the unique identifier for the identity.
	 */
	public function getId() {
		return $this->getState('id');
	}

	/**
	 * Returns the display name for the identity.
	 * @return string the display name for the identity.
	 */
	public function getName() {
		return $this->getState('name');
	}

	/**
	 * Authenticates the user using external auth service.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate() {
		$service = $this->getService();
		$service->authenticate();
		if ($service->isAuthenticated) {
			$attributes = $service->getAttributes();
			foreach ($attributes as $name => $value) {
				$this->setState($name, $value);
			}
			$this->setState('id', $this->fetchIdFromServiceAttributes($attributes));
			$this->setState('name', $this->fetchNameFromServiceAttributes($attributes));
			$this->setState('authServiceId', $service->getId());
			$this->setState('authServiceName', $service->getName());
			$this->errorCode = self::ERROR_NONE;
		} else {
			$this->errorCode = self::ERROR_EXTERNAL_SERVICE_AUTH_FAILED;
		}
		return !$this->errorCode;
	}

	/**
	 * Fetches the identity id from given service attributes.
	 * @param array $attributes auth service attributes.
	 * @return string identity id value.
	 */
	protected function fetchIdFromServiceAttributes(array $attributes) {
		if (array_key_exists('id', $attributes)) {
			return $attributes['id'];
		}
		return '';
	}

	/**
	 * Fetches the identity name from given service attributes.
	 * @param array $attributes auth service attributes.
	 * @return string identity name value.
	 */
	protected function fetchNameFromServiceAttributes(array $attributes) {
		$attributes = array_change_key_case($attributes, CASE_LOWER);
		// Name specified directly:
		if (array_key_exists('name', $attributes)) {
			return $attributes['name'];
		}
		// Combine name from first and last name:
		foreach ($attributes as $name => $value) {
			$canonicalName = str_replace(array(' ', '-', '_'), '', $name);
			$attributes[$canonicalName] = $value;
		}
		$nameParts = array();
		if (array_key_exists('firstname', $attributes)) {
			$nameParts[] = $attributes['firstname'];
		}
		if (array_key_exists('lastname', $attributes)) {
			$nameParts[] = $attributes['lastname'];
		}
		return implode(' ', $nameParts);
	}
}
