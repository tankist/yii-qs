<?php
/**
 * QsActiveRecordBehaviorNameValue class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows usage of table as a storage of "name=value" pairs.
 * This behavior is suitable for the settings storage.
 * In order to achieve better performance behavior uses cache.
 * Cache supposed to be permanent and will be automatically cleared on model save.
 *
 * @property string $namePropertyName public alias of {@link _namePropertyName}.
 * @property string $valuePropertyName public alias of {@link _valuePropertyName}.
 * @property string $autoNamePrefix public alias of {@link _autoNamePrefix}.
 * @property integer $valuesCacheDuration public alias of {@link _valuesCacheDuration}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorNameValue extends CBehavior {
	/**
	 * @var string name of model's attribute, which will be used to store setting name
	 */
	protected $_namePropertyName = 'name';
	/**
	 * @var string name of model's attribute, which will be used to store setting value
	 */
	protected $_valuePropertyName = 'value';
	/**
	 * @var string prefix, which will be append for record name on getting values.
	 */
	protected $_autoNamePrefix = '';
	/**
	 * @var integer duration of cache for values, default value is 0, meaning cache is permanent.
	 */
	protected $_valuesCacheDuration = 0;

	// Set / Get:

	public function setNamePropertyName($namePropertyName) {
		if (!is_string($namePropertyName)) {
			return false;
		}
		$this->_namePropertyName = $namePropertyName;
		return true;
	}

	public function getNamePropertyName() {
		return $this->_namePropertyName;
	}

	public function setValuePropertyName($valuePropertyName) {
		if (!is_string($valuePropertyName)) {
			return false;
		}
		$this->_valuePropertyName = $valuePropertyName;
		return true;
	}

	public function getValuePropertyName() {
		return $this->_valuePropertyName;
	}

	public function setAutoNamePrefix($autoNamePrefix) {
		if (!is_string($autoNamePrefix)) {
			return false;
		}
		$this->_autoNamePrefix = $autoNamePrefix;
		return true;
	}

	public function getAutoNamePrefix() {
		return $this->_autoNamePrefix;
	}

	public function setValuesCacheDuration($valuesCacheDuration) {
		if (!is_numeric($valuesCacheDuration)) {
			return false;
		}
		$this->_valuesCacheDuration = $valuesCacheDuration;
		return true;
	}

	public function getValuesCacheDuration() {
		return $this->_valuesCacheDuration;
	}

	/**
	 * Appends name with the {@link autoNamePrefix}.
	 * @param string $name raw name.
	 * @return string name with prefix.
	 */
	protected function appendNamePrefix($name) {
		$outputName = $this->_autoNamePrefix . $name;
		return $outputName;
	}

	/**
	 * Trims {@link autoNamePrefix} from name.
	 * @param string $name raw name.
	 * @return string name without prefix.
	 */
	protected function trimNamePrefix($name) {
		$prefix = $this->_autoNamePrefix;
		if (strpos($name, $prefix)===0) {
			$inputName = substr($name, strlen($prefix));
		} else {
			$inputName = $name;
		}
		return $inputName;
	}

	/**
	 * Returns values saved in cache.
	 * @return mixed cached value.
	 */
	protected function getValuesFromCache() {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getValuesCacheId();
			return Yii::app()->cache->get($cacheId);
		}
		return false;
	}

	/**
	 * Set values into the cache.
	 * @param mixed $values value to be cached.
	 * @return boolean success
	 */
	protected function setValuesToCache($values) {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getValuesCacheId();
			return Yii::app()->cache->set($cacheId, $values, $this->getValuesCacheDuration());
		}
		return false;
	}

	/**
	 * Returns id of cache, which storing values.
	 * @return string cache id
	 */
	public function getValuesCacheId() {
		$owner = $this->getOwner();
		$cacheId = get_class($owner) . '_' . get_class($this);
		return $cacheId;
	}

	/**
	 * Clears values cache.
	 * @return boolean success
	 */
	public function clearValuesCache() {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getValuesCacheId();
			return Yii::app()->cache->delete($cacheId);
		}
		return true;
	}

	/**
	 * Returns set of values in array format.
	 * Array key is the name of parameter, array value - its value.
	 * This methods automatically uses caching.
	 * @return array set of values
	 */
	public function getValues() {
		$cachedValues = $this->getValuesFromCache();
		if ($cachedValues !== false) {
			return $cachedValues;
		}

		$owner = $this->getOwner();

		$records = $owner->findAll();
		$result = array();
		if (empty($records)) {
			return $result;
		}

		$nameProperty = $this->getNamePropertyName();
		$valueProperty = $this->getValuePropertyName();

		foreach ($records as $record) {
			$recordName = $this->appendNamePrefix($record->$nameProperty);
			$result[$recordName] = $record->$valueProperty;
		}

		$this->setValuesToCache($result);
		return $result;
	}

	/**
	 * Updates values by its name.
	 * @param array $values set of named values.
	 * @return boolean success.
	 */
	public function updateValues(array $values) {
		$owner = $this->getOwner();

		$nameProperty = $this->getNamePropertyName();
		$valueProperty = $this->getValuePropertyName();

		foreach ($values as $name=>$value) {
			$recordName = $this->trimNamePrefix($name);
			$attributes = array(
				$nameProperty => $recordName
			);
			$settingModel = $owner->findByAttributes($attributes);
			if (!empty($settingModel)) {
				$settingModel->$valueProperty = $value;
				$settingModel->save();
			}
		}
		return true;
	}

	// Events:

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onAfterSave' => 'afterSave',
			'onAfterDelete' => 'afterDelete',
		);
	}

	/**
	 * This event raises after owner saved.
	 * It clears cached values.
	 * @param CEvent $event event instance.
	 */
	public function afterSave($event) {
		$this->clearValuesCache();
	}

	/**
	 * This event raises after owner record deleted.
	 * It clears cached values.
	 * @param CEvent $event event instance.
	 */
	public function afterDelete($event) {
		$this->clearValuesCache();
	}
}