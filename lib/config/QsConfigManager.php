<?php
/**
 * QsConfigManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsConfigManager allows management of the dynamic application configuration parameters.
 * Configuration parameters are set up via {@link items}.
 * Parameters can be saved inside the persistent storage determined by {@link storage}.
 *
 * Application configuration example:
 * <code>
 * array(
 *     'components' => array(
 *         'configManager' => array(
 *             'class' => 'qs.config.QsConfigManager',
 *             'items' => array(
 *                 'appName' => array(
 *                     'path' => 'name',
 *                     'label' => 'Application Name',
 *                     'rules' => array(
 *                         array('required')
 *                     ),
 *                 ),
 *                 'validationKey' => array(
 *                     'path' => 'components.securityManager.validationKey',
 *                     'label' => 'CSRF Validation Key',
 *                     'rules' => array(
 *                         array('required')
 *                     ),
 *                 ),
 *             ),
 *         ),
 *         ...
 *     ),
 * );
 * </code>
 *
 * Each configuration item is a model and so can be used to compose web form.
 *
 * Configuration apply example:
 * <code>
 * $configManager = Yii::app()->getComponent('configManager');
 * Yii::app()->configure($configManager->fetchConfig());
 * </code>
 *
 * @see QsConfigItem
 * @see QsConfigStorage
 *
 * @property array[]|QsConfigItem[]|string $items public alias of {@link _items}.
 * @property QsConfigStorage|array $storage public alias of {@link _storage}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.config
 */
class QsConfigManager extends CApplicationComponent {
	/**
	 * @var array[]|QsConfigItem[]|string config items in format: id => configuration.
	 * This filed can be setup as PHP file name, which returns the array of items.
	 */
	protected $_items = array();
	/**
	 * @var QsConfigStorage|array config storage.
	 * It should be {@link QsConfigStorage} instance or its array configuration.
	 */
	protected $_storage = array(
		'class' => 'qs.config.QsConfigStorageDb'
	);
	/**
	 * @var string id of the cache application component.
	 */
	public $cacheComponentId = 'cache';
	/**
	 * @var string id, which will be used to stored composed application configuration
	 * in the cache.
	 */
	public $cacheId = 'QsConfigManager';
	/**
	 * @var integer duration of cache for models in seconds.
	 * '0' means never expire.
	 * Set this parameter to a negative integer to aviod caching.
	 */
	public $cacheDuration = 0;

	/**
	 * @param array|QsConfigStorage $storage
	 * @throws CException on invalid argument.
	 */
	public function setStorage($storage) {
		if (!is_array($storage) && !is_object($storage)) {
			throw new CException('"' . get_class($this) . '::storage" should be instance of "QsConfigStorage" or its array configuration. "' . gettype($storage) . '" given.');
		}
		$this->_storage = $storage;
	}

	/**
	 * @return array|QsConfigStorage
	 */
	public function getStorage() {
		if (!is_object($this->_storage)) {
			$this->_storage = Yii::createComponent($this->_storage);
		}
		return $this->_storage;
	}

	/**
	 * Creates config storage by given configuration.
	 * @param array $config storage configuration.
	 * @return QsConfigStorage config storage instance
	 */
	protected function createStorage(array $config) {
		$storage = Yii::createComponent($config);
		$storage->init();
		return $storage;
	}

	/**
	 * @param array|string $items
	 */
	public function setItems($items) {
		$this->_items = $items;
	}

	/**
	 * @return QsConfigItem[] config items
	 */
	public function getItems() {
		$this->normalizeItems();
		$items = array();
		foreach ($this->_items as $id => $item) {
			$items[] = $this->getItem($id);
		}
		return $items;
	}

	/**
	 * @param mixed $id item id
	 * @return QsConfigItem config item instance.
	 * @throws CException on failure.
	 */
	public function getItem($id) {
		$this->normalizeItems();
		if (!array_key_exists($id, $this->_items)) {
			throw new CException("Unknown config item '{$id}'.");
		}
		if (!is_object($this->_items[$id])) {
			$this->_items[$id] = $this->createItem($id, $this->_items[$id]);
		}
		return $this->_items[$id];
	}

	/**
	 * Creates config item by given configuration.
	 * @param mixed $id item id.
	 * @param array $config item configuration.
	 * @return QsConfigItem config item instance
	 */
	protected function createItem($id, array $config) {
		if (empty($config['class'])) {
			$config['class'] = 'qs.config.QsConfigItem';
		}
		$config['id'] = $id;
		return Yii::createComponent($config);
	}

	/**
	 * Normalizes {@link items} value, ensuring it is array.
	 * @throws CException on failure
	 */
	protected function normalizeItems() {
		if (!is_array($this->_items)) {
			if (is_string($this->_items)) {
				$fileName = $this->_items;
				if (file_exists($fileName)) {
					$this->_items = require($fileName);
					if (!is_array($this->_items)) {
						throw new CException('File "' . $fileName . '" should return an array.');
					}
				} else {
					throw new CException('File "' . $this->_items . '" does not exist.');
				}
			} else {
				throw new CException('"' . get_class($this) . '::items" should be array or file name containing it.');
			}
		}
	}

	/**
	 * @param array $itemValues config item values.
	 * @return QsConfigManager self reference.
	 */
	public function setItemValues(array $itemValues) {
		foreach ($itemValues as $id => $value) {
			$item = $this->getItem($id);
			$item->value = $value;
		}
		return $this;
	}

	/**
	 * @return array config item values
	 */
	public function getItemValues() {
		$itemValues = array();
		foreach ($this->getItems() as $item) {
			$itemValues[$item->id] = $item->value;
		}
		return $itemValues;
	}

	/**
	 * @return CCache cache component instance.
	 */
	public function getCacheComponent() {
		return Yii::app()->getComponent($this->cacheComponentId);
	}

	/**
	 * Composes application configuration array from config items.
	 * @return array application configuration.
	 */
	public function composeConfig() {
		$itemConfigs = array();
		foreach ($this->getItems() as $item) {
			$itemConfigs[] = $item->composeConfig();
		}
		return call_user_func_array(array('CMap', 'mergeArray'), $itemConfigs);
	}

	/**
	 * Saves the current config item values into the persistent storage.
	 * @return boolean success.
	 */
	public function saveValues() {
		$result = $this->getStorage()->save($this->getItemValues());
		if ($result) {
			$this->getCacheComponent()->delete($this->cacheId);
		}
		return $result;
	}

	/**
	 * Restores config item values from the persistent storage.
	 * @return QsConfigManager self reference.
	 */
	public function restoreValues() {
		return $this->setItemValues($this->getStorage()->get());
	}

	/**
	 * Clears config item values saved in the persistent storage.
	 * @return boolean success.
	 */
	public function clearValues() {
		$result = $this->getStorage()->clear();
		if ($result) {
			$this->getCacheComponent()->delete($this->cacheId);
		}
		return $result;
	}

	/**
	 * Composes the application configuration using config item values
	 * from the persistent storage.
	 * This method caches its result for the better performance.
	 * @return array application configuration.
	 */
	public function fetchConfig() {
		$cache = $this->getCacheComponent();
		$config = $cache->get($this->cacheId);
		if ($config === false) {
			$this->restoreValues();
			$config = $this->composeConfig();
			$cache->set($this->cacheId, $config, $this->cacheDuration);
		}
		return $config;
	}

	/**
	 * Performs the validation for all config item models at once.
	 * @return boolean whether the validation is successful without any error.
	 */
	public function validate() {
		$result = true;
		foreach ($this->getItems() as $item) {
			$isItemValid = $item->validate();
			$result = $result && $isItemValid;
		}
		return $result;
	}
}