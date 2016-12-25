<?php
/**
 * QsApplicationBehaviorInitFromParam class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CApplication}, which allows to set any {@link CApplication} property
 * from to the {@link CApplication::params}. 
 * The correspondence between application properties and params determined by {@link propertyParamNames}.
 * Properties, which are bound with the empty params, will be ignored.
 * This behavior make sense, if it is used together with {@link QsApplicationBehaviorParamDb}.
 *
 * @see QsApplicationBehaviorParamDb
 *
 * @property array $propertyParamNames public alias of {@link _propertyParamNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.application
 */
class QsApplicationBehaviorInitFromParam extends CBehavior {
	/**
	 * @var array map, which determines correspondence between application properties and
	 * array keys from {@link CApplication::params}.
	 * For example:
	 * <code>
	 * array(
	 *     'name' => 'application_name',
	 *     'charset' => 'application_charset',
	 * );
	 * </code>
	 */
	protected $_propertyParamNames = array();

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onBeginRequest' => 'beginRequest'
		);
	}

	// Set / Get :

	public function setPropertyParamNames(array $propertyParamNames) {
		$this->_propertyParamNames = $propertyParamNames;
		return true;
	}

	public function getPropertyParamNames() {
		return $this->_propertyParamNames;
	}

	/**
	 * This event raises before {@link CApplication}.
	 * It update application properties with values found in {@link CApplication::params}
	 * @param CEvent $event event object.
	 */
	public function beginRequest(CEvent $event) {
		$this->updateApplicationProperties();
	}

	/**
	 * Updates application properties with values found in {@link CApplication::params},
	 * according to the map given in {@link propertyParamNames}.
	 * Only not empty params will be applied.
	 * @return boolean success.
	 */
	public function updateApplicationProperties() {
		foreach ($this->_propertyParamNames as $propertyName => $paramName) {
			$paramValue = Yii::app()->params[$paramName];
			if (!empty($paramValue)) {
				Yii::app()->$propertyName = $paramValue;
			}
		}
		return true;
	}
}