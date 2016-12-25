<?php
/**
 * QsOpenIdClient class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOpenIdClient represents OpenId client.
 * This class is a wrapper around {@link LightOpenID} instance.
 * @see LightOpenID
 *
 * @property LightOpenID $openId public alias of {@link _openId}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.openid
 */
class QsOpenIdClient extends CComponent {
	/**
	 * @var LightOpenID|array OpenId instance or its array configuration.
	 */
	protected $_openId = array();
	/**
	 * @var array list of OpenId instance available properties.
	 * This field is for internal usage only.
	 */
	protected $_openIdAvailablePropertyNames = array();

	/**
	 * @param LightOpenID|array $openId open id instance or its array configuration.
	 * @return QsOpenIdClient self instance.
	 * @throws CException on wrong input.
	 */
	public function setOpenId($openId) {
		if (!is_object($openId) && !is_array($openId)) {
			throw new CException('"'.get_class($this).'::openId" should be an object or its array configuration!');
		}
		$this->_openId = $openId;
		return $this;
	}

	/**
	 * @return LightOpenID OpenId instance.
	 */
	public function getOpenId() {
		if (!is_object($this->_openId)) {
			$this->_openId = $this->createOpenId($this->_openId);
		}
		return $this->_openId;
	}

	/**
	 * Loads the OpenId class definition.
	 * @return string OpenId class name.
	 */
	protected function loadOpenIdClass() {
		$className = 'LightOpenID';
		if (!class_exists($className, false)) {
			$classFileName = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendors'  . DIRECTORY_SEPARATOR . $className . '.php';
			require_once($classFileName);
		}
		return $className;
	}

	/**
	 * Returns the list of OpenId instance properties available to be set or get.
	 * @param string $type property access type, could be "set" or "get".
	 * @return array list of available properies.
	 */
	protected function getOpenIdAvailablePropertyNames($type) {
		if (empty($this->_openIdAvailablePropertyNames)) {
			$this->_openIdAvailablePropertyNames = $this->collectOpenIdAvailablePropertyNames();
		}
		return $this->_openIdAvailablePropertyNames[$type];
	}

	/**
	 * Composes list of OpenId instance properties, which are available for setup.
	 * @return array properties list in format: array('set' => array(...), 'get' => array(...)).
	 */
	protected function collectOpenIdAvailablePropertyNames() {
		$setGetPropertyNames = array();
		$className = $this->loadOpenIdClass();
		$classReflection = new ReflectionClass($className);
		$publicProperties = $classReflection->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach ($publicProperties as $publicProperty) {
			$setGetPropertyNames[] = $publicProperty->getName();
		}
		$setGetPropertyNames = array_merge(
			$setGetPropertyNames,
			array(
				'identity',
				'trustRoot',
				'realm',
			)
		);
		$setPropertyNames = $setGetPropertyNames;
		$getPropertyNames = $setGetPropertyNames;
		$getPropertyNames[] = 'mode';
		$result = array(
			'set' => $setPropertyNames,
			'get' => $getPropertyNames,
		);
		return $result;
	}

	/**
	 * Creates OpenId instance.
	 * @param array $openIdConfig open id configuration.
	 * @return LightOpenID open id instance.
	 */
	protected function createOpenId(array $openIdConfig = array()) {
		$openIdConfig['class'] = $this->loadOpenIdClass();
		return Yii::createComponent($openIdConfig);
	}

	/**
	 * Sets value of a component property.
	 * This method allows to set {@link openId} instance properties directly.
	 * @param string $name the property name or the event name
	 * @param mixed $value the property value or callback
	 * @return mixed
	 * @see CComponent::__get
	 */
	public function __set($name, $value) {
		if (in_array($name, $this->getOpenIdAvailablePropertyNames('get'))) {
			$this->getOpenId()->$name = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Returns a property value, an event handler list or a behavior based on its name.
	 * This method allows to get {@link openId} instance properties directly.
	 * @param string $name the property name or event name
	 * @return mixed the property value
	 * @see CComponent::__set
	 */
	public function __get($name) {
		if (in_array($name, $this->getOpenIdAvailablePropertyNames('set'))) {
			return $this->getOpenId()->$name;
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * Calls the named method which is not a class method.
	 * This method allows to invoke {@link openId} methods directly.
	 * @param string $name the method name
	 * @param array $parameters method parameters
	 * @return mixed the method return value
	 */
	public function __call($name, $parameters) {
		$openId = $this->getOpenId();
		if (method_exists($openId, $name)) {
			return call_user_func_array(array($openId, $name), $parameters);
		}
		return parent::__call($name, $parameters);
	}
}
