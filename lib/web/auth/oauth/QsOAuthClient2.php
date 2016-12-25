<?php
/**
 * QsOAuthClient2 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthClient2 service client for the OAuth 2 flow.
 *
 * In oder to acquire access token perform following sequence:
 * <code>
 * $oauthClient = new QsOAuthClient2();
 * $url = $oauthClient->buildAuthUrl(); // Build authorization URL
 * Yii::app()->getComponent('request')->redirect($url); // Redirect to authorization URL.
 * // After user returns at our site:
 * $code = $_GET['code'];
 * $accessToken = $oauthClient->fetchAccessToken($code); // Get access token
 * </code>
 *
 * @see http://oauth.net/2/
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth
 */
class QsOAuthClient2 extends QsOAuthClient {
	/**
	 * @var string protocol version.
	 */
	public $version = '2.0';
	/**
	 * @var string OAuth client ID.
	 */
	public $clientId = '';
	/**
	 * @var string OAuth client secret.
	 */
	public $clientSecret = '';
	/**
	 * @var string token request URL endpoint.
	 */
	public $tokenUrl = '';

	/**
	 * Composes user authorization URL.
	 * @param array $params additional auth GET params.
	 * @return string authorization URL.
	 */
	public function buildAuthUrl(array $params = array()) {
		$defaultParams = array(
			'client_id' => $this->clientId,
			'response_type' => 'code',
			'redirect_uri' => $this->getReturnUrl(),
			'xoauth_displayname' => Yii::app()->name,
		);
		if (!empty($this->scope)) {
			$defaultParams['scope'] = $this->scope;
		}
		return $this->composeUrl($this->authUrl, array_merge($defaultParams, $params));
	}

	/**
	 * Fetches access token from authorization code.
	 * @param string $authCode authorization code, usually comes at $_GET['code'].
	 * @param array $params additional request params.
	 * @return QsOAuthToken access token.
	 */
	public function fetchAccessToken($authCode, array $params = array()) {
		$defaultParams = array(
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'code' => $authCode,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->getReturnUrl(),
		);
		$response = $this->sendRequest('POST', $this->tokenUrl, array_merge($defaultParams, $params));
		$token = $this->createToken(array('params' => $response));
		$this->setAccessToken($token);
		return $token;
	}

	/**
	 * Composes HTTP request CUrl options, which will be merged with the default ones.
	 * @param string $method request type.
	 * @param string $url request URL.
	 * @param array $params request params.
	 * @return array CUrl options.
	 * @throws CException on failure.
	 */
	protected function composeRequestCurlOptions($method, $url, array $params) {
		$curlOptions = array();
		switch ($method) {
			case 'GET': {
				$curlOptions[CURLOPT_URL] = $this->composeUrl($url, $params);
				break;
			}
			case 'POST': {
				$curlOptions[CURLOPT_POST] = true;
				$curlOptions[CURLOPT_HTTPHEADER] = array('Content-type: application/x-www-form-urlencoded');
				$curlOptions[CURLOPT_POSTFIELDS] = QsOAuthHelper::buildQueryString($params);
				break;
			}
			case 'HEAD':
			case 'PUT':
			case 'DELETE': {
				$curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
				if (!empty($params)) {
					$curlOptions[CURLOPT_URL] = $this->composeUrl($url, $params);
				}
				break;
			}
			default: {
				throw new CException("Unknown request method '{$method}'.");
			}
		}
		return $curlOptions;
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
		$params['access_token'] = $accessToken->getToken();
		return $this->sendRequest($method, $url, $params);
	}

	/**
	 * Gets new auth token to replace expired one.
	 * @param QsOAuthToken $token expired auth token.
	 * @return QsOAuthToken new auth token.
	 */
	public function refreshAccessToken(QsOAuthToken $token) {
		$params = array(
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'grant_type' => 'refresh_token'
		);
		$params = array_merge($token->getParams(), $params);
		$response = $this->sendRequest('POST', $this->tokenUrl, $params);
		return $response;
	}

	/**
	 * Composes default {@link returnUrl} value.
	 * @return string return URL.
	 */
	protected function defaultReturnUrl() {
		$params = $_GET;
		unset($params['code']);
		return Yii::app()->createAbsoluteUrl(Yii::app()->getController()->route, $params);
	}

	/**
	 * Creates token from its configuration.
	 * @param array $tokenConfig token configuration.
	 * @return QsOAuthToken token instance.
	 */
	protected function createToken(array $tokenConfig = array()) {
		$tokenConfig['tokenParamKey'] = 'access_token';
		return parent::createToken($tokenConfig);
	}
}
