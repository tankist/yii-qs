<?php
/**
 * QsAuthExternalServiceYandexOpenId class file.
 * 
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsAuthExternalServiceYandexOpenId allows authentication via Yandex OpenId.
 * Unlike Yandex OAuth you do not need to register your application anywhere in order to use Yandex OpenId.
 *
 * Example application configuration:
 * <code>
 * 'components' => array(
 *     'externalAuth' => array(
 *         'class' => 'qs.web.auth.external.QsAuthExternalServiceCollection',
 *         'services' => array(
 *             'yandex' => array(
 *                 'class' => 'QsAuthExternalServiceYandexOpenId',
 *             ),
 *         ),
 *     ),
 * ),
 * </code>
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.services
 */
class QsAuthExternalServiceYandexOpenId extends QsAuthExternalServiceOpenId {
	/**
	 * @var string the OpenID authorization url.
	 */
	public $authUrl = 'http://openid.yandex.ru';
	/**
	 * @var integer auth popup window width in pixels.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupWidth = 900;
	/**
	 * @var integer auth popup window height in pixels.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupHeight = 550;

	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return 'yandex_openid';
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return 'Yandex';
	}

	/**
	 * Generates default {@link requiredAttributes} value.
	 * @return array required attributes.
	 */
	protected function defaultRequiredAttributes() {
		return array(
			'namePerson',
			'contact/email',
		);
	}

	/**
	 * Creates default {@link normalizeAttributeMap} value.
	 * @return array normalize attribute map.
	 */
	protected function defaultNormalizeAttributeMap() {
		return array(
			'name' => 'namePerson',
			'email' => 'contact/email',
		);
	}
}
