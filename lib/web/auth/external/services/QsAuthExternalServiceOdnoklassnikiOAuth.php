<?php
/**
 * QsAuthExternalServiceOdnoklassnikiOAuth class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceOdnoklassnikiOAuth allows authentication via Odnoklassniki.Ru OAuth.
 * In order to use Odnoklassniki.Ru OAuth you must register your application at {@link http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=5668937}.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'odnoklassniki' => array(
 *                 'class' => 'QsAuthExternalServiceOdnoklassnikiOAuth',
 *                 'oAuthClient' => array(
 *                     'clientId' => 'odnoklassniki_client_id',
 *                     'clientSecret' => 'odnoklassniki_client_secret',
 *                 ),
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @see http://dev.odnoklassniki.ru/wiki/pages/viewpage.action?pageId=5668937
 * @see http://dev.odnoklassniki.ru/wiki/display/ok/REST+API+-+users.getCurrentUser
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceOdnoklassnikiOAuth extends QsAuthExternalServiceOAuth2 {
	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'odnoklassniki';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Odnoklassniki';
	}

	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClientOdnoklassniki';
	}

	/**
	 * Returns default OAuth client configuration.
	 * @return array OAuth client configuration
	 */
	protected function defaultOAuthClientConfig() {
		return array(
			'clientId' => 'anonymous',
			'clientSecret' => 'anonymous',
			'authUrl' => 'http://www.odnoklassniki.ru/oauth/authorize',
			'tokenUrl' => 'http://api.odnoklassniki.ru/oauth/token.do',
			'apiBaseUrl' => 'http://api.odnoklassniki.ru/fb.do',
			'scope' => '',
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

/**
 * QsOAuthClientOdnoklassniki is an OAuth 2 client, which applies specific of Odnoklassniki.Ru OAuth.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsOAuthClientOdnoklassniki extends QsOAuthClient2 {
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
		$params['application_key'] = $this->clientId;
		$apiRequestSignature = $this->generateApiRequestSignature($params);
		$params['sig'] = $apiRequestSignature;
		return $this->sendRequest($method, $url, $params);
	}

	/**
	 * Generates API request signature string.
	 * @param array $params API request params.
	 * @return string signature string.
	 */
	protected function generateApiRequestSignature(array $params) {
		$signatureParamNames = array(
			'application_key',
			'method',
		);
		$signatureBaseStringParts = array();
		foreach ($signatureParamNames as $signatureParamName) {
			$signatureBaseStringParts[] = $signatureParamName . '=' . $params[$signatureParamName];
		}
		$signatureBaseStringParts[] = md5($params['access_token'] . $this->clientSecret);
		$signatureBaseString = implode('', $signatureBaseStringParts);
		return md5($signatureBaseString);
	}
}