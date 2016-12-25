<?php
/**
 * QsAuthExternalServiceLinkedInOAuth class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceLinkedInOAuth allows authentication via LinkedIn OAuth.
 * In order to use linkedIn OAuth you must register your application at {@link https://www.linkedin.com/secure/developer}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'linkedin' => array(
 *                 'class' => 'QsAuthExternalServiceLinkedInOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'linkedin_client_id',
 *                     'clientSecret' => 'linkedin_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://developer.linkedin.com/documents/authentication
 * @see https://www.linkedin.com/secure/developer
 * @see http://developer.linkedin.com/apis
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceLinkedInOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'linkedin';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'LinkedIn';
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClientLinkedIn';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'https://www.linkedin.com/uas/oauth2/authorization',
			'tokenUrl' => 'https://www.linkedin.com/uas/oauth2/accessToken',
			'apiBaseUrl' => 'https://api.linkedin.com/v1',
			'scope' => implode(' ', array(
				'r_basicprofile',
				'r_emailaddress',
			)),
		);
	}

	/**
	 * Creates default {@link normalizeAttributeMap} value.
	 * @return array normalize attribute map.
	 */
	protected function defaultNormalizeAttributeMap() {
		return array(
			'email' => 'email-address',
			'first_name' => 'first-name',
			'last_name' => 'last-name',
		);
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		$attributeNames = array(
			'id',
			'email-address',
			'first-name',
			'last-name',
			'public-profile-url',
		);
		$attributes = $this->api('people/~:(' . implode(',', $attributeNames) . ')', 'GET');
		return $attributes;
	}
}

/**
 * QsOAuthClientLinkedIn is an OAuth 2 client, which applies specific of LinkedIn OAuth.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsOAuthClientLinkedIn extends QsOAuthClient2 {
	/**
	 * Composes user authorization URL.
	 * @param array $params additional auth GET params.
	 * @return string authorization URL.
	 */
	public function buildAuthUrl(array $params = array()) {
		$authState = $this->generateAuthState();
		$this->setState('authState', $authState);
		$params['state'] = $authState;
		return parent::buildAuthUrl($params);
	}

	/**
	 * Fetches access token from authorization code.
	 * @param string $authCode authorization code, usually comes at $_GET['code'].
	 * @param array $params additional request params.
	 * @throws CHttpException on wrong auth state.
	 * @return QsOAuthToken access token.
	 */
	public function fetchAccessToken($authCode, array $params = array()) {
		$authState = $this->getState('authState');
		if (!isset($_REQUEST['state']) || empty($authState) || strcmp($_REQUEST['state'], $authState) !== 0) {
			throw new CHttpException(400, 'Invalid auth state parameter.');
		} else {
			$this->removeState('authState');
		}
		return parent::fetchAccessToken($authCode, $params);
	}

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
		$params['oauth2_access_token'] = $accessToken->getToken();
		return $this->sendRequest($method, $url, $params);
	}

	/**
	 * Composes default {@link returnUrl} value.
	 * @return string return URL.
	 */
	protected function defaultReturnUrl() {
		$params = $_GET;
		unset($params['code']);
		unset($params['state']);
		return Yii::app()->createAbsoluteUrl(Yii::app()->getController()->route, $params);
	}

	/**
	 * Generates the auth state value.
	 * @return string auth state value.
	 */
	protected function generateAuthState() {
		return sha1(uniqid(get_class($this), true));
	}
}