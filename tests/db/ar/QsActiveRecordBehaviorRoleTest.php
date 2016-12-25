<?php

/**
 * Test case for the extension "qs.db.ar.QsActiveRecordBehaviorRole".
 * @see QsActiveRecordBehaviorRole
 */
class QsActiveRecordBehaviorRoleTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.db.ar.*');

		$dbSetUp = new QsTestDbMigration();
		$activeRecordGenerator = new QsTestActiveRecordGenerator();

		// Slave :
		$testSlaveTableName = self::getTestSlaveTableName();
		$columns = array(
			'id' => 'pk',
			'master_id' => 'integer',
			'slave_name' => 'string',
		);
		$dbSetUp->createTable($testSlaveTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testSlaveTableName,
				'rules' => array(
					array('slave_name', 'required'),
				),
			)
		);

		// Master :
		$testMasterTableName = self::getTestMasterTableName();
		$columns = array(
			'id' => 'pk',
			'master_name' => 'string',
		);
		$dbSetUp->createTable($testMasterTableName, $columns);

		$activeRecordGenerator->generate(
			array(
				'tableName' => $testMasterTableName,
				'rules' => array(
					array('master_name', 'required'),
				),
				'behaviors' => array(
					'roleBehavior' => array(
						'class' => 'qs.db.ar.QsActiveRecordBehaviorRole',
						'relationName' => 'slave',
						'relationConfig' => array(
							$testSlaveTableName, 'master_id'
						),
					),
				),
			)
		);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestMasterTableName());
		$dbSetUp->dropTable(self::getTestSlaveTableName());

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
		$testMasterTableName = self::getTestMasterTableName();
		$testSlaveTableName = self::getTestSlaveTableName();

		$dbSetUp->truncateTable($testMasterTableName);
		$dbSetUp->truncateTable($testSlaveTableName);

		// insert:
		for ($i=1; $i<=5; $i++) {
			$data = array(
				'master_name' => 'master name '.$i
			);
			$dbSetUp->insert($testMasterTableName, $data);
		}

		for ($i=2; $i<=4; $i++) {
			$data = array(
				'master_id' => $i,
				'slave_name' => 'master name '.$i
			);
			$dbSetUp->insert($testSlaveTableName, $data);
		}
	}

	/**
	 * Returns the name of the master test table.
	 * @return string test table name.
	 */
	public static function getTestMasterTableName() {
		return 'test_master_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the master test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestMasterActiveRecordClassName() {
		return self::getTestMasterTableName();
	}

	/**
	 * Returns the name of the slave test table.
	 * @return string test table name.
	 */
	public static function getTestSlaveTableName() {
		return 'test_slave_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the slave test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestSlaveActiveRecordClassName() {
		return self::getTestSlaveTableName();
	}

	/**
	 * @return CActiveRecord test active record finder.
	 */
	public function getTestActiveRecordFinder() {
		$activeRecord = CActiveRecord::model(self::getTestMasterActiveRecordClassName());
		return $activeRecord;
	}

	// Tests:
	public function testCreate() {
		$behavior = new QsActiveRecordBehaviorRole();
		$this->assertTrue(is_object($behavior));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$behavior = new QsActiveRecordBehaviorRole();

		$testRelationName = 'Test relation name';
		$this->assertTrue($behavior->setRelationName($testRelationName), 'Unable to set relation name!');
		$this->assertEquals($behavior->getRelationName(), $testRelationName, 'Unable to set relation name correctly!');

		$testRelationConfig = array(
			'testArg1',
			'testArg2'
		);
		$this->assertTrue($behavior->setRelationConfig($testRelationConfig), 'Unable to set relation config!');
		$this->assertEquals($behavior->getRelationConfig(), $testRelationConfig, 'Unable to set relation config correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetRelationConfigParam() {
		$behavior = new QsActiveRecordBehaviorRole();

		$testClassName = 'TestClassName';
		$testForeignKey = 'test_foreign_key';
		$testRelationConfig = array(
			$testClassName,
			$testForeignKey
		);
		$behavior->setRelationConfig($testRelationConfig);

		$returnedRelationClass = $behavior->getRelationConfigParam('class');
		$this->assertEquals($testClassName, $returnedRelationClass, 'Unable to get relation config param "class"!');

		$returnedForeignKey = $behavior->getRelationConfigParam('foreignKey');
		$this->assertEquals($testForeignKey, $returnedForeignKey, 'Unable to get relation config param "foreignKey"!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testNewActiveRecordRole() {
		$activeRecordName = self::getTestMasterActiveRecordClassName();
		$activeRecord = new $activeRecordName;

		$this->assertTrue(is_object($activeRecord));
		$this->assertTrue(is_object($activeRecord->slave));
	}

	/**
	 * @depends testNewActiveRecordRole
	 */
	public function testActiveRecordRoleSave() {
		$startActiveRecord = $this->getTestActiveRecordFinder();

		$activeRecord = $startActiveRecord->find();

		$testSlaveName = 'test first name #'.rand();
		$activeRecord->slave->slave_name = $testSlaveName;

		$activeRecord->save();

		$refreshedActiveRecord = $startActiveRecord->findByPk($activeRecord->getPrimaryKey());
		$this->assertEquals($refreshedActiveRecord->slave->slave_name, $testSlaveName, 'Unable to save related active record while saving the main one!');
	}

	/**
	 * @depends testNewActiveRecordRole
	 */
	public function testActiveRecordRoleValidate() {
		$startActiveRecord = $this->getTestActiveRecordFinder();

		$activeRecord = $startActiveRecord->find();

		$this->assertTrue($activeRecord->validate(), 'Just found model fails on validate!');


		$activeRecord->master_name = 'test master name';

		$activeRecord->slave->slave_name=null;
		$this->assertFalse($activeRecord->validate(), 'Model considered as vallid, while related part is invalid!');
	}

	/**
	 * @depends testNewActiveRecordRole
	 */
	public function testActiveRecordRolePropertyAccess() {
		$activeRecordName = self::getTestMasterActiveRecordClassName();

		$activeRecord = new $activeRecordName();
		$testSlaveName = 'test_slave_name';
		$activeRecord->slave_name = $testSlaveName;
		$this->assertEquals($testSlaveName, $activeRecord->slave->slave_name, 'Unable to set property for the related active record!');
		$this->assertEquals($testSlaveName, $activeRecord->slave_name, 'Unable to get property from the related active record directly!');
	}

	/**
	 * @depends testNewActiveRecordRole
	 */
	public function testActiveRecordFinderRolePropertyAccess() {
		$activeRecordName = self::getTestMasterActiveRecordClassName();

		$activeRecordFinder = CActiveRecord::model($activeRecordName);
		$testSlaveName = 'test_slave_name';
		$activeRecordFinder->slave_name = $testSlaveName;
		$this->assertEquals($activeRecordFinder->slave->slave_name, $testSlaveName, 'Unable to set property for the related active record!');
	}
}
