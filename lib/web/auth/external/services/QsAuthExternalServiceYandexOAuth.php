<?php
/**
 * QsAuthExternalServiceYandexOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceYandexOAuth allows authentication via Yandex OAuth.
 * In order to use Yandex OAuth you must register your application at {@link https://oauth.yandex.ru/client/new}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'yandex' => array(
 *                 'class' => 'QsAuthExternalServiceYandexOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'yandex_client_id',
 *                     'clientSecret' => 'yandex_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see https://oauth.yandex.ru/client/new
 * @see http://api.yandex.ru/login/doc/dg/reference/response.xml
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceYandexOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'yandex';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Yandex';
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClientYandex';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://oauth.yandex.ru/authorize',
			'tokenUrl' => 'https://oauth.yandex.ru/token',
			'apiBaseUrl' => 'https://login.yandex.ru',
			'scope' => '',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('info', 'GET');
		return $attributes;
	}
}

/**
 * QsOAuthClientYandex is an OAuth 2 client, which applies specific of Yandex OAuth.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsOAuthClientYandex extends QsOAuthClient2 {
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
		if (!isset($params['format'])) {
			$params['format'] = 'json';
		}
		$params['oauth_token'] = $accessToken->getToken();
		return $this->sendRequest($method, $url, $params);
	}
}