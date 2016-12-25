<?php
/**
 * QsAuthExternalServiceFacebookOAuth class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceFacebookOAuth allows authentication via Facebook OAuth.
 * In order to use Facebook OAuth you must register your application at {@link https://developers.facebook.com/apps}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'facebook' => array(
 *                 'class' => 'QsAuthExternalServiceFacebookOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'facebook_client_id',
 *                     'clientSecret' => 'facebook_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see https://developers.facebook.com/apps
 * @see http://developers.facebook.com/docs/reference/api
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceFacebookOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'facebook';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Facebook';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://www.facebook.com/dialog/oauth',
			'tokenUrl' => 'https://graph.facebook.com/oauth/access_token',
			'apiBaseUrl' => 'https://graph.facebook.com',
			'scope' => 'email',
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
