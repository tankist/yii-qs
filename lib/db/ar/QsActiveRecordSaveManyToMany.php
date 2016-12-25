<?php
/**
 * QsActiveRecordSaveManyToMany class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows to save "many to many" relationship
 * database records.
 * This behavior adds the attribute to its owner, which name is equal to {@link relationAttributeName}.
 * Use this attribute to create checkbox list for the model edit.
 * Attention: in order work properly, attribute named {@link relationAttributeName} should be marked as "safe"
 * in the model validation rules.
 *
 * Behavior attach example:
 * <code>
 * class Item extends CActiveRecord {
 *     ...
 *     public function relations() {
 *         return array(
 *             'options' => array(
 *                 self::MANY_MANY, 'ItemOptions', 'item_option_references(item_id, option_id)'
 *             ),
 *         );
 *     }
 *
 *     public function behaviors() {
 *        return array(
 *            'saveManyToManyOptions' => array(
 *               'class' => 'qs.db.ar.QsActiveRecordSaveManyToMany',
 *               'relationName' => 'options',
 *               'relationAttributeName' => 'option_ids',
 *        ));
 *     }
 *
 *     public function rules() {
 *         return array(
 *             ...
 *             array('option_ids', 'safe'),
 *         );
 *     }
 * }
 * </code>
 *
 * @property string relationName interface for {@link _relationName}.
 * @property string relationAttributeName interface for {@link _relationAttributeName}.
 * @property string relationAttributeValue interface for {@link _relationAttributeValue}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordSaveManyToMany extends CBehavior {
	/**
	 * @var string name of the owner model "many to many" relation,
	 * which should be handled.
	 */
	protected $_relationName = '';
	/**
	 * @var string name of the owner model attribute, which should be used to set
	 * "many to many" relation values.
	 */
	protected $_relationAttributeName = '';
	/**
	 * @var array relation attribute value, which stores related record primary keys.
	 */
	protected $_relationAttributeValue = null;
	/**
	 * @var CManyManyRelation|null current owner "many to many" relation.
	 * This field is for internal usage only.
	 */
	protected $_relation = null;
	/**
	 * @var CDbTableSchema|null the related table schema information.
	 * This field is for internal usage only.
	 */
	protected $_relatedTableSchema = null;

	// Set / Get :

	public function setRelationName($relationName) {
		$this->_relationName = $relationName;
		return true;
	}

	public function getRelationName() {
		return $this->_relationName;
	}

	public function setRelationAttributeName($relationAttributeName) {
		$this->_relationAttributeName = $relationAttributeName;
		return true;
	}

	public function getRelationAttributeName() {
		if (empty($this->_relationAttributeName)) {
			$this->initRelationAttributeName();
		}
		return $this->_relationAttributeName;
	}

	public function setRelationAttributeValue($relationAttributeValue) {
		if (!is_array($relationAttributeValue)) {
			if ($relationAttributeValue !== null) {
				$relationAttributeValue = array(
					$relationAttributeValue
				);
			} else {
				$relationAttributeValue = array();
			}
		}
		$this->_relationAttributeValue = $relationAttributeValue;
		return true;
	}

	public function getRelationAttributeValue() {
		if (!is_array($this->_relationAttributeValue)) {
			$this->initRelationAttributeValue();
		}
		return $this->_relationAttributeValue;
	}

	/**
	 * @return CManyManyRelation
	 */
	public function getRelation() {
		if ($this->_relation===null) {
			$this->initRelation();
		}
		return $this->_relation;
	}

	/**
	 * @return CDbTableSchema
	 */
	public function getRelatedTableSchema() {
		if ($this->_relatedTableSchema===null) {
			$this->initRelatedTableSchema();
		}
		return $this->_relatedTableSchema;
	}

	// Property Access Extension:

	public function __set($name, $value) {
		try {
			parent::__set($name, $value);
		} catch (CException $exception) {
			if (strcasecmp($name, $this->getRelationAttributeName()) == 0) {
				$this->setRelationAttributeValue($value);
			} else {
				throw $exception;
			}
		}
	}

	public function __get($name) {
		try {
			parent::__get($name);
		} catch (CException $exception) {
			if (strcasecmp($name, $this->getRelationAttributeName()) == 0) {
				return $this->getRelationAttributeValue();
			} else {
				throw $exception;
			}
		}
	}

	public function canGetProperty($name) {
		$result = parent::canGetProperty($name);
		if (!$result) {
			if (strcasecmp($name, $this->getRelationAttributeName()) == 0) {
				return true;
			}
		}
		return $result;
	}

	public function canSetProperty($name) {
		$result = parent::canSetProperty($name);
		if (!$result) {
			if (strcasecmp($name, $this->getRelationAttributeName()) == 0) {
				return true;
			}
		}
		return $result;
	}

	/**
	 * Initializes {@link relationAttributeName} value.
	 * @return boolean success.
	 */
	protected function initRelationAttributeName() {
		$relationName = $this->getRelationName();
		$relationAttributeName = $relationName . '_ids';
		$this->_relationAttributeName = $relationAttributeName;
		return true;
	}

	/**
	 * Initializes {@link relationAttributeValue} value,
	 * using information from the owner relation {@link relationName}.
	 * @return boolean success.
	 */
	protected function initRelationAttributeValue() {
		$this->_relationAttributeValue = $this->getCurrentRelatedRecordPrimaryKeys();
		return true;
	}

	/**
	 * Initializes {@link relation} value with the current owner "many to many" relation by name {@link relationName}.
	 * @throws CException if relation not found.
	 * @return boolean success.
	 */
	protected function initRelation() {
		$owner = $this->getOwner();
		$relationName = $this->getRelationName();
		if (!$owner->getMetaData()->hasRelation($relationName)) {
			throw new CException('Unable to find relation "' . $relationName . '" for active record "' . get_class($owner) . '"');
		}
		$this->_relation = $owner->getMetaData()->relations[$relationName];
		return true;
	}

	/**
	 * Initializes {@link relatedTableSchema}, based on the the current owner "many to many" relation information.
	 * @return boolean success.
	 */
	protected function initRelatedTableSchema() {
		$relation = $this->getRelation();
		$relatedModelClassName = $relation->className;
		$relatedModelFinder = CActiveRecord::model($relatedModelClassName);
		$relatedTableSchema = $relatedModelFinder->getMetaData()->tableSchema;
		$this->_relatedTableSchema = $relatedTableSchema;
		return true;
	}

	/**
	 * Returns the command builder used by owner AR.
	 * @return CDbCommandBuilder the command builder used by owner AR
	 */
	protected function getDbCommandBuilder() {
		$owner = $this->getOwner();
		return $owner->getCommandBuilder();
	}

	/**
	 * Returns primary key values for all records, which can be related through the
	 * specified "many to many" relation.
	 * @return array list of primary key values.
	 */
	protected function getPossibleRelatedRecordPrimaryKeys() {
		$dbCommandBuilder = $this->getDbCommandBuilder();
		$tableSchema = $this->getRelatedTableSchema();
		$criteria = new CDbCriteria(array('select' => $tableSchema->primaryKey));
		$dbCommand = $dbCommandBuilder->createFindCommand($tableSchema, $criteria);
		$primaryKeys = $dbCommand->queryColumn();
		return $primaryKeys;
	}

	/**
	 * Returns the primary key values for currently related through the "many to many" records.
	 * @return array list of primary key values.
	 */
	protected function getCurrentRelatedRecordPrimaryKeys() {
		$owner = $this->getOwner();
		$primaryKeys = array();

		$relationName = $this->getRelationName();
		$relatedModels = $owner->$relationName;
		if (is_array($relatedModels)) {
			foreach ($relatedModels as $relatedModel) {
				$primaryKeys[] = $relatedModel->getPrimaryKey();
			}
		}
		return $primaryKeys;
	}

	// Events:

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onAfterSave' => 'afterSave',
		);
	}

	/**
	 * Responds to {@link CActiveRecord::onAfterSave} event.
	 * This method saves the "many to many" relation reference.
	 * @param CModelEvent $event event parameter.
	 */
	public function afterSave($event) {
		if (is_array($this->_relationAttributeValue)) {
			$owner = $this->getOwner();

			$givenRelatedRecordPrimaryKeys = $this->_relationAttributeValue;

			$dbCommandBuilder = $this->getDbCommandBuilder();

			// Extract relation data:
			$relation = $this->getRelation();
			$connectorTableName = $relation->getJunctionTableName();
			$foreignKeys = $relation->getJunctionForeignKeys();
			$ownerForeignKeyName = array_shift($foreignKeys);
			$relatedForeignKeyName = array_shift($foreignKeys);

			// All possible references:
			$possibleRelatedRecordPrimaryKeys = $this->getPossibleRelatedRecordPrimaryKeys();

			// Current references:
			$currentRelatedRecordPrimaryKeys = $this->getCurrentRelatedRecordPrimaryKeys();

			// Insert missing references:
			foreach ($givenRelatedRecordPrimaryKeys as $givenRelatedRecordPrimaryKey) {
				$possibleRelatedRecordPrimaryKeyPosition = array_search($givenRelatedRecordPrimaryKey, $possibleRelatedRecordPrimaryKeys);
				if ($possibleRelatedRecordPrimaryKeyPosition !== false) {
					if (!in_array($givenRelatedRecordPrimaryKey, $currentRelatedRecordPrimaryKeys)) {
						$data = array(
							$ownerForeignKeyName => $owner->getPrimaryKey(),
							$relatedForeignKeyName => $givenRelatedRecordPrimaryKey,
						);
						$insertCommand = $dbCommandBuilder->createInsertCommand($connectorTableName, $data);
						$insertCommand->execute();
					}
					unset($possibleRelatedRecordPrimaryKeys[$possibleRelatedRecordPrimaryKeyPosition]);
				}
			}

			// Delete extra references:
			$criteria = new CDbCriteria();
			$criteria->addColumnCondition(array($ownerForeignKeyName => $owner->getPrimaryKey()));
			$criteria->addInCondition($relatedForeignKeyName, $possibleRelatedRecordPrimaryKeys);
			$deleteCommand = $dbCommandBuilder->createDeleteCommand($connectorTableName, $criteria);
			$deleteCommand->execute();
		}
	}
}
