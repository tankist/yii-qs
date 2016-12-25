<?php
/**
 * QsBehaviorDataCache class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsBehaviorDataCache grands its owner the smooth ability to use internal data caching.
 * This behavior checks if cache application component is available and provides unique cache ids.
 *
 * Example:
 * <code>
 * class MyComponent extends CApplicationComponent {
 *     public $behaviors = array(
 *         'dataCacheBehavior' => array(
 *             'class' => 'qs.caching.QsBehaviorDataCache',
 *             'cacheDuration' => 3600
 *         )
 *     );
 *     ...
 *     public function myMethod() {
 *         $data = $this->getDataFromCache('myCacheId');
 *         if ($data===false) {
 *             ...
 *             $this->setDataToCache('myCacheId',$data);
 *             ...
 *         }
 *         ...
 *     }
 * }
 * </code>
 *
 * @property integer $cacheDuration public alias of {@link _cacheDuration}.
 * @property string $ownerIdPropertyName public alias of {@link _ownerIdPropertyName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.caching
 */
class QsBehaviorDataCache extends CBehavior {
	/**
	 * @var integer duration of cache for models in seconds.
	 * '0' means never expire.
	 * Set this parameter to a negative integer to aviod caching.
	 */
	protected $_cacheDuration = 3600;
	/**
	 * @var string name of the owner property, which contains it unique identifier.
	 * Set up this field, if you want the cache id to vary for different owner instances.
	 */
	protected $_ownerIdPropertyName = '';

	// Set / Get :

	public function setCacheDuration($cacheDuration) {
		if (!is_numeric($cacheDuration)) {
			throw new CException('"' . get_class($this) . '::cacheDuration" should be an integer!');
		}
		$this->_cacheDuration = $cacheDuration;
		return true;
	}

	public function getCacheDuration() {
		return $this->_cacheDuration;
	}

	public function setOwnerIdPropertyName($ownerIdPropertyName) {
		$this->_ownerIdPropertyName = $ownerIdPropertyName;
		return true;
	}

	public function getOwnerIdPropertyName() {
		return $this->_ownerIdPropertyName;
	}

	/**
	 * Saves the give data into the cache.
	 * @param string $cacheId cache id
	 * @param mixed $data data to be cached.
	 * @return boolean success.
	 */
	public function setDataToCache($cacheId, $data) {
		if (Yii::app()->hasComponent('cache') && $this->getCacheDuration() >= 0) {
			$cacheId = $this->normalizeCacheId($cacheId);
			return Yii::app()->cache->set($cacheId, $data, $this->getCacheDuration());
		}
		return false;
	}

	/**
	 * Restores data from cache.
	 * @param string $cacheId cache id.
	 * @return mixed restored data, false if the value is not in the cache or expired.
	 */
	public function getDataFromCache($cacheId) {
		if (Yii::app()->hasComponent('cache') && $this->getCacheDuration() >= 0) {
			$cacheId = $this->normalizeCacheId($cacheId);
			return Yii::app()->cache->get($cacheId);
		}
		return false;
	}

	/**
	 * Normalizes cache id, appending the owner class name
	 * and, as option, owner id.
	 * @param string $cacheId raw cache id.
	 * @return string normalized cache id.
	 */
	protected function normalizeCacheId($cacheId) {
		$normalizedCacheId = get_class($this->getOwner()) . '.';
		if (!empty($this->_ownerIdPropertyName)) {
			$owner = $this->getOwner();
			$ownerIdPropertyName = $this->_ownerIdPropertyName;
			$normalizedCacheId .= $owner->$ownerIdPropertyName . '.';
		}
		$normalizedCacheId .= $cacheId;
		return $normalizedCacheId;
	}

	/**
	 * Deletes a value with the specified key from cache.
	 * @param string $cacheId cache id.
	 * @return boolean if no error happens during deletion
	 */
	public function deleteDataCache($cacheId) {
		if (Yii::app()->hasComponent('cache') && $this->getCacheDuration()>=0) {
			$cacheId = $this->normalizeCacheId($cacheId);
			return Yii::app()->cache->delete($cacheId);
		}
		return true;
	}
}
