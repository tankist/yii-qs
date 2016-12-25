<?php
 
/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorNestedSet".
 * This test checks the scope of using group attributes to create several trees in the same table.
 * @see QsActiveRecordBehaviorNestedSet
 * @see QsActiveRecordBehaviorNestedSetTest
 */
class QsActiveRecordBehaviorNestedSetGroupAttributeTest extends CTestCase {
	const TEST_GROUPS_COUNT = 3;

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'group_id' => 'integer',
			'left_index' => 'integer',
			'right_index' => 'integer',
			'level' => 'integer',
		);
		$dbSetUp->createTable( $testTableName , $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'behaviors' => array(
					'treeBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorNestedSet',
						'groupAttributes' => array(
							'group_id'
						),
					),
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());

		// garbage collection:
		if (function_exists('gc_enabled')) {
			if (gc_enabled()) {
				gc_collect_cycles();
			} else {
				gc_enable();
				gc_collect_cycles();
				gc_disable();
			}
		}
	}

	public function setUp() {
		$dbSetUp = new QsTestDbMigration();
		$treeTableName = self::getTestTableName();
		$dbSetUp->truncateTable($treeTableName);

		for ($groupId=1; $groupId<=self::TEST_GROUPS_COUNT; $groupId++) {
			$columns = array(
				'name' => 'root',
				'group_id' => $groupId,
				'left_index' => '1',
				'right_index' => '16',
				'level' => '0',
			);
			$dbSetUp->insert($treeTableName, $columns);

			$columns = array(
				'name' => 'node_1',
				'group_id' => $groupId,
				'left_index' => '2',
				'right_index' => '7',
				'level' => '1',
			);
			$dbSetUp->insert($treeTableName, $columns);
			$columns = array(
				'name' => 'node_1.1',
				'group_id' => $groupId,
				'left_index' => '3',
				'right_index' => '4',
				'level' => '2',
			);
			$dbSetUp->insert($treeTableName, $columns);
			$columns = array(
				'name' => 'node_1.2',
				'group_id' => $groupId,
				'left_index' => '5',
				'right_index' => '6',
				'level' => '2',
			);
			$dbSetUp->insert($treeTableName, $columns);

			$columns = array(
				'name' => 'node_2',
				'group_id' => $groupId,
				'left_index' => '8',
				'right_index' => '13',
				'level' => '1',
			);
			$dbSetUp->insert($treeTableName, $columns);
			$columns = array(
				'name' => 'node_2.1',
				'group_id' => $groupId,
				'left_index' => '9',
				'right_index' => '10',
				'level' => '2',
			);
			$dbSetUp->insert($treeTableName, $columns);
			$columns = array(
				'name' => 'node_2.2',
				'group_id' => $groupId,
				'left_index' => '11',
				'right_index' => '12',
				'level' => '2',
			);
			$dbSetUp->insert($treeTableName, $columns);
			$columns = array(
				'name' => 'node_3',
				'group_id' => $groupId,
				'left_index' => '14',
				'right_index' => '15',
				'level' => '1',
			);
			$dbSetUp->insert($treeTableName, $columns);
		}
	}

	/**
	 * Returns the name of the test table.
	 * @return string test table name.
	 */
	public static function getTestTableName() {
		return 'test_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestActiveRecordClassName() {
		return self::getTestTableName();
	}

	/**
	 * Returns new test active record finder.
	 * @return CActiveRecord test active record finder.
	 */
	protected function getTestActiveRecordFinder() {
		$className = self::getTestActiveRecordClassName();
		$activeRecord = new $className();
		return $activeRecord;
	}

	/**
	 * Creates new test active record instance.
	 * @return CActiveRecord new test active record instance.
	 */
	protected function newTestActiveRecord() {
		$className = self::getTestActiveRecordClassName();
		$activeRecord = new $className();
		return $activeRecord;
	}

	/**
	 * Asserts that tree root has correct indexes.
	 * @param integer $groupId tree group id.
	 * @param string $message message on fail.
	 * @return void
	 */
	protected function assertCorrectTreeRoot($groupId, $message='Tree root incorrect!') {
		$dbConnection = Yii::app()->getDb();
		$countDbCommand = $dbConnection->commandBuilder->createCountCommand(self::getTestTableName(), null);
		$recordsCount = $countDbCommand->queryScalar();

		$dbCommand = $dbConnection->createCommand();
		$dbCommand
			->select('*')
			->from( self::getTestTableName() )
			->where("group_id={$groupId} AND level = 0")
		;
		$root = $dbCommand->queryRow();

		$result = ($root['left_index']==1 && $root['right_index']==$recordsCount*2);
		$this->assertTrue($result,$message);
	}

	/**
	 * Asserts if tree structure is correct.
	 * @throws Exception on timeout.
	 * @param integer $groupId tree group id.
	 * @param string $messagePrefix - on fail message prefix.
	 */
	protected function assertTreeIsCorrect($groupId, $messagePrefix='Tree structure is incorrect: ') {
		$tableName = self::getTestTableName();
		$dbCommandBuilder =Yii::app()->db->commandBuilder;

		// Select Root:
		$criteria = array(
			'condition' => "group_id={$groupId} AND level=0"
		);
		$criteria = new CDbCriteria($criteria);
		$rows = $dbCommandBuilder->createFindCommand($tableName, $criteria)->queryAll();
		$this->assertEquals(1, count($rows), $messagePrefix.'Incorrect amount of root records!');


		list($currentRow) = $rows;
		$this->assertEquals(1, $currentRow['left_index'], $messagePrefix.'Incorrect left index of root record!');
		$treeRows = array();
		$openedTreeRows = array();
		$loopTimeOut = 10;
		$loopStartTime = time();
		while (true) {
			// Timeout:
			$loopCurrentTime = time();
			if ($loopCurrentTime-$loopStartTime > $loopTimeOut) {
				throw new Exception($messagePrefix.'loop timeout reached!');
			}

			// Remember found records:
			$treeRows[$currentRow['id']] = $currentRow;

			$this->assertTrue( (int)$currentRow['right_index'] > (int)$currentRow['left_index'], $messagePrefix.'Incorrect indexes!' );
			if ( ((int)$currentRow['right_index'] - (int)$currentRow['left_index']) > 1) {
				// Not leaf:
				if (!array_key_exists($currentRow['id'],$openedTreeRows)) {
					// Not opened node yet:
					$openedTreeRows[$currentRow['id']] = $currentRow;
					$criteria = array(
						'condition'=>"group_id={$groupId} AND left_index={$currentRow['left_index']}+1 AND level={$currentRow['level']}+1"
					);
					$criteria = new CDbCriteria($criteria);
					$rows = $dbCommandBuilder->createFindCommand($tableName, $criteria)->queryAll();
					$this->assertEquals( 1, count($rows), $messagePrefix.'Incorrect amount of node records!' );
					list($currentRow) = $rows;
				} else {
					// Already opened node:
					$criteria = array(
						'condition' => "
							group_id={$groupId} AND (
								( left_index={$currentRow['right_index']}+1 AND level={$currentRow['level']} )
								OR
								( right_index={$currentRow['right_index']}+1 AND level={$currentRow['level']}-1 )
							)
						"
					);
					$criteria = new CDbCriteria($criteria);
					$rows = $dbCommandBuilder->createFindCommand($tableName, $criteria)->queryAll();
					$this->assertEquals( 1, count($rows), $messagePrefix.'Incorrect amount of node records!' );
					list($currentRow) = $rows;
				}
			} else {
				// leaf:
				$criteria = array(
					'condition' => "
						group_id={$groupId} AND (
							( left_index={$currentRow['right_index']}+1 AND level={$currentRow['level']} )
							OR
							( right_index={$currentRow['right_index']}+1 AND level={$currentRow['level']}-1 )
						)
					"
				);
				$criteria = new CDbCriteria($criteria);
				$rows = $dbCommandBuilder->createFindCommand($tableName, $criteria)->queryAll();
				$this->assertEquals(1, count($rows), $messagePrefix.'Incorrect amount of node records!');
				list($currentRow) = $rows;
			}

			if ($currentRow['level']==0) {
				break;
			}
		}

		$criteria = array(
			'condition' => "group_id={$groupId}"
		);
		$criteria = new CDbCriteria($criteria);
		$countDbCommand = $dbCommandBuilder->createCountCommand($tableName, $criteria);
		$recordsCount = $countDbCommand->queryScalar();

		$this->assertEquals($recordsCount, count($treeRows), $messagePrefix.'Incorrect tree records count!');
	}

	/**
	 * Asserts that all trees have correct structure.
	 */
	public function assertAllTreesAreCorrect() {
		for ($groupId=1; $groupId<=self::TEST_GROUPS_COUNT; $groupId++) {
			$this->assertTreeIsCorrect($groupId, "Tree structure is incorrect for group id = {$groupId}: ");
		}
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorNestedSet();
		$this->assertTrue(is_object($behavior));
		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorNestedSet();

		$testGroupAttributes = array(
			'test_group_attribute_1',
			'test_group_attribute_2',
		);
		$this->assertTrue($behavior->setGroupAttributes($testGroupAttributes), 'Unable to set group attributes!');
		$this->assertEquals($testGroupAttributes, $behavior->getGroupAttributes(), 'Unable to set group attributes correctly!');
	}

	// Root:

	/**
	 * @depends testCreate
	 */
	public function testFindAllRoots() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();

		$rootRecords = $activeRecordFinder->root()->findAll();
		$this->assertTrue(is_array($rootRecords) && !empty($rootRecords), 'Unable to find all root records!');
		$this->assertEquals(self::TEST_GROUPS_COUNT, count($rootRecords), 'Wrong amount of root records!');
	}

	/**
	 * @depends testFindAllRoots
	 */
	public function testFindGroupRoot() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();

		$criteria = array(
			'condition'=>'level = 1',
			'order'=>'RAND()',
		);
		$testModel = $activeRecordFinder->find($criteria);
		$rootRecords = $testModel->root()->findAll();
		$this->assertEquals(1, count($rootRecords), 'Wrong amount of root records!');
		$rootRecord = array_shift($rootRecords);
		$this->assertEquals($testModel->group_id, $rootRecord->group_id, 'Root record has wrong group!');
	}

	// Axis:

	/**
	 * @depends testCreate
	 */
	public function testFindParent() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 2;
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$parentActiveRecord = $activeRecord->parent()->find();
		$this->assertTrue(is_object($parentActiveRecord), 'Unable to find parent record!');

		$assertCondition = ( ($parentActiveRecord->level == $activeRecord->level-1) && ($parentActiveRecord->left_index < $activeRecord->left_index) && ($parentActiveRecord->right_index > $activeRecord->right_index) );
		$this->assertTrue($assertCondition, 'Unable to find parent record correctly!');

		$this->assertEquals($testGroupId, $parentActiveRecord->group_id, 'Parent record has wrong group!');
	}

	/**
	 * @depends testCreate
	 */
	public function testFindChild() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 1;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$childActiveRecord = $activeRecord->child()->find();
		$this->assertTrue(is_object($childActiveRecord), 'Unable to find child record!');

		$assertCondition = ( ($childActiveRecord->level == $activeRecord->level+1) && ($childActiveRecord->left_index > $activeRecord->left_index) && ($childActiveRecord->right_index < $activeRecord->right_index) );
		$this->assertTrue($assertCondition, 'Unable to find child record correctly!');

		$this->assertEquals($testGroupId, $childActiveRecord->group_id, 'Child record has wrong group!');
	}

	/**
	 * @depends testCreate
	 */
	public function testFindAncestor() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$ancestorActiveRecords = $activeRecord->ancestor()->findAll();
		$this->assertTrue(is_array($ancestorActiveRecords) && !empty($ancestorActiveRecords), 'Unable to find ancestor records!');

		foreach ($ancestorActiveRecords as $ancestorActiveRecord) {
			$assertCondition = ( ($ancestorActiveRecord->level < $activeRecord->level) && ($ancestorActiveRecord->left_index < $activeRecord->left_index) && ($ancestorActiveRecord->right_index > $activeRecord->right_index) );
			$this->assertTrue($assertCondition, 'Unable to find ancestor record correctly!');
			$this->assertEquals($testGroupId, $ancestorActiveRecord->group_id, 'Ancestor record has wrong group!');
		}
	}

	/**
	 * @depends testCreate
	 */
	public function testFindDescendant() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 1;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$descendantActiveRecords = $activeRecord->descendant()->findAll();
		$this->assertTrue(is_array($descendantActiveRecords) && !empty($descendantActiveRecords), 'Unable to find descendant records!');

		foreach ($descendantActiveRecords as $descendantActiveRecord) {
			$assertCondition = ( ($descendantActiveRecord->level > $activeRecord->level) && ($descendantActiveRecord->left_index > $activeRecord->left_index) && ($descendantActiveRecord->right_index < $activeRecord->right_index) );
			$this->assertTrue($assertCondition, 'Unable to find descendant record correctly!');
			$this->assertEquals($testGroupId, $descendantActiveRecord->group_id, 'Descendant record has wrong group!');
		}
	}

	/**
	 * @depends testCreate
	 */
	public function testFindAncestorOrSelf() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$ancestorOrSelfActiveRecords = $activeRecord->ancestorOrSelf()->findAll();
		$this->assertTrue(is_array($ancestorOrSelfActiveRecords) && !empty($ancestorOrSelfActiveRecords), 'Unable to find ancestor or self records!');

		foreach ($ancestorOrSelfActiveRecords as $ancestorOrSelfActiveRecord) {
			$assertCondition = ( ($ancestorOrSelfActiveRecord->level <= $activeRecord->level) && ($ancestorOrSelfActiveRecord->left_index <= $activeRecord->left_index) && ($ancestorOrSelfActiveRecord->right_index >= $activeRecord->right_index) );
			$this->assertTrue($assertCondition, 'Unable to find ancestor or self record correctly!');
			$this->assertEquals($testGroupId, $ancestorOrSelfActiveRecord->group_id, 'Ancestor or self record has wrong group!');
		}
	}

	/**
	 * @depends testCreate
	 */
	public function testFindDescendantOrSelf() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$testLevel = 1;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$activeRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($activeRecord), 'Unable to find active record for the test!');

		$descendantOrSelfActiveRecords = $activeRecord->descendantOrSelf()->findAll();
		$this->assertTrue(is_array($descendantOrSelfActiveRecords) && !empty($descendantOrSelfActiveRecords), 'Unable to find descendant or self records!');

		foreach ($descendantOrSelfActiveRecords as $descendantOrSelfActiveRecord) {
			$assertCondition = ( ($descendantOrSelfActiveRecord->level >= $activeRecord->level) && ($descendantOrSelfActiveRecord->left_index >= $activeRecord->left_index) && ($descendantOrSelfActiveRecord->right_index <= $activeRecord->right_index) );
			$this->assertTrue($assertCondition, 'Unable to find descendant or self record correctly!');
			$this->assertEquals($testGroupId, $descendantOrSelfActiveRecord->group_id, 'Descendant or self record has wrong group!');
		}
	}

	// Movement:

	/**
	 * @depends testCreate
	 */
	public function testMovePrev() {
		$activeRecordModel = $this->getTestActiveRecordFinder();

		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);

		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$criteria = array(
			'order' => 'left_index ASC',
			'limit' => 2
		);
		$records = $activeRecordModel->findAllByAttributes($attributes, $criteria);
		list($prevRecord, $currentRecord) = $records;
		$this->assertTrue(is_object($currentRecord), 'Unable to find current record for the test!');
		$this->assertTrue(is_object($prevRecord), 'Unable to find previous record for the test!');

		$oldCurrentRecord = clone $currentRecord;

		$this->assertTrue($currentRecord->movePrev(), 'Unable to move record to the prev!');

		$newCurrentRecord = $currentRecord->findByPk($currentRecord->getPrimaryKey());
		$assertCondition = ( ($newCurrentRecord->left_index != $oldCurrentRecord->left_index) && ($newCurrentRecord->right_index != $oldCurrentRecord->right_index) );
		$this->assertTrue($assertCondition, 'Indexes of moved record have not been updated after move to prev!');

		$assertCondition = ( ($currentRecord->left_index == $newCurrentRecord->left_index) && ($currentRecord->right_index == $newCurrentRecord->right_index) );
		$this->assertTrue($assertCondition, 'Indexes of current record have not been updated after move to prev!');

		$oldCurrentRecordIndexDelta = $oldCurrentRecord->right_index - $oldCurrentRecord->left_index;
		$newCurrentRecordIndexDelta = $currentRecord->right_index - $currentRecord->left_index;

		$this->assertEquals($newCurrentRecordIndexDelta, $oldCurrentRecordIndexDelta, 'Index delta of current record become wrong after move prev!');

		$oldPrevRecordIndexDelta = $prevRecord->right_index - $prevRecord->left_index;
		$newPrevRecord = $prevRecord->findByPk( $prevRecord->getPrimaryKey() );
		$newPrevRecordIndexDelta = $newPrevRecord->right_index - $newPrevRecord->left_index;
		$this->assertEquals($newPrevRecordIndexDelta, $oldPrevRecordIndexDelta, 'Index delta of previous record become wrong after move prev!');

		$currentRecordIndexOffset = $oldPrevRecordIndexDelta+1;
		$prevRecordIndexOffset = $oldCurrentRecordIndexDelta+1;

		$assertCondition = ( ($currentRecord->left_index == $oldCurrentRecord->left_index-$currentRecordIndexOffset) && ($currentRecord->right_index == $oldCurrentRecord->right_index-$currentRecordIndexOffset) );
		$this->assertTrue($assertCondition, 'Wrong index data of current record after move prev!');

		$assertCondition = ( ($newPrevRecord->left_index == $prevRecord->left_index+$prevRecordIndexOffset) && ($newPrevRecord->right_index == $prevRecord->right_index+$prevRecordIndexOffset) );
		$this->assertTrue($assertCondition, 'Wrong index data of previous record after move prev!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testCreate
	 */
	public function testMoveNext() {
		$activeRecordModel = $this->getTestActiveRecordFinder();

		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);

		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$criteria = array(
			'order' => 'left_index ASC',
			'limit' => 2
		);
		$records = $activeRecordModel->findAllByAttributes($attributes, $criteria);
		list($currentRecord, $nextRecord) = $records;
		$this->assertTrue(is_object($currentRecord), 'Unable to find current record for the test!');
		$this->assertTrue(is_object($nextRecord), 'Unable to find next record for the test!');

		$oldCurrentRecord = clone $currentRecord;

		$this->assertTrue($currentRecord->moveNext(), 'Unable to move record to the next!');

		$newCurrentRecord = $currentRecord->findByPk($currentRecord->getPrimaryKey());
		$assertCondition = ( ($newCurrentRecord->left_index != $oldCurrentRecord->left_index) && ($newCurrentRecord->right_index != $oldCurrentRecord->right_index) );
		$this->assertTrue($assertCondition, 'Indexes of moved record have not been updated after move to next!');

		$assertCondition = ( ($currentRecord->left_index == $newCurrentRecord->left_index) && ($currentRecord->right_index == $newCurrentRecord->right_index) );
		$this->assertTrue($assertCondition, 'Indexes of current record have not been updated after move to next!');

		$oldCurrentRecordIndexDelta = $oldCurrentRecord->right_index - $oldCurrentRecord->left_index;
		$newCurrentRecordIndexDelta = $currentRecord->right_index - $currentRecord->left_index;

		$this->assertEquals($newCurrentRecordIndexDelta, $oldCurrentRecordIndexDelta, 'Index delta of current record become wrong after move next!');

		$oldNextRecordIndexDelta = $nextRecord->right_index - $nextRecord->left_index;
		$newNextRecord = $nextRecord->findByPk( $nextRecord->getPrimaryKey() );
		$newNextRecordIndexDelta = $newNextRecord->right_index - $newNextRecord->left_index;
		$this->assertEquals($newNextRecordIndexDelta, $oldNextRecordIndexDelta, 'Index delta of next record become wrong after move next!');

		$currentRecordIndexOffset = $oldNextRecordIndexDelta+1;
		$nextRecordIndexOffset = $oldCurrentRecordIndexDelta+1;

		$assertCondition = ( ($currentRecord->left_index == $oldCurrentRecord->left_index+$currentRecordIndexOffset) && ($currentRecord->right_index == $oldCurrentRecord->right_index+$currentRecordIndexOffset) );
		$this->assertTrue($assertCondition, 'Wrong index data of current record after move next!');

		$assertCondition = ( ($newNextRecord->left_index == $nextRecord->left_index-$nextRecordIndexOffset) && ($newNextRecord->right_index == $nextRecord->right_index-$nextRecordIndexOffset) );
		$this->assertTrue($assertCondition, 'Wrong index data of next record after move next!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testMovePrev
	 */
	public function testMoveFirst() {
		$activeRecordModel = $this->getTestActiveRecordFinder();

		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);

		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$criteria = array(
			'order' => 'left_index DESC',
		);
		$currentRecord = $activeRecordModel->findByAttributes($attributes, $criteria);
		$this->assertTrue(is_object($currentRecord), 'Unable to find current record for the test!');

		$this->assertTrue($currentRecord->moveFirst(), 'Unable to move current record to the first!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testMoveNext
	 */
	public function testMoveLast() {
		$activeRecordModel = $this->getTestActiveRecordFinder();

		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);

		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$criteria = array(
			'order' => 'left_index ASC',
		);
		$currentRecord = $activeRecordModel->findByAttributes($attributes, $criteria);
		$this->assertTrue(is_object($currentRecord), 'Unable to find current record for the test!');

		$this->assertTrue($currentRecord->moveLast(), 'Unable to move current record to the last!');

		$this->assertAllTreesAreCorrect();
	}

	// Add new record:

	/**
	 * @depends testMovePrev
	 * @depends testMoveNext
	 * @depends testMoveFirst
	 * @depends testMoveLast
	 */
	public function testSaveNewRecord() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$refParentPropertyName = $activeRecordFinder->getRefParentPropertyName();

		$testLevel = 2;
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$parentRecord = $activeRecordFinder->findByAttributes($attributes);

		$newRecord = $this->newTestActiveRecord();
		$newRecord->group_id = $testGroupId;
		$testRecordName = 'test_record_name';
		$newRecord->name = $testRecordName;
		$newRecord->$refParentPropertyName = $parentRecord->getPrimaryKey();

		$rootRecord = $parentRecord->root()->find();

		$this->assertTrue($newRecord->save(), 'Unable to save new record!');

		$refreshedNewRecord = $newRecord->findByPk($newRecord->getPrimaryKey());
		$newParentRecord = $parentRecord->findByPk($parentRecord->getPrimaryKey());

		$assertCondition = ( ($refreshedNewRecord->left_index>0) && ($refreshedNewRecord->right_index>0) && ($newRecord->level>0) );
		$this->assertTrue($assertCondition, 'Indexes and level of new record have not been saved in database!');

		$assertCondition = ( ($newRecord->left_index == $refreshedNewRecord->left_index) && ($newRecord->right_index == $refreshedNewRecord->right_index) && ($newRecord->level == $refreshedNewRecord->level) );
		$this->assertTrue($assertCondition, 'Indexes and level of new record does not updated at once!');

		$assertCondition = ( ($newRecord->left_index > $newParentRecord->left_index) && ($newRecord->right_index < $newParentRecord->right_index) && ($newRecord->level == $newParentRecord->level+1) );
		$this->assertTrue($assertCondition, 'Indexes and level of inserted record are wrong!');

		$rightIndexOffset = 2;

		$assertCondition = ( ($newParentRecord->left_index == $parentRecord->left_index) && ($newParentRecord->right_index == $parentRecord->right_index+$rightIndexOffset) );
		$this->assertTrue($assertCondition, 'Indexes of parent record have not been updated correctly!');

		$newRootRecord = $parentRecord->root()->find();
		$assertCondition = ( ($newRootRecord->left_index == $rootRecord->left_index) && ($newRootRecord->right_index == $rootRecord->right_index+$rightIndexOffset) );
		$this->assertTrue($assertCondition, 'Indexes of root record have not been updated correctly!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testSaveNewRecord
	 */
	public function testSaveNewRecordInRoot() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$rootRecord = $activeRecordFinder->root()->find();

		$newRecord = $this->newTestActiveRecord();
		$newRecord->group_id = $testGroupId;
		$testRecordName = 'test_record_name';
		$newRecord->name = $testRecordName;

		$this->assertTrue( $newRecord->save(), 'Unable to save new record!' );

		$returnedParentRecord = $newRecord->parent()->find();
		$this->assertEquals($returnedParentRecord->getPrimaryKey(), $rootRecord->getPrimaryKey(), 'Auto assigned parent record does not match the root record!');

		$this->assertAllTreesAreCorrect();
	}

	// Update Record:

	/**
	 * @depends testSaveNewRecord
	 */
	public function testSaveRecordWithNewParent() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1,self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$refParentPropertyName = $activeRecordFinder->getRefParentPropertyName();

		$testLevel = 1;
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$parentRecords = $activeRecordFinder->findAllByAttributes($attributes);
		list($oldParentRecord, $newParentRecord) = $parentRecords;

		$this->assertTrue(is_object($oldParentRecord), 'Unable to find old parent record for the test!');
		$this->assertTrue(is_object($newParentRecord), 'Unable to find new parent record for the test!');

		$childRecord = $oldParentRecord->child()->find();
		$this->assertTrue(is_object($childRecord), 'Unable to find child record for the test!');

		$childRecord->$refParentPropertyName = $newParentRecord->getPrimaryKey();
		$this->assertTrue($childRecord->save(), 'Unable to save record with the new parent!');

		$refreshedChildRecord = $childRecord->findByPk($childRecord->getPrimaryKey());
		$refreshedOldParentRecord = $oldParentRecord->findByPk($oldParentRecord->getPrimaryKey());
		$refreshedNewParentRecord = $newParentRecord->findByPk($newParentRecord->getPrimaryKey());

		$childRecordIndexDelta = $childRecord->right_index - $childRecord->left_index;
		$refreshedChildRecordIndexDelta = $refreshedChildRecord->right_index - $refreshedChildRecord->left_index;
		$this->assertEquals($refreshedChildRecordIndexDelta, $childRecordIndexDelta, 'Child record index delta is wrong after save!');

		$assertCondition = ( ($refreshedChildRecord->left_index > $refreshedNewParentRecord->left_index) && ($refreshedChildRecord->right_index < $refreshedNewParentRecord->right_index) && ($refreshedChildRecord->level == $refreshedNewParentRecord->level+1) );
		$this->assertTrue($assertCondition, 'Child record has wrong index data after save!');

		$assertCondition = ( $refreshedOldParentRecord->right_index == $oldParentRecord->right_index-($childRecordIndexDelta+1) );
		$this->assertTrue($assertCondition, 'Old parent record right index has not been updated correctly!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testSaveRecordWithNewParent
	 */
	public function testSaveRecordChildrenWithNewParent() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$refParentPropertyName = $activeRecordFinder->getRefParentPropertyName();

		$testLevel = 1;
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$records = $activeRecordFinder->findAllByAttributes($attributes);

		list($newParentRecord, $targetRecord) = $records;

		$oldChildren = $targetRecord->child()->findAll();

		$targetRecord->$refParentPropertyName = $newParentRecord->getPrimaryKey();
		$this->assertTrue($targetRecord->save(), 'Unable to save record with the new parent!');

		$newChildren = $targetRecord->child()->findAll();

		foreach ($oldChildren as $recordIndex => $oldChild) {
			$newChild = $newChildren[$recordIndex];
			$this->assertEquals($oldChild->getPrimaryKey(), $newChild->getPrimaryKey(), 'Child record missmatch!');
		}

		$this->assertAllTreesAreCorrect();
	}

	// Delete Record:

	/**
	 * @depends testSaveNewRecord
	 */
	public function testDeleteRecord() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$testLevel = 2;
		$attributes = array(
			'level' => $testLevel,
			'group_id' => $testGroupId,
		);
		$currentRecord = $activeRecordFinder->findByAttributes($attributes);
		$parentRecord = $currentRecord->parent()->find();

		$currentRecordIndexDelta = $currentRecord->right_index - $currentRecord->left_index;

		$this->assertTrue( $currentRecord->delete(), 'Unable to delete record!');

		$refreshedParentRecord = $parentRecord->findByPk($parentRecord->getPrimaryKey());

		$assertCondition = ( ($refreshedParentRecord->left_index == $parentRecord->left_index) && ($refreshedParentRecord->right_index == $parentRecord->right_index-($currentRecordIndexDelta+1)) );
		$this->assertTrue($assertCondition, 'Parent record indexes have wrong data!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testDeleteRecord
	 */
	public function testDeleteRoot() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$rootRecord = $activeRecordFinder->root()->find();

		$this->assertTrue($rootRecord->delete(), 'Unable to delete root record!');

		$recordCount = $activeRecordFinder->countByAttributes(array('group_id'=>$testGroupId));
		$this->assertTrue($recordCount<=0, 'There are still some records after the root has been deleted!');
	}

	/**
	 * @depends testSaveNewRecordInRoot
	 */
	public function testAutoCreateRoot() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$activeRecordFinder->autoCreateRoot = true;
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$activeRecordFinder->deleteAllByAttributes(array('group_id'=>$testGroupId));

		$newRecord = $this->newTestActiveRecord();
		$newRecord->group_id = $testGroupId;
		$testRecordName = 'test_record_name';
		$newRecord->name = $testRecordName;

		$this->assertTrue($newRecord->save(), 'Unable to save new record!');

		$rootRecord = $newRecord->root()->find();
		$this->assertTrue(is_object($rootRecord), 'Unable to auto create root record!');

		$returnedParentRecord = $newRecord->parent()->find();
		$this->assertEquals($returnedParentRecord->getPrimaryKey(), $rootRecord->getPrimaryKey(), 'Auto assigned parent record does not match the root record!');
		$this->assertEquals($testGroupId, $rootRecord->group_id, 'Auto created root has wrong group attributes!');

		$this->assertAllTreesAreCorrect();
	}

	/**
	 * @depends testFindGroupRoot
	 * @depends testFindChild
	 */
	public function testResetTree() {
		$activeRecordFinder = $this->getTestActiveRecordFinder();
		$testGroupId = rand(1, self::TEST_GROUPS_COUNT);
		$activeRecordFinder->group_id = $testGroupId;

		$this->assertTrue($activeRecordFinder->resetTree(), 'Unable to reset tree!');

		$totalRecordsCount = $activeRecordFinder->countByAttributes(array('group_id'=>$testGroupId));

		$rootRecord = $activeRecordFinder->root()->find();

		$rootChildRecords = $rootRecord->child()->findAll();

		$this->assertEquals($totalRecordsCount, count($rootChildRecords)+1, 'Not all records became the child of the root!' );

		$this->assertAllTreesAreCorrect();
	}
}
