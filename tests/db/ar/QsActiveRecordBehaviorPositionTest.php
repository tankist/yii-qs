<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorPosition".
 * @see QsActiveRecordBehaviorPosition
 */
class QsActiveRecordBehaviorPositionTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'position' => 'integer',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
				'behaviors' => array(
					'positionBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorPosition'
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
		$records = $activeRecordModel->findAll(array('order' => "{$positionAttributeName} ASC"));
		foreach ($records as $recordNumber => $record) {
			$this->assertEquals($record->$positionAttributeName, $recordNumber+1, 'List positions have been broken!');
		}
	}

	// Tests:

	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorPosition();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorPosition();

		$testPositionAttributeName = 'test_position_attribute_name';

		$this->assertTrue($behavior->setPositionAttributeName($testPositionAttributeName), 'Unable to set position attribute name!');
		$this->assertEquals($behavior->getPositionAttributeName(), $testPositionAttributeName, 'Unable to set position attribute name correctly!');

		$testGroupAttributes = array(
			'test_group_attribute_1',
			'test_group_attribute_2'
		);
		$this->assertTrue($behavior->setGroupAttributes($testGroupAttributes), 'Unable to set group attributes!');
		$this->assertEquals($behavior->getGroupAttributes(), $testGroupAttributes, 'Unable to set group attributes correctly!');

		$testDefautOrdering = 'testDefautOrderingValue';
		$this->assertTrue($behavior->setDefaultOrdering($testDefautOrdering), 'Unable to set default ordering!');
		$this->assertEquals($behavior->getDefaultOrdering(), $testDefautOrdering, 'Unable to set default ordering correctly!');

		$testPositionOnSave = 15;
		$this->assertTrue($behavior->setPositionOnSave($testPositionOnSave), 'Unable to set position on save!');
		$this->assertEquals($behavior->getPositionOnSave(), $testPositionOnSave, 'Unable to set position on save correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testMovePrev() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue-1
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

	/**
	 * @depends testSetGet
	 */
	public function testMoveNext() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$testCurrentPositionValue = 1;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue+1
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

	/**
	 * @depends testSetGet
	 */
	public function testMoveFirst() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$this->assertTrue($currentActiveRecord->moveFirst(), 'Unable to move record to first!');

		$this->assertEquals(1, $currentActiveRecord->$positionAttributeName, 'While moving record first current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk( $currentActiveRecord->getPrimaryKey() );
		$this->assertEquals(1, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to first wrong position granted!');

		$this->assertListCorrect();
	}

	/**
	 * @depends testSetGet
	 */
	public function testMoveLast() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$recordsCount = $activeRecordModel->count();

		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$this->assertTrue($currentActiveRecord->moveLast(), 'Unable to move record to last!');

		$this->assertEquals($recordsCount, $currentActiveRecord->$positionAttributeName, 'While moving record last current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk($currentActiveRecord->getPrimaryKey());
		$this->assertEquals($recordsCount, $controlCurrentActiveRecord->$positionAttributeName, 'While moving record to last wrong position granted!');

		$this->assertListCorrect();
	}

	/**
	 * @depends testSetGet
	 */
	public function testMoveToPosition() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$testPositionToMove = 3;
		$this->assertTrue($currentActiveRecord->moveToPosition($testPositionToMove), 'Unable to move record to the specific position down!');

		$this->assertEquals($testPositionToMove, $currentActiveRecord->$positionAttributeName, 'While moving record to the specific position down current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk( $currentActiveRecord->getPrimaryKey() );
		$this->assertEquals($testPositionToMove, $controlCurrentActiveRecord->$positionAttributeName, 'Unable to move record to the specific position down correctly!');

		$testCurrentPositionValue = 3;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$testPositionToMove = 2;

		$this->assertTrue($currentActiveRecord->moveToPosition($testPositionToMove), 'Unable to move record to the specific position up!');

		$this->assertEquals($testPositionToMove, $currentActiveRecord->$positionAttributeName, 'While moving record to the specific position up current object does not updated!');

		$controlCurrentActiveRecord = $activeRecordModel->findByPk( $currentActiveRecord->getPrimaryKey() );
		$this->assertEquals($testPositionToMove, $controlCurrentActiveRecord->$positionAttributeName, 'Unable to move record to the specific position up correctly!');

		$this->assertListCorrect();
	}

	/**
	 * @depends testMoveToPosition
	 */
	public function testSave() {
		$activeRecordModel = $this->getTestActiveRecordFinder();
		$positionAttributeName = $activeRecordModel->getPositionAttributeName();

		$testCurrentPositionValue = 2;
		$attributes = array(
			$positionAttributeName => $testCurrentPositionValue
		);
		$currentActiveRecord = $activeRecordModel->findByAttributes($attributes);
		$this->assertTrue(is_object($currentActiveRecord), 'Unable to find active record for the test!');

		$testNewPositionValue = $testCurrentPositionValue-1;
		$currentActiveRecord->$positionAttributeName = $testNewPositionValue;

		$currentActiveRecord->save();

		$this->assertEquals($currentActiveRecord->$positionAttributeName, $testNewPositionValue, 'While saving, position attribute value has been lost!');

		$this->assertListCorrect();
	}
}
