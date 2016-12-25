<?php
/**
 * QsAuthExternalServiceMailRuOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceMailRuOAuth allows authentication via Mail.Ru OAuth.
 * In order to use Mail.Ru OAuth you must register your application at {@link http://api.mail.ru/sites/my/add}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'mailru' => array(
 *                 'class' => 'QsAuthExternalServiceMailRuOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'mailru_client_id',
 *                     'clientSecret' => 'mailru_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://api.mail.ru/sites/my/add
 * @see http://api.mail.ru/docs/guides/restapi
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceMailRuOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'mailru';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Mail.Ru';
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClientMailRu';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://connect.mail.ru/oauth/authorize',
			'tokenUrl' => 'https://connect.mail.ru/oauth/token',
			'apiBaseUrl' => 'http://www.appsmail.ru/platform/api',
			'scope' => '',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('', 'GET', array(
			'method' => 'users.getInfo',
		));
		return $attributes;
	}
}

/**
 * QsOAuthClientMailRu is an OAuth 2 client, which applies specific of Mail.Ru OAuth.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsOAuthClientMailRu extends QsOAuthClient2 {
	/**
	 * Performs request to the OAuth API.
	 * @param QsOAuthToken $accessToken actual access token.
	 * @param string $url absolute API URL.
	 * @param string $method request method.
	 * @param array $params request parameters.
	 * @return array API response.
	 * @throws CException on failure.
	 */
	protected function apiInternal($accessToken, $url, $method, array $params) {
		if (!array_key_exists('secure', $params)) {
			$params['secure'] = '1';
		}
		$params['app_id'] = $this->clientId;
		$params['session_key'] = $accessToken->getToken();
		$apiRequestSignature = $this->generateApiRequestSignature($params);
		$params['sig'] = $apiRequestSignature;
		return $this->sendRequest($method, $url, $params);
	}

	/**
	 * Generates API request signature string.
	 * @param array $params API request params.
	 * @return string signature string.
	 */
	protected function generateApiRequestSignature(array $params) {
		$signatureParamNames = array(
			'app_id',
			'method',
			'secure',
			'session_key',
		);
		$signatureBaseStringParts = array();
		foreach ($signatureParamNames as $signatureParamName) {
			$signatureBaseStringParts[] = $signatureParamName . '=' . $params[$signatureParamName];
		}
		$signatureBaseStringParts[] = $this->clientSecret;
		$signatureBaseString = implode('', $signatureBaseStringParts);
		return md5($signatureBaseString);
	}
}