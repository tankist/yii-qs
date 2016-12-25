<?php
/**
 * QsActiveRecordBehaviorPosition class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CActiveRecord}, which allows to manage custom order for the records in the model database table.
 * Behavior uses the specific integer field of the database table to set up position index.
 * Due to this the database table, which the model refers to, must contain field {@link positionAttributeName}.
 *
 * @property string $positionAttributeName public alias of {@link _positionAttributeName}.
 * @property array $groupAttributes public alias of {@link _groupAttributes}.
 * @property boolean $defaultOrdering public alias of {@link _defaultOrdering}.
 * @method CActiveRecord getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorPosition extends CBehavior {
	/**
	 * @var string name owner attribute, which will store position value.
	 * This attribute should be an integer.
	 */
	protected $_positionAttributeName = 'position';
	/**
	 * @var array list of owner attribute names, which values split records into the groups,
	 * which should have their own positioning.
	 * Example: <code>array('group_id', 'category_id')</code>
	 */
	protected $_groupAttributes = array();
	/**
	 * @var boolean determines if default order (position ASC) should
	 * be applied automatically on the search.
	 */
	protected $_defaultOrdering = false;
	/**
	 * @var integer position value, which should be applied to the model on its save.
	 * Internal usage only.
	 */
	protected $_positionOnSave = 0;

	// Set / Get:

	public function setPositionAttributeName($positionAttributeName) {
		if (!is_string($positionAttributeName)) {
			return false;
		}
		$this->_positionAttributeName = $positionAttributeName;
		return true;
	}

	public function getPositionAttributeName() {
		return $this->_positionAttributeName;
	}

	public function setGroupAttributes(array $groupAttributes) {
		$this->_groupAttributes = $groupAttributes;
		return true;
	}

	public function getGroupAttributes() {
		return $this->_groupAttributes;
	}

	public function setDefaultOrdering($defaultOrdering) {
		$this->_defaultOrdering = $defaultOrdering;
		return true;
	}

	public function getDefaultOrdering() {
		return $this->_defaultOrdering;
	}

	public function setPositionOnSave($positionOnSave) {
		if (!is_numeric($positionOnSave)) {
			return false;
		}
		$this->_positionOnSave = $positionOnSave;
		return true;
	}

	public function getPositionOnSave() {
		return $this->_positionOnSave;
	}

	/**
	 * Creates array of group attributes with their values.
	 * @see groupAttributes
	 * @return array attribute conditions.
	 */
	protected function createGroupConditionAttributes() {
		$attributesCondition = array();
		if (!empty($this->_groupAttributes)) {
			$owner = $this->getOwner();
			foreach ($this->_groupAttributes as $groupAttributeName) {
				$attributesCondition[$groupAttributeName] = $owner->getAttribute($groupAttributeName);
			}
		}
		return $attributesCondition;
	}

	/**
	 * Creates search criteria, which applies group attribute values.
	 * @see groupAttributes
	 * @param string $condition basic query condition.
	 * @param array $params query params.
	 * @return CDbCriteria db criteria.
	 */
	protected function createGroupConditionCriteria($condition = '', $params = array()) {
		$owner = $this->getOwner();
		$builder = $owner->getCommandBuilder();
		//$prefix = $owner->getTableAlias(true).'.';
		$attributes = $this->createGroupConditionAttributes();
		$criteria = $builder->createColumnCriteria($owner->getTableSchema(), $attributes, $condition, $params);
		return $criteria;
	}

	/**
	 * Finds the number of records which belongs to the group of the owner.
	 * @see groupAttributes
	 * @return integer records count.
	 */
	protected function countGroupRecords() {
		$owner = $this->getOwner();
		$attributes = $this->createGroupConditionAttributes();
		if (!empty($attributes)) {
			$recordsCount = $owner->countByAttributes($attributes);
		} else {
			$recordsCount = $owner->count();
		}
		return $recordsCount;
	}

	/**
	 * Creates default order value if {@link defaultOrdering} is enabled.
	 * @return string default order value.
	 */
	public function createDefaultOrder() {
		if ($this->getDefaultOrdering()) {
			$owner = $this->getOwner();
			$order = $owner->getTableAlias(true) . '.' . $this->getPositionAttributeName() . ' ASC';
			return $order;
		} else {
			return null;
		}
	}

	/**
	 * Moves owner record by one position towards the start of the list.
	 * @return boolean movement successful.
	 */
	public function movePrev() {
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();
		$findAttributes = $this->createGroupConditionAttributes();

		$findAttributes[$positionAttributeName] = $owner->getAttribute($positionAttributeName) - 1;
		$previousRecord = $owner->findByAttributes($findAttributes);
		if (empty($previousRecord)) {
			return false;
		}

		$attributes = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName)
		);
		$previousRecord->updateByPk($previousRecord->getPrimaryKey(), $attributes);

		$attributes = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName) - 1
		);
		$owner->updateByPk($owner->getPrimaryKey(), $attributes);

		$owner->setAttribute($positionAttributeName, $owner->getAttribute($positionAttributeName) - 1);
		return true;
	}

	/**
	 * Moves owner record by one position towards the end of the list.
	 * @return boolean movement successful.
	 */
	public function moveNext() {
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();
		$findAttributes = $this->createGroupConditionAttributes();

		$findAttributes[$positionAttributeName] = $owner->getAttribute($positionAttributeName) + 1;
		$nextRecord = $owner->findByAttributes($findAttributes);
		if (empty($nextRecord)) {
			return false;
		}

		$attributes = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName)
		);
		$nextRecord->updateByPk($nextRecord->getPrimaryKey(), $attributes);

		$attributes = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName) + 1
		);
		$owner->updateByPk($owner->getPrimaryKey(), $attributes);

		$owner->setAttribute($positionAttributeName, $owner->getAttribute($positionAttributeName) + 1);
		return true;
	}

	/**
	 * Moves owner record to the start of the list.
	 * @return boolean movement successful.
	 */
	public function moveFirst() {
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();
		if ($owner->getAttribute($positionAttributeName) == 1) {
			return false;
		}

		$attributes = array(
			$positionAttributeName => new CDbExpression("{$positionAttributeName}+1")
		);
		$condition = "{$positionAttributeName} < :{$positionAttributeName}";
		$params = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName)
		);
		$criteria = $this->createGroupConditionCriteria($condition, $params);
		$owner->updateAll($attributes, $criteria);

		$attributes = array(
			$positionAttributeName => 1
		);
		$owner->updateByPk($owner->getPrimaryKey(), $attributes);

		$owner->setAttribute($positionAttributeName, 1);
		return true;
	}

	/**
	 * Moves owner record to the end of the list.
	 * @return boolean movement successful.
	 */
	public function moveLast() {
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();

		$recordsCount = $this->countGroupRecords();
		if ($owner->getAttribute($positionAttributeName) == $recordsCount) {
			return false;
		}

		$attributes = array(
			$positionAttributeName => new CDbExpression("{$positionAttributeName}-1")
		);
		$condition = "{$positionAttributeName} > :{$positionAttributeName}";
		$params = array(
			$positionAttributeName => $owner->getAttribute($positionAttributeName)
		);
		$criteria = $this->createGroupConditionCriteria($condition, $params);
		$owner->updateAll($attributes, $criteria);

		$attributes = array(
			$positionAttributeName => $recordsCount
		);
		$owner->updateByPk($owner->getPrimaryKey(), $attributes);

		$owner->setAttribute($positionAttributeName, $recordsCount);
		return true;
	}

	/**
	 * Moves owner record to the specific position.
	 * If specified position exceeds the total number of records,
	 * owner will be moved to the end of the list.
	 * @param integer $position number of the new position.
	 * @return boolean movement successful.
	 */
	public function moveToPosition($position) {
		if (!is_numeric($position) || $position < 1) {
			return false;
		}
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();

		$oldRecord = $owner->findByPk($owner->getPrimaryKey());

		$oldRecordPosition = $oldRecord->getAttribute($positionAttributeName);

		if ($oldRecordPosition==$position) {
			return true;
		}

		if ($position < $oldRecordPosition) {
			// Move Up:
			$attributes = array(
				$positionAttributeName => new CDbExpression("{$positionAttributeName}+1")
			);
			$condition = "( {$positionAttributeName} >= :{$positionAttributeName}_from AND {$positionAttributeName} < :{$positionAttributeName}_to )";
			$params = array(
				"{$positionAttributeName}_from" => $position,
				"{$positionAttributeName}_to" => $oldRecord->getAttribute($positionAttributeName)
			);
			$criteria = $this->createGroupConditionCriteria($condition, $params);
			$owner->updateAll($attributes, $criteria);

			$attributes = array(
				$positionAttributeName => $position
			);
			$owner->updateByPk($owner->getPrimaryKey(),$attributes);

			$owner->setAttribute($positionAttributeName, $position);
			return true;
		} else {
			// Move Down:
			$recordsCount = $this->countGroupRecords();
			if ($position >= $recordsCount) {
				return $this->moveLast();
			}

			$attributes = array(
				$positionAttributeName => new CDbExpression("{$positionAttributeName}-1")
			);
			$condition = "( {$positionAttributeName} > :{$positionAttributeName}_from AND {$positionAttributeName} <= :{$positionAttributeName}_to )";
			$params = array(
				"{$positionAttributeName}_from" => $oldRecord->getAttribute($positionAttributeName),
				"{$positionAttributeName}_to" => $position
			);
			$criteria = $this->createGroupConditionCriteria($condition, $params);
			$owner->updateAll($attributes, $criteria);

			$attributes = array(
				$positionAttributeName => $position
			);
			$owner->updateByPk($owner->getPrimaryKey(), $attributes);

			$owner->setAttribute($positionAttributeName, $position);
			return true;
		}
	}

	// Events:

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onAfterConstruct' => 'afterConstruct',
			'onBeforeSave' => 'beforeSave',
			'onAfterSave' => 'afterSave',
			'onBeforeDelete' => 'beforeDelete',
			'onBeforeFind' => 'beforeFind',
		);
	}

	/**
	 * This event raises after owner creation.
	 * It clears the position attribute value.
	 * @param CEvent $event event object.
	 */
	public function afterConstruct($event) {
		$owner = $this->getOwner();
		$owner->setAttribute($this->_positionAttributeName, 0);
	}

	/**
	 * This event raises before owner save.
	 * It checks if the position or the group of the owner has been changed.
	 * If such changes have took place positioning will be update automatically.
	 * @param CEvent $event event object.
	 */
	public function beforeSave($event) {
		$this->setPositionOnSave(0);
		$owner = $this->getOwner();
		$positionAttributeName = $this->getPositionAttributeName();

		if ($owner->getIsNewRecord()) {
			if ($owner->getAttribute($positionAttributeName) > 0) {
				$this->setPositionOnSave($owner->getAttribute($positionAttributeName));
			}
			$owner->setAttribute($positionAttributeName, $this->countGroupRecords() + 1);
		} else {
			$oldRecord = $owner->findByPk($owner->getPrimaryKey());
			$groupAttributes = $this->getGroupAttributes();
			$isNewGroup = false;

			if (!empty($groupAttributes)) {
				foreach ($groupAttributes as $groupAttribute) {
					if ($owner->getAttribute($groupAttribute) != $oldRecord->getAttribute($groupAttribute)) {
						$isNewGroup = true;
						break;
					}
				}
			}
			if ($isNewGroup) {
				$oldRecord->moveLast();
				$this->setPositionOnSave($owner->getAttribute($positionAttributeName));
				$owner->setAttribute($positionAttributeName, $this->countGroupRecords() + 1);
			} else {
				if ($owner->getAttribute($positionAttributeName) != $oldRecord->getAttribute($positionAttributeName)) {
					$this->setPositionOnSave($owner->getAttribute($positionAttributeName));
					$owner->setAttribute($positionAttributeName, $oldRecord->getAttribute($positionAttributeName));
				}
			}
		}
	}

	/**
	 * This event raises after owner saved.
	 * It applies previosly set {@link positionOnSave}.
	 * This event supports other functionality.
	 * @param CEvent $event event object.
	 */
	public function afterSave($event) {
		$positionOnSave = $this->getPositionOnSave();
		if ($positionOnSave>0) {
			$this->moveToPosition($positionOnSave);
		}
		$this->setPositionOnSave(0);
	}

	/**
	 * This event raises before owner deleted.
	 * It move the record to the end of the list, so its removal
	 * will not break the entire list.
	 * @param CEvent $event event object.
	 */
	public function beforeDelete($event) {
		$this->moveLast();
	}

	/**
	 * This event raises before any find method of the owner.
	 * It applies default order to search criteria if
	 * {@link defaultOrdering} is enabled.
	 * @param CEvent $event event object.
	 */
	public function beforeFind($event) {
		if (!$this->getDefaultOrdering()) {
			return;
		}
		$criteria = $event->sender->getDbCriteria(true);
		if (empty($criteria->order)) {
			$criteria->order = $this->createDefaultOrder();
		}
	}
}