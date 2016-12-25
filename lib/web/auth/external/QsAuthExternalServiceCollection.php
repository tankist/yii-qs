<?php
/**
 * QsAuthExternalServiceCollection class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.auth.external.*');
Yii::import('qs.web.auth.external.services.*');

/**
 * QsAuthExternalServiceCollection is a storage for all external auth services in the application.
 * @see QsAuthExternalService
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'google' => array(
 *                 'class' => 'QsAuthExternalServiceGoogleOpenId',
 *             ),
 *             'facebook' => array(
 *                 'class' => 'QsAuthExternalServiceFacebookOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'facebook_client_id',
 *                     'clientSecret' => 'facebook_client_secret',
 *                 ),
 *             ),
 *             ...
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @property array $services public alias of {@link _services}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
class QsAuthExternalServiceCollection extends CApplicationComponent {
	/**
	 * @var array list of Auth services with their configuration in format: 'serviceId' => array(...)
	 */
	protected $_services = array();

	/**
	 * @param array $services services
	 * @return QsAuthExternalServiceCollection self instance.
	 */
	public function setServices(array $services) {
		$this->_services = $services;
		return $this;
	}

	/**
	 * @return array services.
	 */
	public function getServices() {
		$services = array();
		foreach ($this->_services as $id => $service) {
			$services[$id] = $this->getService($id);
		}
		return $services;
	}

	/**
	 * @param string $id service id.
	 * @return QsAuthExternalService auth service instance.
	 * @throws CException on failure.
	 */
	public function getService($id) {
		if (!array_key_exists($id, $this->_services)) {
			throw new CException("Unknown auth service '{$id}'.");
		}
		if (!is_object($this->_services[$id])) {
			$this->_services[$id] = $this->createService($id, $this->_services[$id]);
		}
		return $this->_services[$id];
	}

	/**
	 * Checks if service exists in the hub.
	 * @param string $id service id.
	 * @return boolean is service exist.
	 */
	public function hasService($id) {
		return array_key_exists($id, $this->_services);
	}

	/**
	 * Creates auth service instance from its array configuration.
	 * @param string $id auth service id.
	 * @param array $serviceConfig auth service instance configuration.
	 * @return QsAuthExternalService auth service instance.
	 */
	protected function createService($id, array $serviceConfig) {
		$serviceConfig['id'] = $id;
		return Yii::createComponent($serviceConfig);
	}
}
