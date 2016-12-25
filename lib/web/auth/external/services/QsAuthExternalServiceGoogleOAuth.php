<?php
/**
 * QsAuthExternalServiceGoogleOAuth class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceGoogleOAuth allows authentication via Google OAuth.
 * In order to use Google OAuth you must register your application at {@link https://code.google.com/apis/console#access}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'google' => array(
 *                 'class' => 'QsAuthExternalServiceGoogleOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'google_client_id',
 *                     'clientSecret' => 'google_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see https://code.google.com/apis/console#access
 * @see https://developers.google.com/google-apps/contacts/v3/
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceGoogleOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'google';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Google';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://accounts.google.com/o/oauth2/auth',
			'tokenUrl' => 'https://accounts.google.com/o/oauth2/token',
			'apiBaseUrl' => 'https://www.googleapis.com/oauth2/v1',
			'scope' => implode(' ', array(
				'https://www.googleapis.com/auth/userinfo.profile',
				'https://www.googleapis.com/auth/userinfo.email',
			)),
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('userinfo', 'GET');
		return $attributes;
	}
}
