<?php
/**
 * QsActiveRecordBehaviorRole class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows to use model related as {@link CActiveRecord::HAS_ONE} as the part of the main model.
 * Behavior will initialize the relation automatically.
 * Behavior allows access to the related model attributes directly as properties of the main model.
 * Related model will be validated and saved automatically with the main one.
 *
 * @property string $relationName public alias of {@link _relationName}.
 * @property array $relationConfig public alias of {@link _relationConfig}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorRole extends CBehavior {
	/**
	 * @var string name of the role relation.
	 */
	protected $_relationName = '';
	/**
	 * @var array configuration of the role relation.
	 * Relation type {@link CActiveRecord::HAS_ONE} will be added automatically,
	 * so this config should be simple.
	 * Example:<code>array('UserProfile', 'user_id')</code>
	 */
	protected $_relationConfig = array();
	/**
	 * @var boolean indicates if relation has been initialized.
	 * Internal usage only.
	 */
	protected $_isRelationInitialized = false;
	/**
	 * @var boolean indicates if related model has been initialized.
	 * Internal usage only.
	 */
	protected $_isRelatedModelInitialized = false;

	// Set / Get:

	public function setRelationName($relationName) {
		if (!is_string($relationName)) {
			return false;
		}
		$this->_relationName = $relationName;
		return true;
	}

	public function getRelationName() {
		return $this->_relationName;
	}

	public function setRelationConfig(array $relationConfig) {
		$this->_relationConfig = $relationConfig;
		return true;
	}

	public function getRelationConfig() {
		return $this->_relationConfig;
	}

	// Property Access Extension:

	public function __set($name, $value) {
		try {
			parent::__set($name, $value);
		} catch (CException $exception) {
			$relatedModel = $this->getRelatedModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				$relatedModel->$name = $value;
			} else {
				throw $exception;
			}
		}
	}

	public function __get($name) {
		try {
			parent::__get($name);
		} catch (CException $exception) {
			$relatedModel = $this->getRelatedModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				return $relatedModel->$name;
			} else {
				throw $exception;
			}
		}
	}

	public function canGetProperty($name) {
		$result = parent::canGetProperty($name);
		if (!$result) {
			$relatedModel = $this->getRelatedModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				return true;
			}
		}
		return $result;
	}

	public function canSetProperty($name) {
		$result = parent::canSetProperty($name);
		if (!$result) {
			$relatedModel = $this->getRelatedModel();
			if (is_object($relatedModel) && $relatedModel->hasAttribute($name)) {
				return true;
			}
		}
		return $result;
	}

	/**
	 * Initializes role relation for the owner object.
	 * @return boolean success.
	 */
	protected function initRelation() {
		$config = $this->getRelationConfig();
		array_unshift($config, CActiveRecord::HAS_ONE);
		$config['joinType'] = 'INNER JOIN';
		$config['together'] = true;
		$owner = $this->getOwner();
		$owner->getMetaData()->addRelation($this->getRelationName(), $config);
		return true;
	}

	/**
	 * Initializes related model for the owner object.
	 * This method make sure each new owner model has new related one.
	 * @return boolean success.
	 */
	protected function initRelatedModel() {
		$owner = $this->getOwner();
		$relationName = $this->getRelationName();
		if ($owner->getIsNewRecord() || !is_object($owner->$relationName)) {
			$relatedClassName = $this->getRelationConfigParam('class');
			$owner->$relationName = new $relatedClassName();
		}
		return true;
	}

	/**
	 * Initializes relation ensuring this action will be performed only once.
	 * @return boolean success.
	 */
	protected function initRelationOnce() {
		if (!$this->_isRelationInitialized) {
			$this->initRelation();
			$this->_isRelationInitialized = true;
		}
		return true;
	}

	/**
	 * Initializes related model ensuring this action will be performed only once.
	 * @return boolean success.
	 */
	protected function initRelatedModelOnce() {
		if (!$this->_isRelatedModelInitialized) {
			$this->initRelationOnce();
			$this->initRelatedModel();
			$this->_isRelatedModelInitialized = true;
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
	 * Returns model related to the main one.
	 * @return CActiveRecord related model.
	 */
	protected function getRelatedModel() {
		$this->initRelatedModelOnce();
		$owner = $this->getOwner();
		$relationName = $this->getRelationName();
		return $owner->$relationName;
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
	 * This event raises after owner creation.
	 * @param CEvent $event event object.
	 */
	public function afterConstruct($event) {
		$this->initRelatedModelOnce();
	}

	/**
	 * This event raises after owner validation.
	 * It make ensure related model will be automatically validated too.
	 * Related model errors will be append to the owner.
	 * @param CEvent $event event object.
	 */
	public function afterValidate($event) {
		$relatedModel = $this->getRelatedModel();
		if (is_object($relatedModel)) {
			if (!$relatedModel->validate()) {
				$owner = $this->getOwner();
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

	/**
	 * This event raises after owner saved.
	 * It make sure related model will be automatically saved too.
	 * @param CEvent $event event object.
	 */
	public function afterSave($event) {
		$relatedModel = $this->getRelatedModel();
		if (is_object($relatedModel)) {
			$owner = $this->getOwner();
			$ownerPrimaryKeyValue = $owner->getPrimaryKey();

			$foreignKey = $this->getRelationConfigParam('foreignKey');
			$relatedModel->setAttribute($foreignKey, $ownerPrimaryKeyValue);

			$relatedModel->save(false);
		}
	}

	/**
	 * This event raises before owner deleted.
	 * It make sure related model will be automatically deleted too.
	 * @param CEvent $event event object.
	 */
	public function beforeDelete($event) {
		$relatedModel = $this->getRelatedModel();
		if (is_object($relatedModel)) {
			$relatedModel->delete();
		}
	}

	/**
	 * This event raises before any find method of the owner.
	 * It make sure role relation will be automatically applied
	 * to the search criteria.
	 * @param CEvent $event event object.
	 */
	public function beforeFind($event) {
		$this->initRelationOnce();
		$criteria = $event->sender->getDbCriteria(true);
		if ( !is_array($criteria->with) ) {
			$criteria->with = array();
		}
		$criteria->with[] = $this->getRelationName();
	}
}