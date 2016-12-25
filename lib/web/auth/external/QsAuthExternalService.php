<?php
/**
 * QsAuthExternalService class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalService provides basic interface and functionality for the external authentication services.
 *
 * Example:
 * <code>
 * $service = new QsAuthExternalServiceSome();
 * $service->authenticate(); // attempts to perform authentication
 * if ($service->isAuthenticated) { // authentication success
 *     $attributes = $service->getAttributes(); // user account attributes
 *     ...
 * }
 * </code>
 *
 * QsAuthExternalService can create own user identity, which could be used for login:
 * <code>
 * $service = new QsAuthExternalServiceSome();
 * $userIdentity = $service->createUserIdentity();
 * if ($userIdentity->authenticate()) {
 *     Yii::app()->getComponent('user')->login($userIdentity);
 * }
 * </code>
 *
 * @property string $id public alias of {@link _id}.
 * @property string $name public alias of {@link _name}.
 * @property string $title public alias of {@link _title}.
 * @property string $successUrl public alias of {@link _successUrl}.
 * @property string $cancelUrl public alias of {@link _cancelUrl}.
 * @property array $attributes public alias of {@link _attributes}.
 * @property array $normalizeAttributeMap public alias of {@link _normalizeAttributeMap}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external
 */
abstract class QsAuthExternalService extends CComponent {
	/**
	 * @var boolean whether user was successfully authenticated.
	 */
	public $isAuthenticated = false;
	/**
	 * @var string service id.
	 * This value mainly used as HTTP request parameter.
	 */
	protected $_id;
	/**
	 * @var string service unique name.
	 * This value may be used in database records, CSS files and so on.
	 */
	protected $_name;
	/**
	 * @var string service title to display in views.
	 */
	protected $_title;
	/**
	 * @var string the redirect url after successful authorization.
	 */
	protected $_successUrl = '';
	/**
	 * @var string the redirect url after unsuccessful authorization (e.g. user canceled).
	 */
	protected $_cancelUrl = '';
	/**
	 * @var array authorization attributes.
	 */
	protected $_attributes;
	/**
	 * @var array map used to normalize user attributes fetched from external auth service
	 * in format: normalizedAttributeName => actualAttributeName
	 */
	protected $_normalizeAttributeMap;
	/**
	 * @var integer auth popup window width in pixels.
	 * If not set default value will be used.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupWidth;
	/**
	 * @var integer auth popup window height in pixels.
	 * If not set default value will be used.
	 * @see QsAuthExternalServiceChoice
	 */
	public $popupHeight;

	/**
	 * @param string $id service id.
	 * @return QsAuthExternalService self instance.
	 */
	public function setId($id) {
		$this->_id = $id;
		return $this;
	}

	/**
	 * @return string service id
	 */
	public function getId() {
		if (empty($this->_id)) {
			$this->_id = $this->getName();
		}
		return $this->_id;
	}

	/**
	 * @return string service name.
	 */
	public function getName() {
		if ($this->_name === null) {
			$this->_name = $this->defaultName();
		}
		return $this->_name;
	}

	/**
	 * @return string service title.
	 */
	public function getTitle() {
		if ($this->_title === null) {
			$this->_title = $this->defaultTitle();
		}
		return $this->_title;
	}

	/**
	 * @param string $successUrl successful URL.
	 */
	public function setSuccessUrl($successUrl) {
		$this->_successUrl = $successUrl;
	}

	/**
	 * @return string successful URL.
	 */
	public function getSuccessUrl() {
		if (empty($this->_successUrl)) {
			$this->_successUrl = $this->defaultSuccessUrl();
		}
		return $this->_successUrl;
	}

	/**
	 * @param string $cancelUrl cancel URL.
	 */
	public function setCancelUrl($cancelUrl) {
		$this->_cancelUrl = $cancelUrl;
	}

	/**
	 * @return string cancel URL.
	 */
	public function getCancelUrl() {
		if (empty($this->_cancelUrl)) {
			$this->_cancelUrl = $this->defaultCancelUrl();
		}
		return $this->_cancelUrl;
	}

	/**
	 * @param array $normalizeAttributeMap normalize attribute map
	 * @return QsAuthExternalService self instance.
	 */
	public function setNormalizeAttributeMap(array $normalizeAttributeMap) {
		$this->_normalizeAttributeMap = $normalizeAttributeMap;
		return $this;
	}

	/**
	 * @return array normalize attribute map
	 */
	public function getNormalizeAttributeMap() {
		if (!is_array($this->_normalizeAttributeMap)) {
			$this->_normalizeAttributeMap = $this->defaultNormalizeAttributeMap();
		}
		return $this->_normalizeAttributeMap;
	}

	/**
	 * @param array $attributes auth attributes.
	 * @return QsAuthExternalService self instance
	 */
	public function setAttributes(array $attributes) {
		$this->_attributes = $this->normalizeAttributes($attributes);
		return $this;
	}

	/**
	 * @return array auth attributes.
	 */
	public function getAttributes() {
		if ($this->_attributes === null) {
			$this->setAttributes($this->initAttributes());
		}
		return $this->_attributes;
	}

	/**
	 * Creates user identity instance for this service.
	 * @return QsAuthExternalUserIdentity user identity instance.
	 */
	public function createUserIdentity() {
		$identityConfig = array(
			'class' => 'QsAuthExternalUserIdentity',
			'service' => $this
		);
		return Yii::createComponent($identityConfig);
	}

	/**
	 * Generates service name.
	 * @return string service name.
	 */
	protected function defaultName() {
		return get_class($this);
	}

	/**
	 * Generates service title.
	 * @return string service title.
	 */
	protected function defaultTitle() {
		return get_class($this);
	}

	/**
	 * Creates default {@link successUrl} value.
	 * @return string success URL value.
	 */
	protected function defaultSuccessUrl() {
		return Yii::app()->getComponent('user')->getReturnUrl();
	}

	/**
	 * Creates default {@link cancelUrl} value.
	 * @return string cancel URL value.
	 */
	protected function defaultCancelUrl() {
		/* @var $request CHttpRequest */
		$request = Yii::app()->getComponent('request');
		return $request->getBaseUrl(true) . '/' . $request->getPathInfo();
	}

	/**
	 * Creates default {@link normalizeAttributeMap} value.
	 * @return array normalize attribute map.
	 */
	protected function defaultNormalizeAttributeMap() {
		return array();
	}

	/**
	 * Creates initial auth attributes.
	 * @return array auth attributes.
	 */
	protected function initAttributes() {
		return array();
	}

	/**
	 * Normalize given user attributes according to {@link normalizeAttributeMap}.
	 * @param array $attributes raw attributes.
	 * @return array normalized attributes.
	 */
	protected function normalizeAttributes(array $attributes) {
		foreach ($this->getNormalizeAttributeMap() as $normalizedName => $actualName) {
			if (array_key_exists($actualName, $attributes)) {
				$attributes[$normalizedName] = $attributes[$actualName];
			}
		}
		return $attributes;
	}

	/**
	 * Redirect to the given URL or simply close the popup window.
	 * @param mixed $url URL to redirect, could be a string or array config to generate a valid URL.
	 * @param boolean $enforceRedirect indicates if redirect should be performed even in case of popup window.
	 * @param array $options {@link QsAuthExternalPopupWindowRedirect} widget options.
	 * @param boolean $terminate whether to terminate the current application.
	 */
	public function redirect($url, $enforceRedirect = true, array $options = array(), $terminate = true) {
		$widgetClassName = 'QsAuthExternalPopupWindowRedirect';
		if (!class_exists($widgetClassName, false)) {
			require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . $widgetClassName . '.php');
		}
		$widgetOptions = array_merge(
			$options,
			array(
				'url' => $url,
				'enforceRedirect' => $enforceRedirect,
				'terminate' => $terminate,
			)
		);
		$widget = Yii::app()->getWidgetFactory()->createWidget($this, $widgetClassName, $widgetOptions);
		$widget->init();
		$widget->run();
	}

	/**
	 * Redirect to the URL. If URL is null, {@link successUrl} will be used.
	 * @param string $url URL to redirect.
	 */
	public function redirectSuccess($url = null) {
		if ($url === null) {
			$url = $this->getSuccessUrl();
		}
		$this->redirect($url);
	}

	/**
	 * Redirect to the {@link cancelUrl} or simply close the popup window.
	 * @param string $url URL to redirect.
	 */
	public function redirectCancel($url = null) {
		if ($url === null) {
			$url = $this->getCancelUrl();
		}
		$this->redirect($url, false);
	}

	/**
	 * Authenticate the user.
	 * @return boolean whether user was successfully authenticated.
	 */
	abstract public function authenticate();
}
