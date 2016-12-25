<?php
/**
 * QsAuthExternalServiceGitHubOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceGitHubOAuth allows authentication via GitHub OAuth.
 * In order to use GitHub OAuth you must register your application at {@link https://github.com/settings/applications/new}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'github' => array(
 *                 'class' => 'QsAuthExternalServiceGitHubOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'github_client_id',
 *                     'clientSecret' => 'github_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://developer.github.com/v3/oauth/
 * @see https://github.com/settings/applications/new
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceGitHubOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'github';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'GitHub';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://github.com/login/oauth/authorize',
			'tokenUrl' => 'https://github.com/login/oauth/access_token',
			'apiBaseUrl' => 'https://api.github.com',
			'scope' => implode(' ', array(
				'user',
				'user:email',
			)),
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributes = $this->api('user', 'GET');
		return $attributes;
	}
}
