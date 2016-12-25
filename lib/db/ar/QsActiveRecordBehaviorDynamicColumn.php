<?php
/**
 * QsActiveRecordBehaviorDynamicColumn class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsActiveRecordBehaviorDynamicColumn is a behavior for the {@link CActiveRecord},
 * which allows to manage dynamic entity columns, stored in separated table.
 * This is basic implementation of Entity–attribute–value model (EAV).
 *
 * Behavior requires the separated table, which determines the set of columns.
 * Example migration for such table:
 * <code>
 * $tableName = 'item_column';
 * $columns = array(
 *     'id' => 'pk',
 *     'name' => 'string NOT NULL',
 *     'label' => 'string NOT NULL',
 *     'default_value' => 'string',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * </code>
 * Behavior requires the separated table, which should store the column values.
 * Example migration for such table:
 * <code>
 * $tableName = 'item_column_value';
 * $columns = array(
 *     'id' => 'pk',
 *     'item_id' => 'integer',
 *     'item_column_id' => 'integer',
 *     'value' => 'text',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * $this->addForeignKey("fk_{$tableName}_item_id", $tableName, 'item_id', 'item', 'id');
 * $this->addForeignKey("fk_{$tableName}_item_column_id", $tableName, 'item_column_id', 'item_column', 'id');
 * </code>
 *
 * Behavior config example:
 * <code>
 * array(
 *     'variationBehavior' => array(
 *         'class' => 'qs.db.ar.QsActiveRecordBehaviorDynamicColumn',
 *         'columnModelClassName' => 'ItemColumn',
 *         'relationConfig' => array(
 *             'ItemColumnValue', 'item_id'
 *         ),
 *     )
 * );
 * </code>
 *
 * Use method {@link getColumnValueModels()} to fetch all related models.
 *
 * @see http://en.wikipedia.org/wiki/Entity%E2%80%93attribute%E2%80%93value_model
 *
 * @property CActiveRecord[] $columnModels public alias of {@link _columnModels}.
 * @property string $columnModelClassName public alias of {@link _columnModelClassName}.
 * @property CDbCriteria|array $columnModelSearchCriteria public alias of {@link _columnModelSearchCriteria}.
 * @property callback $columnModelSearchCriteriaCallback public alias of {@link _columnModelSearchCriteriaCallback}.
 * @property string $columnValueRelationName public alias of {@link _columnValueRelationName}.
 * @property array $relationConfig public alias of {@link _relationConfig}.
 * @property array $columnValueModels public alias of {@link _columnValueModels}.
 * @property array $autoAdjustColumnValueScenarios public alias of {@link _autoAdjustColumnValueScenarios}.
 * @property string $columnValueColumnForeignKeyName public alias of {@link _columnValueColumnForeignKeyName}.
 * @property string $columnNameAttributeName public alias of {@link _columnNameAttributeName}.
 * @property string $columnDefaultValueAttributeName public alias of {@link _columnDefaultValueAttributeName}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorDynamicColumn extends CBehavior {
	/**
	 * @var boolean indicates if relation has been initialized.
	 * Internal usage only.
	 */
	protected $_isInitialized = false;
	/**
	 * @var array|null cached column models  in format: 'column name'=>'column model'.
	 */
	protected $_columnModels = null;
	/**
	 * @var string name of {@link CActiveRecord} class, which determines dynamic columns.
	 */
	protected $_columnModelClassName = 'ItemColumn';
	/**
	 * @var CDbCriteria|array column model search criteria.
	 */
	protected $_columnModelSearchCriteria = array();
	/**
	 * @var callback PHP callback, which should return the search criteria for {@link columnModelClassName} model.
	 * The callback should return {@link CDbCriteria} instance or its configuration array.
	 * The criteria, returned by this callback will be merged with the {@link columnModelSearchCriteria}.
	 */
	protected $_columnModelSearchCriteriaCallback = null;
	/**
	 * @var string name of relation, which corresponds column values.
	 */
	protected $_columnValueRelationName = 'columnValues';
	/**
	 * @var array configuration of the dynamic column value relation.
	 * Relation type will be added automatically, so this config should be simple.
	 * Example:<code>array('ItemColumnValue', 'item_id')</code>
	 */
	protected $_relationConfig = array();
	/**
	 * @var CActiveRecord[] list of column value models in format: 'column name'=>'column value model'.
	 */
	protected $_columnValueModels = null;
	/**
	 * @var array list of the scenarios, which should adjust variation values automatically.
	 */
	protected $_autoAdjustColumnValueScenarios = array(
		'insert',
		'update',
	);
	/**
	 * @var string name of column value model attribute, which store column model primary key.
	 */
	protected $_columnValueColumnForeignKeyName = 'column_id';
	/**
	 * @var string name of the {@link columnModelClassName} model attribute, which stores dynamic column name.
	 */
	protected $_columnNameAttributeName = 'name';
	/**
	 * @var string name of the column value model attribute, which stores dynamic column value.
	 */
	protected $_columnValueAttributeName = 'value';
	/**
	 * @var string name of the {@link columnModelClassName} model attribute, which stores dynamic column default value.
	 */
	protected $_columnDefaultValueAttributeName = 'default_value';

	// Set / Get :

	public function setIsInitialized($isInitialized) {
		$this->_isInitialized = $isInitialized;
		return true;
	}

	public function getIsInitialized() {
		return $this->_isInitialized;
	}

	public function setColumnModels($columnModels) {
		$this->_columnModels = $columnModels;
		return true;
	}

	/**
	 * @return CActiveRecord[] column models.
	 */
	public function getColumnModels() {
		if (!is_array($this->_columnModels)) {
			$this->initColumnModels();
		}
		return $this->_columnModels;
	}

	public function setColumnModelClassName($columnModelClassName) {
		if (!is_string($columnModelClassName)) {
			throw new CException('"' . get_class($this) . '::columnModelClassName" should be a string!');
		}
		$this->_columnModelClassName = $columnModelClassName;
		return true;
	}

	public function getColumnModelClassName() {
		return $this->_columnModelClassName;
	}

	public function setColumnModelSearchCriteria($columnModelSearchCriteria) {
		if (is_scalar($columnModelSearchCriteria)) {
			throw new CException('"' . get_class($this) . '::columnModelSearchCriteria" should be an instance of "CDbCriteria" or its configuration array!');
		}
		$this->_columnModelSearchCriteria = $columnModelSearchCriteria;
		return true;
	}

	public function getColumnModelSearchCriteria() {
		return $this->_columnModelSearchCriteria;
	}

	public function setColumnModelSearchCriteriaCallback($columnModelSearchCriteriaCallback) {
		if (!is_callable($columnModelSearchCriteriaCallback, true)) {
			throw new CException('"' . get_class($this) . '::columnModelSearchCriteriaCallback" should be a valid callback!');
		}
		$this->_columnModelSearchCriteriaCallback = $columnModelSearchCriteriaCallback;
		return true;
	}

	public function getColumnModelSearchCriteriaCallback() {
		return $this->_columnModelSearchCriteriaCallback;
	}

	public function setColumnValueRelationName($columnValueRelationName) {
		if (!is_string($columnValueRelationName)) {
			throw new CException('"' . get_class($this) . '::columnValueRelationName" should be a string!');
		}
		$this->_columnValueRelationName = $columnValueRelationName;
		return true;
	}

	public function getColumnValueRelationName() {
		return $this->_columnValueRelationName;
	}

	public function setRelationConfig(array $relationConfig) {
		$this->_relationConfig = $relationConfig;
		return true;
	}

	public function getRelationConfig() {
		return $this->_relationConfig;
	}

	public function setColumnValueModels($columnValueModels) {
		$this->_columnValueModels = $columnValueModels;
		return true;
	}

	public function getColumnValueModels() {
		if (!is_array($this->_columnValueModels)) {
			$this->initColumnValueModels();
		}
		return $this->_columnValueModels;
	}

	public function setAutoAdjustColumnValueScenarios(array $autoAdjustColumnValueScenarios) {
		$this->_autoAdjustColumnValueScenarios = $autoAdjustColumnValueScenarios;
		return true;
	}

	public function getAutoAdjustColumnValueScenarios() {
		return $this->_autoAdjustColumnValueScenarios;
	}

	public function setColumnValueColumnForeignKeyName($columnValueColumnForeignKeyName) {
		if (!is_string($columnValueColumnForeignKeyName)) {
			throw new CException('"' . get_class($this) . '::columnValueColumnForeignKeyName" should be a string!');
		}
		$this->_columnValueColumnForeignKeyName = $columnValueColumnForeignKeyName;
		return true;
	}

	public function getColumnValueColumnForeignKeyName() {
		return $this->_columnValueColumnForeignKeyName;
	}

	public function setColumnDefaultValueAttributeName($columnDefaultValueAttributeName) {
		if (!is_string($columnDefaultValueAttributeName)) {
			throw new CException('"' . get_class($this) . '::columnDefaultValueAttributeName" should be a string!');
		}
		$this->_columnDefaultValueAttributeName = $columnDefaultValueAttributeName;
		return true;
	}

	public function getColumnDefaultValueAttributeName() {
		return $this->_columnDefaultValueAttributeName;
	}

	public function setColumnNameAttributeName($columnNameAttributeName) {
		if (!is_string($columnNameAttributeName)) {
			throw new CException('"' . get_class($this) . '::columnNameAttributeName" should be a string!');
		}
		$this->_columnNameAttributeName = $columnNameAttributeName;
		return true;
	}

	public function getColumnNameAttributeName() {
		return $this->_columnNameAttributeName;
	}

	public function setColumnValueAttributeName($columnValueAttributeName) {
		if (!is_string($columnValueAttributeName)) {
			throw new CException('"' . get_class($this) . '::columnValueAttributeName" should be a string!');
		}
		$this->_columnValueAttributeName = $columnValueAttributeName;
		return true;
	}

	public function getColumnValueAttributeName() {
		return $this->_columnValueAttributeName;
	}

	// Property Access Extension:

	public function __set($name, $value) {
		try {
			parent::__set($name, $value);
		} catch (CException $exception) {
			$columnValueModels = $this->getColumnValueModels();
			if (array_key_exists($name, $columnValueModels)) {
				$valueAttributeName = $this->getColumnValueAttributeName();
				$columnValueModels[$name]->$valueAttributeName = $value;
			} else {
				throw $exception;
			}
		}
	}

	public function __get($name) {
		try {
			parent::__get($name);
		} catch (CException $exception) {
			$columnValueModels = $this->getColumnValueModels();
			if (array_key_exists($name, $columnValueModels)) {
				$valueAttributeName = $this->getColumnValueAttributeName();
				return $columnValueModels[$name]->$valueAttributeName;
			} else {
				throw $exception;
			}
		}
	}

	public function canGetProperty($name) {
		$result = parent::canGetProperty($name);
		if (!$result) {
			$columnValueModels = $this->getColumnValueModels();
			if (array_key_exists($name, $columnValueModels)) {
				return true;
			}
		}
		return $result;
	}

	public function canSetProperty($name) {
		$result = parent::canSetProperty($name);
		if (!$result) {
			$columnValueModels = $this->getColumnValueModels();
			if (array_key_exists($name, $columnValueModels)) {
				return true;
			}
		}
		return $result;
	}

	/**
	 * Returns the column model by given name.
	 * @param string $columnName the column name.
	 * @return CActiveRecord|null column model.
	 */
	public function getColumnModel($columnName) {
		$columnModels = $this->getColumnModels();
		if (!is_array($columnModels) || !array_key_exists($columnName, $columnModels)) {
			return null;
		}
		return $columnModels[$columnName];
	}

	/**
	 * Returns the column value model by given name.
	 * @param string $columnName the column name.
	 * @return CActiveRecord|null column value model.
	 */
	public function getColumnValueModel($columnName) {
		$columnValueModels = $this->getColumnValueModels();
		if (!is_array($columnValueModels) || !array_key_exists($columnName, $columnValueModels)) {
			return null;
		}
		return $columnValueModels[$columnName];
	}

	/**
	 * Initializes the {@link columnModels} value.
	 * @return boolean success.
	 */
	protected function initColumnModels() {
		$columnModelFinder = CActiveRecord::model($this->getColumnModelClassName());

		$criteria = $this->getColumnModelSearchCriteria();
		if (!is_object($criteria)) {
			$criteria = new CDbCriteria($criteria);
		}
		if (!empty($this->_columnModelSearchCriteriaCallback)) {
			$additionalCriteria = call_user_func($this->_columnModelSearchCriteriaCallback);
			$criteria->mergeWith($additionalCriteria);
		}

		$columnModels = array();
		$rawColumnModels = $columnModelFinder->findAll($criteria);
		if (is_array($rawColumnModels)) {
			$columnNameAttributeName = $this->getColumnNameAttributeName();
			foreach ($rawColumnModels as $rawColumnModel) {
				$columnModels[$rawColumnModel->$columnNameAttributeName] = $rawColumnModel;
			}
		}

		$this->_columnModels = $columnModels;
		return true;
	}

	/**
	 * Initializes relation, which corresponds all variations.
	 * @return boolean success.
	 */
	protected function initColumnValuesRelation() {
		$config = $this->getRelationConfig();
		array_unshift($config, CActiveRecord::HAS_MANY);
		$owner = $this->getOwner();
		$owner->getMetaData()->addRelation($this->getColumnValueRelationName(), $config);
		return true;
	}

	/**
	 * Initializes all necessary entities.
	 * @return boolean success.
	 */
	protected function initOnce() {
		if (!$this->getIsInitialized()) {
			$this->initColumnValuesRelation();
			$this->setIsInitialized(true);
		}
		return true;
	}

	/**
	 * Initializes the {@link columnValueModels} value.
	 * @return boolean success.
	 */
	public function initColumnValueModels() {
		$columnValueModels = $this->getRelationColumnValueModels();
		if (!is_array($columnValueModels)) {
			$columnValueModels = array();
		}
		$columnValueModels = $this->adjustColumnValueModels($columnValueModels);
		$this->_columnValueModels = $columnValueModels;
		return true;
	}

	/**
	 * Returns models related to the main one as dynamic column values
	 * according to the {@link relationConfig}.
	 * @return CActiveRecord[] set of {@link CActiveRecord}.
	 */
	protected function getRelationColumnValueModels() {
		$owner = $this->getOwner();
		$relationName = $this->getColumnValueRelationName();
		return $owner->$relationName;
	}

	/**
	 * Adjusts given column value models to be adequate to the {@link columnModelClassName} records.
	 * @param array|CActiveRecord[] $initialColumnValueModels set of initial column value models, found by relation.
	 * @return array set of {@link CActiveRecord}.
	 */
	protected function adjustColumnValueModels(array $initialColumnValueModels) {
		$columnModels = $this->getColumnModels();

		$nameAttributeName = $this->getColumnNameAttributeName();

		$columnForeignKeyName = $this->getColumnValueColumnForeignKeyName();
		$ownerForeignKeyName = $this->getRelationConfigParam('foreignKey');

		$columnValueModels = array();
		$confirmedInitialColumnValueModels = array();
		foreach ($columnModels as $columnModel) {
			$matchFound = false;
			foreach ($initialColumnValueModels as $initialColumnValueModel) {
				if ($columnModel->getPrimaryKey() == $initialColumnValueModel->$columnForeignKeyName) {
					$columnValueModels[$columnModel->$nameAttributeName] = $initialColumnValueModel;
					$confirmedInitialColumnValueModels[] = $initialColumnValueModel;
					$matchFound = true;
					break;
				}
			}
			if (!$matchFound) {
				$owner = $this->getOwner();
				$columnValueClassName = $this->getRelationConfigParam('class');
				$columnValueModel = new $columnValueClassName();
				$columnValueModel->$columnForeignKeyName = $columnModel->getPrimaryKey();
				$columnValueModel->$ownerForeignKeyName = $owner->getPrimaryKey();
				// Default value:
				$defaultValuePropertyName = $this->getColumnDefaultValueAttributeName();
				if (in_array($defaultValuePropertyName, $columnModel->attributeNames(), true)) {
					$valueAttributeName = $this->getColumnValueAttributeName();
					$columnValueModel->$valueAttributeName = $columnModel->$defaultValuePropertyName;
				}

				$columnValueModels[$columnModel->$nameAttributeName] = $columnValueModel;
			}
		}

		if (count($confirmedInitialColumnValueModels) < count($initialColumnValueModels)) {
			foreach ($initialColumnValueModels as $initialColumnValueModel) {
				$matchFound = false;
				foreach ($confirmedInitialColumnValueModels as $confirmedInitialVariationModel) {
					if ($confirmedInitialVariationModel->getPrimaryKey() == $initialColumnValueModel->getPrimaryKey()) {
						$matchFound = true;
						break;
					}
				}
				if (!$matchFound) {
					$initialColumnValueModel->delete();
				}
			}
		}

		return $columnValueModels;
	}

	/**
	 * Return parameter from {@link relationConfig} by name.
	 * @param string $paramName - name of the parameter
	 * @return mixed value of parameter
	 */
	public function getRelationConfigParam($paramName) {
		$configKey = $paramName;
		switch ($configKey) {
			case 'class': {
				$configKey = 0;
				break;
			}
			case 'foreignKey': {
				$configKey = 1;
				break;
			}
		}
		return $this->_relationConfig[$configKey];
	}

	/**
	 * Checks if current owner scenario matches {@link autoAdjustVariationScenarios}.
	 * @return boolean success.
	 */
	protected function checkAutoAdjustColumnValueScenario() {
		$owner = $this->getOwner();
		$scenarioName = $owner->getScenario();
		return (array_search($scenarioName, $this->getAutoAdjustColumnValueScenarios(), true) !== false);
	}

	// Events:

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onAfterConstruct' => 'afterConstruct',
			'onAfterValidate' => 'afterValidate',
			'onAfterSave' => 'afterSave',
			'onBeforeDelete' => 'beforeDelete',
			'onBeforeFind' => 'beforeFind',
		);
	}

	/**
	 * Responds to {@link CModel::onAfterConstruct} event.
	 * @param CEvent $event event parameter
	 */
	public function afterConstruct($event) {
		$this->initOnce();
	}

	/**
	 * Responds to {@link CModel::onAfterValidate} event.
	 * This method ensures related column value models will be automatically validated too.
	 * However their validation is performed only, if owner scenario matches {@link autoAdjustColumnValueScenarios}.
	 * @param CEvent $event event parameter.
	 */
	public function afterValidate($event) {
		if ($this->checkAutoAdjustColumnValueScenario()) {
			$this->initOnce();
			$relatedModels = $this->getColumnValueModels();

			if (is_array($relatedModels)) {
				$owner = $this->getOwner();
				foreach ($relatedModels as $relatedModel) {
					if (is_object($relatedModel)) {
						if (!$relatedModel->validate()) {
							$relatedModelErrors = $relatedModel->getErrors();
							foreach ($relatedModelErrors as $attributeName => $errors) {
								if (is_array($errors)) {
									foreach ($errors as $error) {
										$owner->addError($attributeName, $error);
									}
								} else {
									$owner->addError($attributeName, $errors);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onAfterSave} event.
	 * This method ensures related models will be automatically saved too.
	 * Saving of the related models will be performed only, if owner scenario matches {@link autoAdjustColumnValueScenarios}.
	 * @param CModelEvent $event event parameter
	 */
	public function afterSave($event) {
		if ($this->checkAutoAdjustColumnValueScenario()) {
			$this->initOnce();
			$relatedModels = $this->getColumnValueModels();
			if (is_array($relatedModels)) {
				$owner = $this->getOwner();
				foreach ($relatedModels as $relatedModel) {
					if (is_object($relatedModel)) {
						$ownerPrimaryKeyValue = $owner->getPrimaryKey();
						$foreignKey = $this->getRelationConfigParam('foreignKey');
						$relatedModel->setAttribute($foreignKey, $ownerPrimaryKeyValue);
						$relatedModel->save();
					}
				}
			}
			$this->setColumnValueModels(null);
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeDelete} event.
	 * This method ensures related models will be automatically deleted too.
	 * @param CEvent $event event parameter
	 */
	public function beforeDelete($event) {
		$this->initOnce();
		$relatedModels = $this->getRelationColumnValueModels();
		if (is_array($relatedModels)) {
			foreach ($relatedModels as $relatedModel) {
				$relatedModel->delete();
			}
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeFind} event.
	 * This method ensures column value relation will be automatically applied.
	 * @param CEvent $event event parameter
	 */
	public function beforeFind($event) {
		$this->initOnce();
	}
}
