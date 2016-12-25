<?php
/**
 * QsAuthExternalServiceTwitterOAuth class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceTwitterOAuth allows authentication via Twitter OAuth.
 * In order to use Twitter OAuth you must register your application at {@link https://dev.twitter.com/apps/new}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'twitter' => array(
 *                 'class' => 'QsAuthExternalServiceTwitterOAuth',
 *                 'oAuthClient' => array(
 *                     'consumerKey' => 'twitter_consumer_key',
 *                     'consumerSecret' => 'twitter_consumer_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see https://dev.twitter.com/apps/new
 * @see https://dev.twitter.com/docs/api
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceTwitterOAuth extends QsAuthExternalServiceOAuth1 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'twitter';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Twitter';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'consumerKey' => 'anonymous',
			'consumerSecret' => 'anonymous',
			'requestTokenUrl' => 'https://api.twitter.com/oauth/request_token',
			'requestTokenMethod' => 'POST',
			'accessTokenUrl' => 'https://api.twitter.com/oauth/access_token',
			'accessTokenMethod' => 'POST',
			'authUrl' => 'https://api.twitter.com/oauth/authorize',
			'scope' => '',
			'apiBaseUrl' => 'https://api.twitter.com/1.1',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('account/verify_credentials.json', 'GET');
		return $attributes;
	}
}
