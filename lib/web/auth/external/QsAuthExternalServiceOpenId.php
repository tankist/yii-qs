<?php
/**
 * QsAuthExternalServiceOpenId class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.auth.openid.*');

/**
 * QsAuthExternalServiceOpenId is a base class for all OpenID external auth services.
 *
 * @property QsOpenIdClient $openIdClient public alias of {@link _openIdClient}.
 * @property array $requiredAttributes public alias of {@link _requiredAttributes}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
abstract class QsAuthExternalServiceOpenId extends QsAuthExternalService {
	/**
	 * @var QsOpenIdClient|array OpenId client instance or its array configuration.
	 */
	protected $_openIdClient = array();
	/**
	 * @var string the OpenID authorization url.
	 */
	public $authUrl = '';
	/**
	 * @var array the OpenID required attributes list.
	 * For example:
	 * array(
	 *     'personal/name',
	 *     'contact/email',
	 * );
	 */
	protected $_requiredAttributes;

	/**
	 * @param array|QsOpenIdClient $openIdClient OpenId client instance or its array configuration.
	 * @throws CException on invalid parameters.
	 */
	public function setOpenIdClient($openIdClient) {
		if (!is_object($openIdClient) && !is_array($openIdClient)) {
			throw new CException('"' . get_class($this) . '::openIdClient" should be "QsOpenIdClient" instance or its array configuration.');
		}
		$this->_openIdClient = $openIdClient;
	}

	/**
	 * @return QsOpenIdClient OpenId client instance.
	 */
	public function getOpenIdClient() {
		if (!is_object($this->_openIdClient)) {
			$this->_openIdClient = $this->createOpenIdClient($this->_openIdClient);
		}
		return $this->_openIdClient;
	}

	/**
	 * @param array $requiredAttributes OpenId required attributes
	 */
	public function setRequiredAttributes(array $requiredAttributes) {
		$this->_requiredAttributes = $requiredAttributes;
	}

	/**
	 * @return array OpenId required attributes
	 */
	public function getRequiredAttributes() {
		if (!is_array($this->_requiredAttributes)) {
			$this->_requiredAttributes = $this->defaultRequiredAttributes();
		}
		return $this->_requiredAttributes;
	}

	/**
	 * Generates default {@link requiredAttributes} value.
	 * @return array required attributes.
	 */
	protected function defaultRequiredAttributes() {
		return array();
	}

	/**
	 * Creates OpenId client instance from given configuration.
	 * @param array $openIdClientConfig OpenId client instance configuration.
	 * @return QsOpenIdClient OpenId client instance
	 */
	protected function createOpenIdClient(array $openIdClientConfig) {
		if (!array_key_exists('class', $openIdClientConfig)) {
			$openIdClientConfig['class'] = 'QsOpenIdClient';
		}
		return Yii::createComponent($openIdClientConfig);
	}

	/**
	 * Authenticate the user.
	 * @throws CHttpException on invalid request.
	 * @throws CException on error.
	 * @return boolean whether user was successfully authenticated.
	 */
	public function authenticate() {
		$openId = $this->getOpenIdClient()->getOpenId();
		if (!empty($_REQUEST['openid_mode'])) {
			switch ($_REQUEST['openid_mode']) {
				case 'id_res':
					if ($openId->validate()) {
						$attributes = array(
							'id' => $openId->identity
						);
						$rawAttributes = $openId->getAttributes();
						foreach ($this->getRequiredAttributes() as $openIdAttributeName) {
							if (isset($rawAttributes[$openIdAttributeName])) {
								$attributes[$openIdAttributeName] = $rawAttributes[$openIdAttributeName];
							} else {
								throw new CException('Unable to complete the authentication because the required data was not received.');
							}
						}
						$this->setAttributes($attributes);
						$this->isAuthenticated = true;
						return true;
					} else {
						throw new CException('Unable to complete the authentication because the required data was not received.');
					}
					break;
				case 'cancel':
					$this->redirectCancel();
					break;
				default:
					throw new CHttpException(400, Yii::t('yii', 'Your request is invalid.'));
					break;
			}
		} else {
			$openId->identity = $this->authUrl; // Setting identifier
			$openId->required = array(); // Try to get info from openid provider
			foreach ($this->getRequiredAttributes() as $openIdAttributeName) {
				$openId->required[] = $openIdAttributeName;
			}
			/* @var $httpRequest CHttpRequest */
			$httpRequest = Yii::app()->getComponent('request');
			$openId->realm = $httpRequest->hostInfo;
			$openId->returnUrl = $openId->realm . $httpRequest->url; // getting return URL

			$url = $openId->authUrl();
			$httpRequest->redirect($url);
		}

		return false;
	}
}
