<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorPosition".
 * This test case checks positioning in the group mode.
 * @see QsActiveRecordBehaviorPosition
 * @see QsActiveRecordBehaviorPositionTest
 */
class QsActiveRecordBehaviorPositionGroupTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'ref_group' => 'integer',
			'position' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'behaviors' => array(
					'positionBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorPosition',
						'groupAttributes' => array('ref_group')
					)
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
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);

		for ($i=1; $i<=10; $i++) {
			$columns = array(
				'name' => 'name_'.$i,
				'ref_group' => 1,
				'position' => $i
			);
			$dbSetUp->insert($testTableName, $columns);

			$columns = array(
				'name' => 'name_'.$i,
				'ref_group' => 2,
				'position' => $i
			);
			$dbSetUp->insert($testTableName, $columns);
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
	 * @return CActiveRecord test active record finder.
	 */
	public function getTestActiveRecordFinder() {
		$activeRecord = CActiveRecord::model(self::getTestActiveRecordClassName());
		return $activeRecord;
	}

	/**
	 * Asserts if records in the test table are in list order.
	 */
	public function assertListCorrect() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$criteria = array(
			'order' => "{$positionAttributeName} ASC"
		);

		$attributes = array(
			'ref_group' => 1
		);
		$records = $activeRecordModel->findAllByAttributes($attributes, $criteria);
		foreach ($records as $recordNumber => $record) {
			$this->assertEquals($record->$positionAttributeName, $recordNumber+1, 'List positions have been broken!');
		}

		$attributes = array(
			'ref_group' => 2
		);
		$records = $activeRecordModel->findAllByAttributes($attributes, $criteria);
		foreach ($records as $recordNumber => $record) {
			$this->assertEquals($record->$positionAttributeName, $recordNumber+1, 'List positions have been broken!');
		}
	}

	// Tests:
	
	public function testMovePrev() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 5;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue-1,
			$groupAttributeName => $testGroupValue
		);
		$previousActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($previousActiveRecord), 'Unable to find previous active record for the test!');

		$this->assertTrue($currentActiveRecord->movePrev(), 'Unable to move record to the prev!');

		$this->assertEquals($testCurrentPositionValue-1, $currentActiveRecord->$positionAttributeName, 'While moving record to the prev current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk( $currentActiveRecord->getPrimaryKey() );
		$this->assertEquals($testCurrentPositionValue-1, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to the prev wrong position granted!');

		$controlPreviousActiveRecord = $previousActiveRecord->findByPk( $previousActiveRecord->getPrimaryKey() );
		$this->assertEquals($testCurrentPositionValue, $controlPreviousActiveRecord->$positionAttributeName, 'While moving record to the prev wrong position granted to the previous record!');

		$this->assertListCorrect();
	}

	public function testMoveNext() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 5;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue+1,
			$groupAttributeName => $testGroupValue
		);
		$nextActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($nextActiveRecord), 'Unable to find next active record for the test!');

		$this->assertTrue($currentActiveRecord->moveNext(), 'Unable to move record to the next!');

		$this->assertEquals($testCurrentPositionValue+1, $currentActiveRecord->$positionAttributeName, 'While moving record to the next current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk( $currentActiveRecord->getPrimaryKey() );
		$this->assertEquals($testCurrentPositionValue+1, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to the next wrong position granted!');

		$controlNextActiveRecord = $nextActiveRecord->findByPk( $nextActiveRecord->getPrimaryKey() );
		$this->assertEquals($testCurrentPositionValue, $controlNextActiveRecord->$positionAttributeName, 'While moving record to the next wrong position granted to the next record!');

		$this->assertListCorrect();
	}

	public function testMoveFirst() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 4;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$this->assertTrue($currentActiveRecord->moveFirst(), 'Unable to move record to first!');

		$this->assertEquals(1, $currentActiveRecord->$positionAttributeName, 'While moving record first current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk($currentActiveRecord->getPrimaryKey());
		$this->assertEquals(1, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to first wrong position granted!');

		$this->assertListCorrect();
	}

	public function testMoveLast() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 2;
		$recordsCount = $activeRecordModel->countByAttributes(array($groupAttributeName => $testGroupValue));
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$this->assertTrue($currentActiveRecord->moveLast(), 'Unable to move record to last!');

		$this->assertEquals($recordsCount, $currentActiveRecord->$positionAttributeName, 'While moving record last current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk($currentActiveRecord->getPrimaryKey());
		$this->assertEquals($recordsCount, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to last wrong position granted!');

		$this->assertListCorrect();
	}

	public function testMoveToPosition() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$testPositionToMove = 3;
		$this->assertTrue($currentActiveRecord->moveToPosition($testPositionToMove), 'Unable to move record to the specific position down!');

		$this->assertEquals($testPositionToMove, $currentActiveRecord->$positionAttributeName, 'While moving record to the specific position down current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk($currentActiveRecord->getPrimaryKey());
		$this->assertEquals($testPositionToMove, $controlCurrentActiveRecord->$positionAttributeName, 'Unable to move record to the specific position down correctly!');

		$testCurrentPositionValue = 3;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$testPositionToMove = 2;

		$this->assertTrue($currentActiveRecord->moveToPosition($testPositionToMove), 'Unable to move record to the specific position up!');

		$this->assertEquals($testPositionToMove, $currentActiveRecord->$positionAttributeName, 'While moving record to the specific position up current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk($currentActiveRecord->getPrimaryKey());
		$this->assertEquals($testPositionToMove, $controlCurrentActiveRecord->$positionAttributeName, 'Unable to move record to the specific position up correctly!');

		$this->assertListCorrect();
	}

	/**
	 * @depends testMoveToPosition
	 */
	public function testSave() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$testNewPositionValue = $testCurrentPositionValue-1;
		$currentActiveRecord->$positionAttributeName = $testNewPositionValue;

		$currentActiveRecord->save();

		$this->assertEquals($currentActiveRecord->$positionAttributeName, $testNewPositionValue, 'While saving, position attribute value has been lost!' );

		$this->assertListCorrect();
	}

	/**
	 * @depends testSave
	 */
	public function testMoveBetweenGroups() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();
		$groupAttributeName = 'ref_group';

		$testGroupValue = 2;
		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue,
			$groupAttributeName => $testGroupValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$testNewGroupValue = 1;
		$currentActiveRecord->setAttribute($groupAttributeName, $testNewGroupValue);
		$currentActiveRecord->save();

		$this->assertListCorrect();
	}
}
