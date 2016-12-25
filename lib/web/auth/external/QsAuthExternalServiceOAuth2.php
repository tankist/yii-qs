<?php
/**
 * QsAuthExternalServiceOAuth2 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceOAuth2 is a base class for all OAuth/2.0 external auth services.
 * @see QsOAuthClient2
 *
 * @property QsOAuthClient2 $oauthClient public alias of {@link _oauthClient}.
 * @method QsOAuthClient2 getOauthClient()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
abstract class QsAuthExternalServiceOAuth2 extends QsAuthExternalServiceOAuth {
	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClient2';
	}

	/**
	 * Authenticate the user.
	 * @return boolean whether user was successfully authenticated.
	 * @throws CException on error.
	 */
	public function authenticate() {
		/* @var $httpRequest CHttpRequest */
		$httpRequest = Yii::app()->getComponent('request');

		if (isset($_GET['error'])) {
			if ($_GET['error'] == 'access_denied') {
				// user denied error
				$this->redirectCancel();
				return false;
			} else {
				// request error
				if (isset($_GET['error_description'])) {
					$errorMessage = $_GET['error_description'];
				} elseif (isset($_GET['error_message'])) {
					$errorMessage = $_GET['error_message'];
				} else {
					$errorMessage = http_build_query($_GET);
				}
				throw new CException('Auth error: '.$errorMessage);
			}
		}

		// Get the access_token and save them to the session.
		if (isset($_GET['code'])) {
			$code = $_GET['code'];
			$token = $this->fetchAccessToken($code);
			if (!empty($token)) {
				$this->isAuthenticated = true;
			}
		} else {
			$url = $this->buildAuthUrl();
			$httpRequest->redirect($url);
		}

		return $this->isAuthenticated;
	}

	/**
	 * Composes user authorization URL.
	 * @param array $params additional request parameters.
	 * @return string auth URL.
	 */
	protected function buildAuthUrl(array $params = array()) {
		return $this->getOauthClient()->buildAuthUrl($params);
	}

	/**
	 * Fetches access token from authorization code.
	 * @param string $code authorization code.
	 * @param array $params additional request parameters.
	 * @return QsOAuthToken access token.
	 */
	protected function fetchAccessToken($code, array $params = array()) {
		return $this->getOauthClient()->fetchAccessToken($code, $params);
	}
}
