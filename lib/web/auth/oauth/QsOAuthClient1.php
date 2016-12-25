<?php
/**
 * QsOAuthClient1 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthClient1 service client for the OAuth 1/1.0a flow.
 *
 * In oder to acquire access token perform following sequence:
 * <code>
 * $oauthClient = new QsOAuthClient1();
 * $requestToken = $oauthClient->fetchRequestToken(); // Get request token
 * $url = $oauthClient->buildAuthUrl($requestToken); // Get authorization URL
 * Yii::app()->getComponent('request')->redirect($url); // Redirect to authorization URL.
 * // After user returns at our site:
 * $accessToken = $oauthClient->fetchAccessToken($requestToken); // Upgrade to access token
 * </code>
 *
 * @see http://oauth.net/
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth
 */
class QsOAuthClient1 extends QsOAuthClient {
	/**
	 * @var string protocol version.
	 */
	public $version = '1.0';
	/**
	 * @var string OAuth consumer key.
	 */
	public $consumerKey = '';
	/**
	 * @var string OAuth consumer secret.
	 */
	public $consumerSecret = '';
	/**
	 * @var string OAuth request token URL.
	 */
	public $requestTokenUrl = '';
	/**
	 * @var string request token HTTP method.
	 */
	public $requestTokenMethod = 'GET';
	/**
	 * @var string OAuth access token URL.
	 */
	public $accessTokenUrl = '';
	/**
	 * @var string access token HTTP method.
	 */
	public $accessTokenMethod = 'GET';

	/**
	 * Fetches the OAuth request token.
	 * @param array $params additional request params.
	 * @return QsOAuthToken request token.
	 */
	public function fetchRequestToken(array $params = array()) {
		$this->removeState('token');
		$defaultParams = array(
			'oauth_consumer_key' => $this->consumerKey,
			'oauth_callback' => $this->getReturnUrl(),
			//'xoauth_displayname' => Yii::app()->name,
		);
		if (!empty($this->scope)) {
			$defaultParams['scope'] = $this->scope;
		}
		$response = $this->sendSignedRequest($this->requestTokenMethod, $this->requestTokenUrl, array_merge($defaultParams, $params));
		$token = $this->createToken(array(
			'params' => $response
		));
		$this->setState('requestToken', $token);
		return $token;
	}

	/**
	 * Composes user authorization URL.
	 * @param QsOAuthToken $requestToken OAuth request token.
	 * @param array $params additional request params.
	 * @return string authorize URL
	 * @throws CException on failure.
	 */
	public function buildAuthUrl(QsOAuthToken $requestToken = null, array $params = array()) {
		if (!is_object($requestToken)) {
			$requestToken = $this->getState('requestToken');
			if (!is_object($requestToken)) {
				throw new CException('Request token is required to build authorize URL!');
			}
		}
		$params['oauth_token'] = $requestToken->getToken();
		return $this->composeUrl($this->authUrl, $params);
	}

	/**
	 * Fetches OAuth access token.
	 * @param QsOAuthToken $requestToken OAuth request token.
	 * @param string $oauthVerifier OAuth verifier.
	 * @param array $params additional request params.
	 * @return QsOAuthToken OAuth access token.
	 * @throws CException on failure.
	 */
	public function fetchAccessToken(QsOAuthToken $requestToken = null, $oauthVerifier = null, array $params = array()) {
		if (!is_object($requestToken)) {
			$requestToken = $this->getState('requestToken');
			if (!is_object($requestToken)) {
				throw new CException('Request token is required to fetch access token!');
			}
		}
		$this->removeState('requestToken');
		$defaultParams = array(
			'oauth_consumer_key' => $this->consumerKey,
			'oauth_token' => $requestToken->getToken()
		);
		if ($oauthVerifier === null) {
			if (isset($_REQUEST['oauth_verifier'])) {
				$oauthVerifier = $_REQUEST['oauth_verifier'];
			}
		}
		if (!empty($oauthVerifier)) {
			$defaultParams['oauth_verifier'] = $oauthVerifier;
		}
		$response = $this->sendSignedRequest($this->accessTokenMethod, $this->accessTokenUrl, array_merge($defaultParams, $params));

		$token = $this->createToken(array(
			'params' => $response
		));
		$this->setAccessToken($token);
		return $token;
	}

	/**
	 * Sends HTTP request, signed by {@link signatureMethod}.
	 * @param string $method request type.
	 * @param string $url request URL.
	 * @param array $params request params.
	 * @return array response.
	 */
	protected function sendSignedRequest($method, $url, array $params = array()) {
		$params = array_merge($params, $this->generateCommonRequestParams());
		$params = $this->signRequest($method, $url, $params);
		return $this->sendRequest($method, $url, $params);
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
				if (!empty($params)){
					$curlOptions[CURLOPT_POSTFIELDS] = $params;
				}
				$authorizationHeader = $this->composeAuthorizationHeader($params);
				if (!empty($authorizationHeader)/* && $this->curlAuthHeader*/) {
					$curlOptions[CURLOPT_HTTPHEADER] = array('Content-Type: application/atom+xml', $authorizationHeader);
				}
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
		$params['oauth_consumer_key'] = $this->consumerKey;
		$params['oauth_token'] = $accessToken->getToken();
		$response = $this->sendSignedRequest($method, $url, $params);
		return $response;
	}

	/**
	 * Gets new auth token to replace expired one.
	 * @param QsOAuthToken $token expired auth token.
	 * @return QsOAuthToken new auth token.
	 */
	public function refreshAccessToken(QsOAuthToken $token) {
		// @todo
		return null;
	}

	/**
	 * Composes default {@link returnUrl} value.
	 * @return string return URL.
	 */
	protected function defaultReturnUrl() {
		$params = $_GET;
		unset($params['oauth_token']);
		return Yii::app()->createAbsoluteUrl(Yii::app()->getController()->route, $params);
	}

	/**
	 * Generates nonce value.
	 * @return string nonce value.
	 */
	protected function generateNonce() {
		return md5(microtime() . mt_rand());
	}

	/**
	 * Generates timestamp.
	 * @return integer timestamp.
	 */
	protected function generateTimestamp() {
		return time();
	}

	/**
	 * Generate common request params like version, timestamp etc.
	 * @return array common request params.
	 */
	protected function generateCommonRequestParams() {
		$params = array(
			'oauth_version' => $this->version,
			'oauth_nonce' => $this->generateNonce(),
			'oauth_timestamp' => $this->generateTimestamp(),
		);
		return $params;
	}

	/**
	 * Sign request with {@link signatureMethod}.
	 * @param string $method request method.
	 * @param string $url request URL.
	 * @param array $params request params.
	 * @return array signed request params.
	 */
	protected function signRequest($method, $url, array $params) {
		$signatureMethod = $this->getSignatureMethod();
		$params['oauth_signature_method'] = $signatureMethod->getName();
		$signatureBaseString = $this->composeSignatureBaseString($method, $url, $params);
		$signatureKey = $this->composeSignatureKey();
		$params['oauth_signature'] = $signatureMethod->generateSignature($signatureBaseString, $signatureKey);
		return $params;
	}

	/**
	 * Creates signature base string, which will be signed by {@link signatureMethod}.
	 * @param string $method request method.
	 * @param string $url request URL.
	 * @param array $params request params.
	 * @return string base signature string.
	 */
	protected function composeSignatureBaseString($method, $url, array $params) {
		unset($params['oauth_signature']);
		$parts = array(
			strtoupper($method),
			$url,
			QsOAuthHelper::buildQueryString($params)
		);
		$parts = array_map(array('QsOAuthHelper', 'urlEncode'), $parts);
		return implode('&', $parts);
	}

	/**
	 * Composes request signature key.
	 * @return string signature key.
	 */
	protected function composeSignatureKey() {
		$signatureKeyParts = array(
			$this->consumerSecret
		);
		$accessToken = $this->getAccessToken();
		if (is_object($accessToken)) {
			$signatureKeyParts[] = $accessToken->getTokenSecret();
		} else {
			$signatureKeyParts[] = '';
		}
		$signatureKeyParts = array_map(array('QsOAuthHelper', 'urlEncode'), $signatureKeyParts);
		return implode('&', $signatureKeyParts);
	}

	/**
	 * Composes authorization header content.
	 * @param array $params request params.
	 * @param string $realm authorization realm.
	 * @return string authorization header content.
	 */
	protected function composeAuthorizationHeader(array $params, $realm='') {
		$header = 'Authorization: OAuth';
		$headerParams = array();
		if (!empty($realm)) {
			$headerParams[] = 'realm="' . QsOAuthHelper::urlEncode($realm) . '"';
		}
		foreach ($params as $key => $value) {
			if (substr($key, 0, 5) != 'oauth') {
				continue;
			}
			$headerParams[] = QsOAuthHelper::urlEncode($key) . '="' . QsOAuthHelper::urlEncode($value) . '"';
		}
		if (!empty($headerParams)) {
			$header .= ' ' . implode(', ', $headerParams);
		}
		return $header;
	}
}
