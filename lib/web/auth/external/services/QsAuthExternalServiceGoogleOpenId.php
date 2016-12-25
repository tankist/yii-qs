<?php
/**
 * QsAuthExternalServiceGoogleOpenId class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceGoogleOpenId allows authentication via Google OpenId.
 * Unlike Google OAuth you do not need to register your application anywhere in order to use Google OpenId.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'google' => array(
 *                 'class' => 'QsAuthExternalServiceGoogleOpenId',
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceGoogleOpenId extends QsAuthExternalServiceOpenId {
	/**
	 * @var string the OpenID authorization url.
	 */
	public $authUrl = 'https://www.google.com/accounts/o8/id';
	/**
	 * @var integer auth popup window width in pixels.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupWidth = 880;
	/**
	 * @var integer auth popup window height in pixels.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupHeight = 520;

	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'google_openid';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Google';
	}

	/**
	 * Generates default {@link requiredAttributes} value.
	 * @return array required attributes.
	 */
	protected function defaultRequiredAttributes() {
		return array(
			'namePerson/first',
			'namePerson/last',
			'contact/email',
			'pref/language',
		);
	}

	/**
	 * Creates default {@link normalizeAttributeMap} value.
	 * @return array normalize attribute map.
	 */
	protected function defaultNormalizeAttributeMap() {
		return array(
			'first_name' => 'namePerson/first',
			'last_name' => 'namePerson/last',
			'email' => 'contact/email',
			'language' => 'pref/language',
		);
	}
}
