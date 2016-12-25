<?php
/**
 * QsActiveRecordBehaviorClearCache class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which clears cached values, 
 * specified using {@link dependingCacheIds} on owner model change.
 * Each time when model is saved or deleted depending cache will be cleared.
 * This behavior may be used to clear permanent time cache using event based approach.
 * You may specify dynamic cache ids using {@link dependingCacheIdCallback}
 *
 * @property array $dependingCacheIds public alias of {@link _dependingCacheIds}.
 * @property callback $dependingCacheIdCallback public alias of {@link _dependingCacheIdCallback}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorClearCache extends CBehavior {
	/**
	 * @var array set of cache ids, which should be cleared on model change.
	 */
	protected $_dependingCacheIds = array();
	/**
	 * @var callback which will be called in order to determine the
	 * depending cache id list.
	 * The given callback should return array of cache ids.
	 * This parameter can be used, when cache ids can be found only with
	 * complex calculations.
	 */
	protected $_dependingCacheIdCallback = null;

	// Set / Get:

	public function setDependingCacheIds(array $dependingCacheIds) {
		$this->_dependingCacheIds = $dependingCacheIds;
		return true;
	}

	public function getDependingCacheIds() {
		return $this->_dependingCacheIds;
	}

	public function mergeDependingCacheIds(array $additionalDependingCacheIds) {
		$this->_dependingCacheIds = array_merge($this->_dependingCacheIds, $additionalDependingCacheIds);
		return true;
	}

	public function setDependingCacheIdCallback($dependingCacheIdCallback) {
		if (!is_callable($dependingCacheIdCallback, true)) {
			throw new CException('"' . get_class($this) . '::dependingCacheIdCallback" should be a valid callback!');
		}
		$this->_dependingCacheIdCallback = $dependingCacheIdCallback;
		return true;
	}

	public function getDependingCacheIdCallback() {
		return $this->_dependingCacheIdCallback;
	}

	/**
	 * Clears all depending cache, specified by {@link dependingCacheIds}
	 * @return boolean success.
	 */
	public function clearDependingCache() {
		if (Yii::app()->hasComponent('cache')) {
			$dependingCacheIds = $this->getFinalDependingCacheIds();
			foreach ($dependingCacheIds as $cacheId) {
				Yii::app()->cache->delete($cacheId);
			}
		}
		return true;
	}

	/**
	 * Finds all depending cache ids merging {@link dependingCacheIds} and
	 * {@link dependingCacheIdCallback}.
	 * @throws CException on failure.
	 * @return array list of depending cache ids.
	 */
	protected function getFinalDependingCacheIds() {
		$dependingCacheIds = $this->getDependingCacheIds();
		if (!empty($this->_dependingCacheIdCallback)) {
			$additionalCacheIds = call_user_func($this->_dependingCacheIdCallback);
			if (!is_array($additionalCacheIds)) {
				throw new CException('Callback "' . get_class($this) . '::dependingCacheIdCallback" should return an array!');
			}
			$dependingCacheIds = array_merge($dependingCacheIds, $additionalCacheIds);
		}
		return $dependingCacheIds;
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
		$this->clearDependingCache();
	}

	/**
	 * This event raises after owner record deleted.
	 * It clears cached values.
	 * @param CEvent $event event instance.
	 */
	public function afterDelete($event) {
		$this->clearDependingCache();
	}
}