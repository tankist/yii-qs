<?php
/**
 * QsActiveRecordBehaviorVariation class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows to manage variations for the entity.
 * Variation appears, when some entity have different data for the same fields depending on some option Id.
 * For example: manage language translations.
 * Behavior will automatically creates 2 relations: {@link variationsRelationName} - to manage all variations, {@link defaultVariationRelationName} - to get default variation.
 * Behavior allows access to the default variation model attributes directly as properties of the main model.
 * Config example:
 * <code>
 * array(
 *     'variationBehavior' => array(
 *         'class' => 'qs.db.ar.QsActiveRecordBehaviorVariation',
 *         'variatorModelClassName' => 'Language',
 *         'variationsRelationName' => 'translations',
 *         'defaultVariationRelationName' => 'translation',
 *         'relationConfig' => array(
 *             'ContentTranslation', 'content_id'
 *         ),
 *         'variationOptionForeignKeyName' => 'language_id',
 *         'defaultVariationOptionForeignKeyCallback' => array($this, 'findDefaultLanguageId'),
 *     )
 * );
 * </code>
 * 
 * Use method {@link getVariationModels()} to fetch all related models.
 * This method will adjust related model, creating the missing ones according to the {@link variatorModelClassName} records.
 * Form view example:
 * <code>
 * <div class="row">
 *     <?php echo $form->labelEx($model, 'name'); ?>
 *     <?php echo $form->textField($model, 'name'); ?>
 * </div>
 * <?php foreach ($model->getVariationModels() as $variationModel): ?>
 * <div class="row">
 *     <?php echo $form->labelEx($variationModel, 'title'); ?>
 *     <?php echo $form->textField($variationModel, 'title'); ?>
 * </div> 
 * <?php endforeach; ?>
 * </code>
 *
 * @property string $variationsRelationName public alias of {@link _variationsRelationName}.
 * @property string $defaultVariationRelationName public alias of {@link _defaultVariationRelationName}.
 * @property array $relationConfig public alias of {@link _relationConfig}.
 * @property string $variationOptionForeignKeyName public alias of {@link _variationOptionForeignKeyName}.
 * @property string $variatorModelClassName public alias of {@link _variatorModelClassName}.
 * @property callback $defaultVariationOptionForeignKeyCallback public alias of {@link _defaultVariationOptionForeignKeyCallback}.
 * @property array $autoAdjustVariationScenarios public alias of {@link _autoAdjustVariationScenarios}.
 * @property array $variationAttributeDefaultValueMap public alias of {@link _variationAttributeDefaultValueMap}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorVariation extends CBehavior {
	/**
	 * @var boolean indicates if relation has been initialized.
	 * Internal usage only.
	 */
	protected $_initialized = false;
	/**
	 * @var string name of relation, which corresponds all variations.
	 */
	protected $_variationsRelationName = 'variations';
	/**
	 * @var string name of relation, which corresponds default variation.
	 */
	protected $_defaultVariationRelationName = 'variation';
	/**
	 * @var array configuration of the variation relation.
	 * Relation type will be added automatically, so this config should be simple.
	 * Example:<code>array('ItemVariation', 'item_id')</code>
	 */
	protected $_relationConfig = array();
	/**
	 * @var string name of attribute, which store option primary key.
	 */
	protected $_variationOptionForeignKeyName = 'option_id';
	/**
	 * @var string name of {@link CActiveRecord} class, which determines variation options.
	 */
	protected $_variatorModelClassName = 'Variator';
	/**
	 * @var callback callback for the function, which should return default
	 * variation option primary key id.
	 */
	protected $_defaultVariationOptionForeignKeyCallback = array();
	/**
	 * @var array list of the scenarios, which should adjust variation values automatically.
	 */
	protected $_autoAdjustVariationScenarios = array(
		'insert',
		'update',
	);
	/**
	 * @var CActiveRecord[]|null cached variation models.
	 * This field is for the internal usage only.
	 */
	protected $_variationModelsCache = null;
	/**
	 * @var array map, which marks the attributes of main model, which should be a source for
	 * the default value for the variation model attributes.
	 * Format: variationModelAttributeName => mainModelAttributeName.
	 * For example:
	 * <code>
	 * array(
	 *     'title' => 'name',
	 *     'content' => 'default_content',
	 * );
	 * </code>
	 * Default value map will be used if default variation model not exists, or
	 * its requested attribute value is empty.
	 */
	protected $_variationAttributeDefaultValueMap = array();

	// Set / Get:

	public function setInitialized($initialized) {
		$this->_initialized = $initialized;
		return true;
	}

	public function getInitialized() {
		return $this->_initialized;
	}

	public function setVariationsRelationName($variationsRelationName) {
		if (!is_string($variationsRelationName)) {
			return false;
		}
		$this->_variationsRelationName = $variationsRelationName;
		return true;
	}

	public function getVariationsRelationName() {
		return $this->_variationsRelationName;
	}

	public function setDefaultVariationRelationName($defaultVariationRelationName) {
		if (!is_string($defaultVariationRelationName)) {
			return false;
		}
		$this->_defaultVariationRelationName = $defaultVariationRelationName;
		return true;
	}

	public function getDefaultVariationRelationName() {
		return $this->_defaultVariationRelationName;
	}

	public function setRelationConfig(array $relationConfig) {
		$this->_relationConfig = $relationConfig;
		return true;
	}

	public function getRelationConfig() {
		return $this->_relationConfig;
	}

	public function setVariationOptionForeignKeyName($variationOptionForeignKeyName) {
		if (!is_string($variationOptionForeignKeyName)) {
			return false;
		}
		$this->_variationOptionForeignKeyName = $variationOptionForeignKeyName;
		return true;
	}

	public function getVariationOptionForeignKeyName() {
		return $this->_variationOptionForeignKeyName;
	}

	public function setVariatorModelClassName($variatorModelClassName) {
		if (!is_string($variatorModelClassName)) {
			return false;
		}
		$this->_variatorModelClassName = $variatorModelClassName;
		return true;
	}

	public function getVariatorModelClassName() {
		return $this->_variatorModelClassName;
	}

	public function setDefaultVariationOptionForeignKeyCallback($defaultVariationOptionForeignKeyCallback) {
		if (!is_callable($defaultVariationOptionForeignKeyCallback, true)) {
			return false;
		}
		$this->_defaultVariationOptionForeignKeyCallback = $defaultVariationOptionForeignKeyCallback;
		return true;
	}

	public function getDefaultVariationOptionForeignKeyCallback() {
		return $this->_defaultVariationOptionForeignKeyCallback;
	}

	public function setAutoAdjustVariationScenarios(array $autoAdjustVariationScenarios) {
		$this->_autoAdjustVariationScenarios = $autoAdjustVariationScenarios;
		return true;
	}

	public function getAutoAdjustVariationScenarios() {
		return $this->_autoAdjustVariationScenarios;
	}

	public function setVariationAttributeDefaultValueMap(array $variationAttributeDefaultValueMap) {
		$this->_variationAttributeDefaultValueMap = $variationAttributeDefaultValueMap;
		return true;
	}

	public function getVariationAttributeDefaultValueMap() {
		return $this->_variationAttributeDefaultValueMap;
	}

	// Property Access Extension:

	public function __set($name, $value) {
		try {
			parent::__set($name, $value);
		} catch (CException $exception) {
			$variationModelFinder = $this->getVariationModelFinder();
			if (is_object($variationModelFinder) && $variationModelFinder->hasAttribute($name)) {
				$relatedModel = $this->getDefaultVariationModel();
				if (is_object($relatedModel)) {
					$relatedModel->$name = $value;
				} else {
					throw new CException('"' . get_class($this->getOwner()) . '::defaultVariationModel" not found!');
				}
			} else {
				throw $exception;
			}
		}
	}

	public function __get($name) {
		try {
			parent::__get($name);
		} catch (CException $exception) {
			$variationModelFinder = $this->getVariationModelFinder();
			if (is_object($variationModelFinder) && $variationModelFinder->hasAttribute($name)) {
				return $this->fetchDefaultVariationAttributeValue($name);
			} else {
				throw $exception;
			}
		}
	}

	public function canGetProperty($name) {
		$result = parent::canGetProperty($name);
		if (!$result) {
			$relatedModel = $this->getDefaultVariationModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				return true;
			}
		}
		return $result;
	}

	public function canSetProperty($name) {
		$result = parent::canSetProperty($name);
		if (!$result) {
			$relatedModel = $this->getDefaultVariationModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				return true;
			}
		}
		return $result;
	}

	/**
	 * Initializes variation relations for the owner object.
	 * @return boolean success.
	 */
	protected function initRelations() {
		return ($this->initVariationsRelation() && $this->initDefaultVariationRelation());
	}

	/**
	 * Initializes relation, which corresponds all variations.
	 * @return boolean success.
	 */
	protected function initVariationsRelation() {
		$config = $this->getRelationConfig();
		array_unshift($config, CActiveRecord::HAS_MANY);
		$owner = $this->getOwner();
		$owner->getMetaData()->addRelation($this->getVariationsRelationName(), $config);
		return true;
	}

	/**
	 * Initializes relation, which corresponds default variation.
	 * @return boolean success.
	 */
	protected function initDefaultVariationRelation() {
		$owner = $this->getOwner();

		$config = $this->getRelationConfig();
		array_unshift($config, CActiveRecord::HAS_ONE);
		if (!array_key_exists('joinType', $config)) {
			$config['joinType'] = $this->getDefaultVariationRelationDefaultJoinType();
		}
		if (array_key_exists('alias', $config)) {
			$relationAlias = $config['alias'];
		} else {
			$relationAlias = get_class($owner).ucfirst($this->getDefaultVariationRelationName());
			$config['alias'] = $relationAlias;
		}

		$defaultVariationOptionForeignKeyCallback = $this->getDefaultVariationOptionForeignKeyCallback();
		if (!empty($defaultVariationOptionForeignKeyCallback)) {
			$variationOptionForeignKey = call_user_func($defaultVariationOptionForeignKeyCallback);
			$variationOptionForeignKeyName = $this->getVariationOptionForeignKeyName();
			$config['on'] = $relationAlias . ".{$variationOptionForeignKeyName}=:{$variationOptionForeignKeyName}";
			$config['params'] = array(
				$variationOptionForeignKeyName => $variationOptionForeignKey
			);
		}

		$owner->getMetaData()->addRelation($this->getDefaultVariationRelationName(), $config);
		return true;
	}

	/**
	 * Returns the default join type for the default variation relation.
	 * @return string join type.
	 */
	protected function getDefaultVariationRelationDefaultJoinType() {
		return (YII_DEBUG || !empty($this->_variationAttributeDefaultValueMap)) ? 'LEFT JOIN' : 'INNER JOIN';
	}

	/**
	 * Initializes model, related as default variation for the owner object.
	 * @return boolean success.
	 */
	protected function initDefaultVariationModel() {
		$owner = $this->getOwner();
		$relationName = $this->getDefaultVariationRelationName();
		if (!is_object($owner->$relationName)) {
			$relatedClassName = $this->getRelationConfigParam('class');
			$owner->$relationName = new $relatedClassName();
		}
		return true;
	}

	/**
	 * Initializes all necessary entities.
	 * @return boolean success.
	 */
	protected function initOnce() {
		if (!$this->getInitialized()) {
			$this->initRelations();
			$this->initDefaultVariationModel();
			$this->setInitialized(true);
		}
		return true;
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
	 * Returns the variation model active record finder.
	 * @return CActiveRecord variation model finder.
	 */
	protected function getVariationModelFinder() {
		$className = $this->getRelationConfigParam('class');
		$finder = CActiveRecord::model($className);
		return $finder;
	}

	/**
	 * Returns model related to the main one as default variation
	 * according to the {@link relationConfig}.
	 * @return CActiveRecord related model instance.
	 */
	protected function getRelationDefaultVariationModel() {
		$owner = $this->getOwner();
		$relationName = $this->getDefaultVariationRelationName();
		return $owner->$relationName;
	}

	/**
	 * Returns model related to the main one as default variation.
	 * @return CActiveRecord related model instance.
	 */
	protected function getDefaultVariationModel() {
		return $this->getRelationDefaultVariationModel();
	}

	/**
	 * Returns models related to the main one as variations
	 * according to the {@link relationConfig}.
	 * @return CActiveRecord[] set of {@link CActiveRecord}.
	 */
	protected function getRelationVariationModels() {
		$owner = $this->getOwner();
		$relationName = $this->getVariationsRelationName();
		return $owner->$relationName;
	}

	/**
	 * Returns models related to the main one as variations.
	 * This method adjusts set of related models creating missing variations.
	 * @return CActiveRecord[] set of {@link CActiveRecord}.
	 */
	public function getVariationModels() {
		if (is_array($this->_variationModelsCache)) {
			return $this->_variationModelsCache;
		}

		$variationModels = $this->getRelationVariationModels();
		if (!is_array($variationModels)) {
			$variationModels = array();
		}
		$variationModels = $this->adjustVariationModels($variationModels);
		$this->_variationModelsCache = $variationModels;
		return $variationModels;
	}

	/**
	 * Adjusts given variation models to be adequate to the {@link variatorModelClassName} records.
	 * @param array|CActiveRecord[] $initialVariationModels set of initial variation models, found by relation
	 * @return CActiveRecord[] set of {@link CActiveRecord}
	 */
	protected function adjustVariationModels(array $initialVariationModels) {
		$variatorModelFinder = CActiveRecord::model($this->getVariatorModelClassName());
		$variators = $variatorModelFinder->findAll();

		$variatorForeignKeyName = $this->getVariationOptionForeignKeyName();
		$ownerForeignKeyName = $this->getRelationConfigParam('foreignKey');

		$variationModels = array();
		$confirmedInitialVariationModels = array();
		foreach ($variators as $variator) {
			$matchFound = false;
			foreach ($initialVariationModels as $initialVariationModel) {
				if ($variator->getPrimaryKey() == $initialVariationModel->$variatorForeignKeyName) {
					$variationModels[] = $initialVariationModel;
					$confirmedInitialVariationModels[] = $initialVariationModel;
					$matchFound = true;
					break;
				}
			}
			if (!$matchFound) {
				$owner = $this->getOwner();
				$variationClassName = $this->getRelationConfigParam('class');
				$variationModel = new $variationClassName();
				$variationModel->$variatorForeignKeyName = $variator->getPrimaryKey();
				$variationModel->$ownerForeignKeyName = $owner->getPrimaryKey();
				$variationModels[] = $variationModel;
			}
		}

		if (count($confirmedInitialVariationModels) < count($initialVariationModels)) {
			foreach ($initialVariationModels as $initialVariationModel) {
				$matchFound = false;
				foreach ($confirmedInitialVariationModels as $confirmedInitialVariationModel) {
					if ($confirmedInitialVariationModel->getPrimaryKey() == $initialVariationModel->getPrimaryKey()) {
						$matchFound = true;
						break;
					}
				}
				if (!$matchFound) {
					$initialVariationModel->delete();
				}
			}
		}

		return $variationModels;
	}

	/**
	 * Clears internal cache for the variation models,
	 * which are returned by {@link getVariationModels}.
	 * @return boolean success.
	 */
	public function clearVariationModelsCache() {
		$this->_variationModelsCache = null;
		return true;
	}

	/**
	 * Checks if current owner scenario matches {@link autoAdjustVariationScenarios}.
	 * @return boolean success.
	 */
	protected function checkAutoAdjustVariationScenario() {
		$owner = $this->getOwner();
		$scenarioName = $owner->getScenario();
		return (array_search($scenarioName, $this->getAutoAdjustVariationScenarios(), true) !== false);
	}

	/**
	 * Fetches the data of the variation attribute from the default variation model.
	 * If no default variation model exists or requested attribute is empty,
	 * the result value will be composed using {@link variationAttributeDefaultValueMap}.
	 * @param string $attributeName variation model attribute name.
	 * @return mixed attribute value.
	 */
	public function fetchDefaultVariationAttributeValue($attributeName) {
		$attributeValue = null;

		$relatedModel = $this->getDefaultVariationModel();
		if (is_object($relatedModel) && $relatedModel->hasAttribute($attributeName)) {
			$attributeValue = $relatedModel->$attributeName;
		}

		if (empty($attributeValue)) {
			if (array_key_exists($attributeName, $this->_variationAttributeDefaultValueMap)) {
				$ownerAttributeName = $this->_variationAttributeDefaultValueMap[$attributeName];
				$owner = $this->getOwner();
				$attributeValue = $owner->$ownerAttributeName;
			}
		}

		return $attributeValue;
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
	 * This method ensures related models will be automatically validated too.
	 * However their validation is performed only, if owner scenario matches {@link autoAdjustVariationScenarios}.
	 * @param CEvent $event event parameter.
	 */
	public function afterValidate($event) {
		if ($this->checkAutoAdjustVariationScenario()) {
			$this->initOnce();
			$relatedModels = $this->getVariationModels();

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
	 * It make sure related models will be automatically saved too.
	 * Saving of the related models will be performed only, if owner scenario matches {@link autoAdjustVariationScenarios}.
	 * @param CModelEvent $event event parameter
	 */
	public function afterSave($event) {
		if ($this->checkAutoAdjustVariationScenario()) {
			$this->initOnce();
			$relatedModels = $this->getVariationModels();
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
			$this->clearVariationModelsCache();
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeDelete} event.
	 * This method ensures related models will be automatically deleted too.
	 * @param CEvent $event event parameter
	 */
	public function beforeDelete($event) {
		$this->initOnce();
		$relatedModels = $this->getRelationVariationModels();
		if (is_array($relatedModels)) {
			foreach ($relatedModels as $relatedModel) {
				$relatedModel->delete();
			}
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeFind} event.
	 * This method ensures default variation relation will be automatically applied.
	 * @param CEvent $event event parameter
	 */
	public function beforeFind($event) {
		$this->initOnce();
		$criteria = $event->sender->getDbCriteria(true);
		if (!is_array($criteria->with)) {
			$criteria->with = array();
		}
		$criteria->with[] = $this->getDefaultVariationRelationName();
	}
}