<?php
/**
 * QsActiveRecordBehaviorNestedSet class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows to organize database table records into the tree structure.
 * The tree structure is maintained using "Nested Set" data model.
 * The database table, which the model refers to, must contain fields {@link leftIndexAttributeName}, {@link rightIndexAttributeName} and {@link levelAttributeName}.
 * Example migration for such table:
 * <code>
 * $tableName = 'item_tree';
 * $columns = array(
 *     'id' => 'pk',
 *     'left_index' => 'integer',
 *     'right_index' => 'integer',
 *     'level' => 'integer',
 *     'name' => 'string',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * $this->createIndex("idx_{$tableName}_left_index", $tableName, 'left_index');
 * $this->createIndex("idx_{$tableName}_right_index", $tableName, 'right_index');
 * $this->createIndex("idx_{$tableName}_level", $tableName, 'level');
 * </code>
 * For the start the model table should contain root record, with data: leftIndexAttributeName = 1, rightIndexAttributeName=2, levelAttributeName=0.
 * Such record will be automatically created if {@link autoCreateRoot} is set to "true".
 * All further records should be inserted using this behavior.
 *
 * @see http://en.wikipedia.org/wiki/Nested_set_model
 *
 * @property string $leftIndexAttributeName public alias of {@link _leftIndexAttributeName}.
 * @property string $rightIndexAttributeName public alias of {@link _rightIndexAttributeName}.
 * @property string $levelAttributeName public alias of {@link _levelAttributeName}.
 * @property string $refParentPropertyName public alias of {@link _refParentPropertyName}.
 * @property mixed $refParent public alias of {@link _refParent}.
 * @property array $groupAttributes public alias of {@link _groupAttributes}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorNestedSet extends CBehavior {
	/**
	 * @var string name owner attribute, which will store tree left index value.
	 */
	protected $_leftIndexAttributeName = 'left_index';
	/**
	 * @var string name owner attribute, which will store tree right index value.
	 */
	protected $_rightIndexAttributeName = 'right_index';
	/**
	 * @var string name owner attribute, which will store tree level value.
	 */
	protected $_levelAttributeName = 'level';
	/**
	 * @var string name of the property, which will be considered as reference to the parent record
	 * while saving owner.
	 */
	protected $_refParentPropertyName = 'parent_id';
	/**
	 * @var mixed value of the primary key of the parent record.
	 */
	protected $_refParent = null;
	/**
	 * @var array list of owner attribute names, which values split records into the groups,
	 * which should behave like a separated trees.
	 * Example: <code>array('group_id','category_id')</code>
	 * Warning: if you using group attributes, you must ensure different trees will not been mixed up.
	 */
	protected $_groupAttributes = array();
	/**
	 * @var boolean indicates, if behavior will attempt to create tree root record, when it is missing.
	 */
	public $autoCreateRoot = true;

	// Set / Get:

	public function setLeftIndexAttributeName($leftIndexAttributeName) {
		if (!is_string($leftIndexAttributeName)) {
			return false;
		}
		$this->_leftIndexAttributeName = $leftIndexAttributeName;
		return true;
	}

	public function getLeftIndexAttributeName() {
		return $this->_leftIndexAttributeName;
	}

	public function setRightIndexAttributeName($rightIndexAttributeName) {
		if (!is_string($rightIndexAttributeName)) {
			return false;
		}
		$this->_rightIndexAttributeName = $rightIndexAttributeName;
		return true;
	}

	public function getRightIndexAttributeName() {
		return $this->_rightIndexAttributeName;
	}

	public function setLevelAttributeName($levelAttributeName) {
		if (!is_string($levelAttributeName)) {
			return false;
		}
		$this->_levelAttributeName = $levelAttributeName;
		return true;
	}

	public function getLevelAttributeName() {
		return $this->_levelAttributeName;
	}

	public function setRefParentPropertyName($refParentPropertyName) {
		if (!is_string($refParentPropertyName)) {
			return false;
		}
		$this->_refParentPropertyName = $refParentPropertyName;
		return true;
	}

	public function getRefParentPropertyName() {
		return $this->_refParentPropertyName;
	}

	public function setRefParent($refParent) {
		$this->_refParent = $refParent;
		return true;
	}

	public function getRefParent() {
		return $this->_refParent;
	}

	public function setGroupAttributes(array $groupAttributes) {
		$this->_groupAttributes = $groupAttributes;
		return true;
	}

	public function getGroupAttributes() {
		return $this->_groupAttributes;
	}

	// Property Access Extension:
	
	public function __set($name, $value) {
		try {
			parent::__set($name, $value);
		} catch (CException $exception) {
			if ($name == $this->getRefParentPropertyName()) {
				$this->setRefParent($value);
			} else {
				throw $exception;
			}
		}
	}

	public function __get($name) {
		try {
			parent::__get($name);
		} catch (CException $exception) {
			if ($name == $this->getRefParentPropertyName()) {
				return $this->getRefParent();
			} else {
				throw $exception;
			}
		}
	}

	public function canGetProperty($name) {
		$result = parent::canGetProperty($name);
		if (!$result) {
			return ($name == $this->getRefParentPropertyName());
		}
		return $result;
	}

	public function canSetProperty($name) {
		$result = parent::canSetProperty($name);
		if (!$result) {
			return ($name == $this->getRefParentPropertyName());
		}
		return $result;
	}

	/**
	 * Clears value of reference to the parent record.
	 * @see refParent
	 * @return boolean success.
	 */
	public function clearRefParent() {
		$this->_refParent = null;
		return true;
	}

	/**
	 * Returns set of group attribute values.
	 * @param boolean $addTableAlias indicates if columns should be aliased to be suitable for the SQL query.
	 * @return array set of group attribute values.
	 */
	protected function getGroupAttributeValues($addTableAlias = false) {
		$groupAttributeValues = array();
		$groupAttributes = $this->getGroupAttributes();
		if (!empty($groupAttributes)) {
			$owner = $this->getOwner();
			$prefix = ($addTableAlias) ? $owner->getTableAlias(true) . '.' : '';
			foreach ($groupAttributes as $groupAttributeName) {
				$attributeValue = $owner->getAttribute($groupAttributeName);
				if ($attributeValue!==null) {
					$groupAttributeValues[$prefix . $groupAttributeName] = $attributeValue;
				}
			}
		}
		return $groupAttributeValues;
	}

	/**
	 * Creates query criteria using {@link CDbCriteria::compare()},
	 * so it can apply comparison operators: =, >, <, etc.
	 * @param boolean $addTableAlias indicates if columns should be aliased to be suitable for the SQL query.
	 * @return CDbCriteria adjusted db criteria.
	 */
	protected function createAttributeCriteria($addTableAlias = true) {
		$criteria = new CDbCriteria();
		$groupAttributeValues = $this->getGroupAttributeValues($addTableAlias);
		if (!empty($groupAttributeValues)) {
			$criteria->addColumnCondition($groupAttributeValues);
		}
		return $criteria;
	}

	/**
	 * Creates and applies query criteria using {@link CDbCriteria::compare()},
	 * so it can apply comparison operators: =, >, <, etc.
	 * @param array $attributes map of attribute compare values.
	 * @return CActiveRecord owner active record.
	 */
	protected function applyAttributeCriteria(array $attributes) {
		$owner = $this->getOwner();
		$criteria = $owner->getDbCriteria();

		$groupAttributeValues = $this->getGroupAttributeValues(true);
		if (!empty($groupAttributeValues)) {
			$criteria->addColumnCondition($groupAttributeValues);
		}

		$prefix = $owner->getTableAlias(true) . '.';
		foreach ($attributes as $attributeName => $attributeValue) {
			$criteria->compare($prefix . $attributeName, $attributeValue);
		}
		return $owner;
	}

	/**
	 * Finds parent record referenced by {@link refParent}.
	 * If no parent record is referenced the root record will be found.
	 * @throws CException on failure.
	 * @return CActiveRecord - parent record object.
	 */
	public function fetchParentRecordByRef() {
		$owner = $this->getOwner();

		$refParent = $this->getRefParent();
		if (empty($refParent)) {
			$rootRecord = $this->root()->find();
			if (!is_object($rootRecord)) {
				if ($this->autoCreateRoot) {
					$this->createRootRecord();
				} else {
					throw new CException('Unable to find tree root for active record "' . get_class($owner) . '"!');
				}
				$rootRecord = $this->root()->find();
				if (!is_object($rootRecord)) {
					throw new CException('Unable to create tree root for active record "' . get_class($owner) . '"!');
				}

			}
			$refParent = $rootRecord->getPrimaryKey();
			$this->setRefParent($refParent);
			return $rootRecord;
		} else {
			$parentRecord = $owner->findByPk($refParent);
			if (!is_object($parentRecord)) {
				throw new CException('Unable to find tree parent for active record "' . get_class($owner) . '"!');
			}
			return $parentRecord;
		}
	}

	/**
	 * Attempts to create tree root record.
	 * @throws CException if root creation fails.
	 * @return boolean success.
	 */
	protected function createRootRecord() {
		$owner = $this->getOwner();

		$attributes = array(
			$this->getLeftIndexAttributeName() => '1',
			$this->getRightIndexAttributeName() => '2',
			$this->getLevelAttributeName() => '0',
		);
		foreach ($this->getGroupAttributes() as $groupAttributeName) {
			$attributes[$groupAttributeName] = $owner->$groupAttributeName;
		}

		$builder = $owner->getCommandBuilder();
		$table = $owner->getMetaData()->tableSchema;
		$command = $builder->createInsertCommand($table,$attributes);
		$affectedRowsCount = $command->execute();
		if ($affectedRowsCount<1) {
			throw new CException('Unable to create tree root for active record "' . get_class($owner) . '"!');
		}
		return true;
	}

	/**
	 * Checks if the record is leaf (has no child).
	 * @return boolean record is leaf.
	 */
	public function isLeaf() {
		$owner = $this->getOwner();
		return ( ( $owner->getAttribute($this->getRightIndexAttributeName()) - $owner->getAttribute($this->getLeftIndexAttributeName()) ) <= 1 );
	}

	/**
	 * Refreshed tree attribute values for the record
	 * from the database.
	 * @return boolean success.
	 */
	public function refreshTreeAttributes() {
		$owner = $this->getOwner();
		if ($owner->getIsNewRecord()) {
			return false;
		}
		$refreshedOwner = $owner->findByPk($owner->getPrimaryKey());

		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$owner->setAttribute($leftIndexAttributeName, $refreshedOwner->getAttribute($leftIndexAttributeName));
		$owner->setAttribute($rightIndexAttributeName, $refreshedOwner->getAttribute($rightIndexAttributeName));
		$owner->setAttribute($levelAttributeName, $refreshedOwner->getAttribute($levelAttributeName));

		return true;
	}

	/**
	 * Applies "root" condition to the search query criteria.
	 * This scope will return the root of the tree.
	 * Example:
	 * <code>$root = Tree::model()->root()->find();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function root() {
		$levelAttributeName = $this->getLevelAttributeName();
		$attributes = array(
			$levelAttributeName => '0'
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "not root" condition to the search query criteria.
	 * This scope will return the all records except the tree root.
	 * Example:
	 * <code>$significantRecords = Tree::model()->notRoot()->findAll();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function notRoot() {
		$levelAttributeName = $this->getLevelAttributeName();
		$attributes = array(
			$levelAttributeName => '>0'
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "parent axis" condition to the search query criteria.
	 * This scope will return the parent record for the owner.
	 * Example:
	 * <code>$parent = $record->parent()->find();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function parent() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$attributes = array(
			$leftIndexAttributeName => '<' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '>' . $owner->getAttribute($rightIndexAttributeName),
			$levelAttributeName => '' . $owner->getAttribute($levelAttributeName) - 1
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "ancestor axis" condition to the search query criteria.
	 * This scope will return the all ancestor records for the owner.
	 * Example:
	 * <code>$ancestors = $record->ancestor()->findAll();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function ancestor() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$attributes = array(
			$leftIndexAttributeName => '<' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '>' . $owner->getAttribute($rightIndexAttributeName)
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "child axis" condition to the search query criteria.
	 * This scope will return the child record for the owner.
	 * Example:
	 * <code>$child = $record->child()->find();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function child() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$attributes = array(
			$leftIndexAttributeName => '>' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '<' . $owner->getAttribute($rightIndexAttributeName),
			$levelAttributeName => '' . $owner->getAttribute($levelAttributeName) + 1
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "descendant axis" condition to the search query criteria.
	 * This scope will return the all descendant records for the owner.
	 * Example:
	 * <code>$descendants = $record->descendant()->findAll();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function descendant() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();

		$attributes = array(
			$leftIndexAttributeName => '>' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '<' . $owner->getAttribute($rightIndexAttributeName)
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "ancestor-or-self axis" condition to the search query criteria.
	 * This scope will return the all ancestor records for the owner, along with the owner record.
	 * Example:
	 * <code>$ancestorsAndSelf = $record->ancestorOrSelf()->findAll();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function ancestorOrSelf() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();

		$attributes = array(
			$leftIndexAttributeName => '<=' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '>=' . $owner->getAttribute($rightIndexAttributeName)
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Applies "descendant-or-self axis" condition to the search query criteria.
	 * This scope will return the all descendant records for the owner, along with the owner record.
	 * Example:
	 * <code>$descendantsAndSelf = $record->descendantOrSelf()->findAll();</code>
	 * @return CActiveRecord owner active record.
	 */
	public function descendantOrSelf() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();

		$attributes = array(
			$leftIndexAttributeName => '>=' . $owner->getAttribute($leftIndexAttributeName),
			$rightIndexAttributeName => '<=' . $owner->getAttribute($rightIndexAttributeName)
		);
		return $this->applyAttributeCriteria($attributes);
	}

	/**
	 * Moves owner record by one position towards the start of the list of its brothers.
	 * @return boolean movement successful.
	 */
	public function movePrev() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$parentRecord = $owner->parent()->find();
		if (empty($parentRecord)) {
			return false;
		}

		$prefix = $owner->getTableAlias(true) . '.';

		$criteria = $this->createAttributeCriteria();
		$criteria->compare($prefix.$leftIndexAttributeName, '<' . $owner->getAttribute($leftIndexAttributeName));
		$criteria->compare($prefix.$leftIndexAttributeName, '>' . $parentRecord->getAttribute($leftIndexAttributeName));
		$criteria->compare($prefix.$levelAttributeName, $owner->getAttribute($levelAttributeName));
		$criteria->order = "{$leftIndexAttributeName} DESC";

		$prevRecord = $owner->find($criteria);
		if (!is_object($prevRecord)) {
			return false;
		}

		$updateRecords = $owner->descendantOrSelf()->findAll();
		$prevUpdateRecords = $prevRecord->descendantOrSelf()->findAll();

		$indexOffset = $prevRecord->getAttribute($rightIndexAttributeName) - $prevRecord->getAttribute($leftIndexAttributeName) + 1;
		foreach ($updateRecords as $updateRecord) {
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}-{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}-{$indexOffset}"),
			);
			$updateRecord->updateByPk($updateRecord->getPrimaryKey(), $attributes);
		}

		$indexOffset = $owner->getAttribute($rightIndexAttributeName) - $owner->getAttribute($leftIndexAttributeName) + 1;
		foreach ($prevUpdateRecords as $prevUpdateRecord) {
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}+{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+{$indexOffset}"),
			);
			$prevUpdateRecord->updateByPk($prevUpdateRecord->getPrimaryKey(), $attributes);
		}

		return $this->refreshTreeAttributes();
	}

	/**
	 * Moves owner record by one position towards the end of the list of its brothers.
	 * @return boolean movement successful.
	 */
	public function moveNext() {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$parentRecord = $owner->parent()->find();
		if (empty($parentRecord)) {
			return false;
		}

		$prefix = $owner->getTableAlias(true) . '.';

		$criteria = $this->createAttributeCriteria();
		$criteria->compare($prefix.$leftIndexAttributeName, '>'.$owner->getAttribute($leftIndexAttributeName));
		$criteria->compare($prefix.$rightIndexAttributeName, '<'.$parentRecord->getAttribute($rightIndexAttributeName));
		$criteria->compare($prefix.$levelAttributeName, $owner->getAttribute($levelAttributeName));
		$criteria->order = "{$leftIndexAttributeName} ASC";

		$nextRecord = $owner->find($criteria);
		if (!is_object($nextRecord)) {
			return false;
		}

		$updateRecords = $owner->descendantOrSelf()->findAll();
		$nextUpdateRecords = $nextRecord->descendantOrSelf()->findAll();

		$indexOffset = $nextRecord->getAttribute($rightIndexAttributeName) - $nextRecord->getAttribute($leftIndexAttributeName) + 1;
		foreach ($updateRecords as $updateRecord) {
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}+{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+{$indexOffset}"),
			);
			$updateRecord->updateByPk($updateRecord->getPrimaryKey(), $attributes);
		}

		$indexOffset = $owner->getAttribute($rightIndexAttributeName) - $owner->getAttribute($leftIndexAttributeName) + 1;
		foreach ($nextUpdateRecords as $nextUpdateRecord) {
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}-{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}-{$indexOffset}"),
			);
			$nextUpdateRecord->updateByPk($nextUpdateRecord->getPrimaryKey(), $attributes);
		}

		return $this->refreshTreeAttributes();
	}

	/**
	 * Moves owner record to the start of the list of its brothers.
	 * @return boolean movement successful.
	 */
	public function moveFirst() {
		$result = false;
		while ($this->movePrev()) {
			$result = true;
		}
		return $result;
	}

	/**
	 * Moves owner record to the end of the list of its brothers.
	 * @return boolean movement successful.
	 */
	public function moveLast() {
		$result = false;
		while ($this->moveNext()) {
			$result = true;
		}
		return $result;
	}

	/**
	 * Resets tree indexes, making all records to be direct children of the single root.
	 * Use this method for emergency, if the tree structure has been corrupted.
	 * @return boolean success.
	 */
	public function resetTree() {
		$owner = $this->getOwner();

		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		$builder = $owner->getCommandBuilder();
		$table = $owner->getMetaData()->tableSchema;

		$criteria = $this->createAttributeCriteria(false);
		$criteria->order = $levelAttributeName . ' ASC';
		$command = $builder->createFindCommand($table, $criteria);
		$rows = $command->queryAll();

		if (empty($rows)) {
			return false;
		}
		$rowsCount = count($rows);
		$root = array_shift($rows);

		$data = array(
			$leftIndexAttributeName => '1',
			$rightIndexAttributeName => $rowsCount * 2,
			$levelAttributeName => '0',
		);
		$owner->updateByPk($root[$table->primaryKey], $data);

		$currentLeftIndex = 2;
		foreach ($rows as $row) {
			$data = array(
				$leftIndexAttributeName => $currentLeftIndex,
				$rightIndexAttributeName => $currentLeftIndex + 1,
				$levelAttributeName => '1',
			);
			$owner->updateByPk($row[$table->primaryKey], $data);
			$currentLeftIndex += 2;
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
			'onBeforeSave' => 'beforeSave',
			'onBeforeDelete' => 'beforeDelete',
		);
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeSave} event.
	 * Method applies tree attribute values depending of parent record reference.
	 * @param CModelEvent $event event parameter
	 */
	public function beforeSave($event) {
		$owner = $this->getOwner();
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();
		$levelAttributeName = $this->getLevelAttributeName();

		if ($owner->getIsNewRecord()) {
			// Insert:
			$parentRecord = $this->fetchParentRecordByRef();

			$recordLevel = $parentRecord->getAttribute($levelAttributeName) + 1;
			$recordLeftIndex = $parentRecord->getAttribute($rightIndexAttributeName);
			$recordRightIndex = $parentRecord->getAttribute($rightIndexAttributeName) + 1;

			$owner->setAttribute($levelAttributeName, $recordLevel);
			$owner->setAttribute($leftIndexAttributeName, $recordLeftIndex);
			$owner->setAttribute($rightIndexAttributeName, $recordRightIndex);

			$criteria = $this->createAttributeCriteria(false);
			$criteria->compare($leftIndexAttributeName, '>' . $recordLeftIndex);

			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}+2")
			);
			$owner->updateAll($attributes, $criteria);

			$criteria = $this->createAttributeCriteria(false);
			$criteria->compare($rightIndexAttributeName, '>=' . $recordLeftIndex);
			$attributes = array(
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+2")
			);
			$owner->updateAll($attributes, $criteria);
			return true;
		} else {
			// Update:
			$oldRecord = $owner->findByPk($owner->getPrimaryKey());
			$oldParentRecord = $oldRecord->parent()->find();
			$newParentRecord = $this->fetchParentRecordByRef();
			if ($oldParentRecord->getPrimaryKey() == $newParentRecord->getPrimaryKey()) {
				return true;
			}

			$oldRecordIndexDelta = $oldRecord->getAttribute($rightIndexAttributeName) - $oldRecord->getAttribute($leftIndexAttributeName);

			// Prepare target node for insertion, make room:
			$indexOffset = $oldRecordIndexDelta+1;
			$criteria = $this->createAttributeCriteria(false);
			$condition = "{$leftIndexAttributeName}>:{$leftIndexAttributeName} AND {$rightIndexAttributeName}>:{$rightIndexAttributeName}";
			$criteria->addCondition($condition);
			$criteria->params[$leftIndexAttributeName] = $newParentRecord->getAttribute($leftIndexAttributeName);
			$criteria->params[$rightIndexAttributeName] = $newParentRecord->getAttribute($rightIndexAttributeName);
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}+{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+{$indexOffset}"),
			);
			$oldRecord->updateAll($attributes, $criteria);

			$criteria = $this->createAttributeCriteria(false);
			$condition = "{$leftIndexAttributeName}<=:{$leftIndexAttributeName} AND {$rightIndexAttributeName}>=:{$rightIndexAttributeName}";
			$criteria->addCondition($condition);
			$criteria->params[$leftIndexAttributeName] = $newParentRecord->getAttribute($leftIndexAttributeName);
			$criteria->params[$rightIndexAttributeName] = $newParentRecord->getAttribute($rightIndexAttributeName);
			$attributes = array(
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+{$indexOffset}"),
			);
			$oldRecord->updateAll($attributes, $criteria);

			// Insert record into the new space:
			$oldRecord->refresh();
			$indexOffset = $newParentRecord->getAttribute($rightIndexAttributeName) - $oldRecord->getAttribute($leftIndexAttributeName);
			$levelOffset = $newParentRecord->getAttribute($levelAttributeName) + 1 - $oldRecord->getAttribute($levelAttributeName);
			$criteria = $this->createAttributeCriteria(false);
			$condition = "{$leftIndexAttributeName}>=:{$leftIndexAttributeName} AND {$rightIndexAttributeName}<=:{$rightIndexAttributeName}";
			$criteria->addCondition($condition);
			$criteria->params[$leftIndexAttributeName] = $oldRecord->getAttribute($leftIndexAttributeName);
			$criteria->params[$rightIndexAttributeName] = $oldRecord->getAttribute($rightIndexAttributeName);
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}+{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}+{$indexOffset}"),
				$levelAttributeName => new CDbExpression("{$levelAttributeName}+{$levelOffset}"),
			);
			$oldRecord->updateAll($attributes, $criteria);

			// Close gap in the tree
			$indexOffset = $oldRecordIndexDelta+1;
			$criteria = $this->createAttributeCriteria(false);
			$condition = "{$leftIndexAttributeName}>:{$leftIndexAttributeName} AND {$rightIndexAttributeName}>:{$rightIndexAttributeName}";
			$criteria->addCondition($condition);
			$criteria->params[$leftIndexAttributeName] = $oldRecord->getAttribute($leftIndexAttributeName);
			$criteria->params[$rightIndexAttributeName] = $oldRecord->getAttribute($rightIndexAttributeName);
			$attributes = array(
				$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}-{$indexOffset}"),
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}-{$indexOffset}"),
			);
			$oldRecord->updateAll($attributes, $criteria);

			$criteria = $this->createAttributeCriteria(false);
			$condition = "{$leftIndexAttributeName}<:{$leftIndexAttributeName} AND {$rightIndexAttributeName}>:{$rightIndexAttributeName}";
			$criteria->addCondition($condition);
			$criteria->params[$leftIndexAttributeName] = $oldRecord->getAttribute($leftIndexAttributeName);
			$criteria->params[$rightIndexAttributeName] = $oldRecord->getAttribute($rightIndexAttributeName);
			$attributes = array(
				$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}-{$indexOffset}"),
			);
			$oldRecord->updateAll($attributes, $criteria);

			// Ensure save correct tree parameters for the owner:
			$oldRecord->refresh();
			$owner->setAttribute($levelAttributeName, $oldRecord->getAttribute($levelAttributeName));
			$owner->setAttribute($leftIndexAttributeName, $oldRecord->getAttribute($leftIndexAttributeName));
			$owner->setAttribute($rightIndexAttributeName, $oldRecord->getAttribute($rightIndexAttributeName));
			return true;
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeDelete} event.
	 * Method deletes all descendant records of the owner and makes sure tree indexes will not break.
	 * @param CEvent $event event parameter
	 */
	public function beforeDelete($event) {
		$owner = $this->getOwner();
		$oldRecord = $owner->findByPk($owner->getPrimaryKey());

		// Delete all descendants:
		while (!$oldRecord->isLeaf()) {
			$childRecords = $oldRecord->child()->findAll();
			if (!empty($childRecords)) {
				foreach ($childRecords as $childRecord) {
					$childRecord->delete();
				}
			}
			$oldRecord->refresh();
		}

		// Update indexes:
		$leftIndexAttributeName = $this->getLeftIndexAttributeName();
		$rightIndexAttributeName = $this->getRightIndexAttributeName();

		$criteria = $this->createAttributeCriteria(false);
		$condition = "{$leftIndexAttributeName}>:{$leftIndexAttributeName}";
		$criteria->addCondition($condition);
		$criteria->params[$leftIndexAttributeName] = $oldRecord->getAttribute($leftIndexAttributeName);
		$attributes = array(
			$leftIndexAttributeName => new CDbExpression("{$leftIndexAttributeName}-2")
		);
		$oldRecord->updateAll($attributes, $criteria);

		$criteria = $this->createAttributeCriteria(false);
		$condition = "{$rightIndexAttributeName}>:{$rightIndexAttributeName}";
		$criteria->addCondition($condition);
		$criteria->params[$rightIndexAttributeName] = $oldRecord->getAttribute($rightIndexAttributeName);
		$attributes = array(
			$rightIndexAttributeName => new CDbExpression("{$rightIndexAttributeName}-2")
		);
		$oldRecord->updateAll($attributes, $criteria);
	}
}