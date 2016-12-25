<?php
/**
 * QsAuthExternalServiceVKontakteOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceVKontakteOAuth allows authentication via VKontakte OAuth.
 * In order to use VKontakte OAuth you must register your application at {@link http://vk.com/apps.php?act=add&site=1}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'vkontakte' => array(
 *                 'class' => 'QsAuthExternalServiceVKontakteOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'vkontakte_client_id',
 *                     'clientSecret' => 'vkontakte_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://vk.com/apps.php?act=add&site=1
 * @see http://vk.com/developers.php?oid=-1&p=users.get
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceVKontakteOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'vkontakte';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'VKontakte';
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClientVKontakte';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'http://api.vk.com/oauth/authorize',
			'tokenUrl' => 'https://api.vk.com/oauth/access_token',
			'apiBaseUrl' => 'https://api.vk.com/method',
			'scope' => '',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('users.get.json', 'GET', array(
			'fields' => implode(',', array(
				'uid',
				'first_name',
				'last_name',
				'nickname',
				'screen_name',
				'sex',
				'bdate',
				'city',
				'country',
				'timezone',
				'photo'
			)),
		));
		return $attributes;
	}
}

/**
 * QsOAuthClientVKontakte is an OAuth 2 client, which applies specific of VKontakte OAuth.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsOAuthClientVKontakte extends QsOAuthClient2 {
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
		$params['uids'] = $accessToken->getParam('user_id');
		$params['access_token'] = $accessToken->getToken();
		return $this->sendRequest($method, $url, $params);
	}
}