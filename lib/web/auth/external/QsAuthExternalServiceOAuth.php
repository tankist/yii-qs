<?php
/**
 * QsAuthExternalServiceOAuth class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.auth.oauth.*');
Yii::import('qs.web.auth.oauth.signature.*');

/**
 * QsAuthExternalServiceOAuth base class for OAuth services.
 * @see QsOAuthClient
 *
 * @property QsOAuthClient $oauthClient public alias of {@link _oauthClient}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
abstract class QsAuthExternalServiceOAuth extends QsAuthExternalService {
	/**
	 * @var QsOAuthClient|array OAuth client instance or its array configuration.
	 */
	protected $_oauthClient = array();

	/**
	 * @param QsOAuthClient|array $oauthClient OAuth client instance ot its array configuration.
	 * @return QsAuthExternalServiceOAuth2 self instance.
	 */
	public function setOauthClient($oauthClient) {
		$this->_oauthClient = $oauthClient;
		return $this;
	}

	/**
	 * @return QsOAuthClient OAuth client instance.
	 */
	public function getOauthClient() {
		if (!is_object($this->_oauthClient)) {
			$this->_oauthClient = $this->createOAuthClient($this->_oauthClient);
		}
		return $this->_oauthClient;
	}

	/**
	 * Creates OAuth client instance from given configuration.
	 * @param array $oauthClientConfig OAuth client configuration.
	 * @return QsOAuthClient OAuth client instance.
	 */
	protected function createOAuthClient(array $oauthClientConfig) {
		if (!array_key_exists('class', $oauthClientConfig)) {
			$oauthClientConfig['class'] = $this->defaultOAuthClientClassName();
		}
		$oauthClientConfig = array_merge($this->defaultOAuthClientConfig(), $oauthClientConfig);
		return Yii::createComponent($oauthClientConfig);
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array();
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClient';
	}

	/**
	 * Performs request to the OAuth API.
	 * @param string $apiSubUrl API sub URL, which will be append to {@link QsOAuthClient::apiBaseUrl}, or absolute API URL.
	 * @param string $method request method.
	 * @param array $params request parameters.
	 * @return array API response
	 */
	public function api($apiSubUrl, $method = 'GET', array $params = array()) {
		return $this->getOauthClient()->api($apiSubUrl, $method, $params);
	}
}
