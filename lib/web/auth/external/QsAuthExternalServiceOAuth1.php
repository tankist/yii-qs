<?php
/**
 * QsAuthExternalServiceOAuth1 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceOAuth1 is a base class for all OAuth/1.0 external auth services.
 * @see QsOAuthClient1
 *
 * @property QsOAuthClient1 $oauthClient public alias of {@link _oauthClient}.
 * @method QsOAuthClient1 getOauthClient()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
abstract class QsAuthExternalServiceOAuth1 extends QsAuthExternalServiceOAuth {
	/**
	 * Returns default OAuth client class name.
	 * @return string OAuth client class name.
	 */
	protected function defaultOAuthClientClassName() {
		return 'QsOAuthClient1';
	}

	/**
	 * Authenticate the user.
	 * @return boolean whether user was successfully authenticated.
	 */
	public function authenticate() {
		/* @var $httpRequest CHttpRequest */
		$httpRequest = Yii::app()->getComponent('request');
		$oauthClient = $this->getOauthClient();

		// user denied error
		if (isset($_GET['denied'])) {
			$this->redirectCancel();
			return false;
		}

		if (isset($_REQUEST['oauth_token'])) {
			$oauthToken = $_REQUEST['oauth_token'];
		}

		if (!isset($oauthToken)) {
			// Get request token.
			$requestToken = $oauthClient->fetchRequestToken();
			// Get authorization URL.
			$url = $oauthClient->buildAuthUrl($requestToken);
			// Redirect to authorization URL.
			$httpRequest->redirect($url);
		} else {
			// Upgrade to access token.
			$accessToken = $oauthClient->fetchAccessToken();
			$this->isAuthenticated = true;
		}

		return $this->isAuthenticated;
	}
}
