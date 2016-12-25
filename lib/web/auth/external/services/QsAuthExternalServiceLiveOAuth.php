<?php
/**
 * QsAuthExternalServiceLiveOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceLiveOAuth allows authentication via Microsoft Live OAuth.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'live' => array(
 *                 'class' => 'QsAuthExternalServiceLiveOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'live_client_id',
 *                     'clientSecret' => 'live_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://msdn.microsoft.com/en-us/library/live/hh243647.aspx
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceLiveOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'live';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Live';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://login.live.com/oauth20_authorize.srf',
			'tokenUrl' => 'https://login.live.com/oauth20_token.srf',
			'apiBaseUrl' => 'https://apis.live.net/v5.0',
			'scope' => '',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('me', 'GET');
		return $attributes;
	}
}
