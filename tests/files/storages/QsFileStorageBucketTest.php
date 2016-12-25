<?php

/**
 * Test case for the extension "qs.files.storages.QsFileStorageBucket".
 * @see QsFileStorageBucket
 */
class QsFileStorageBucketTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.storages.*');
	}

	protected function createFileStorageBucket() {
		$methodsList = array(
			'create',
			'destroy',
			'exists',
			'saveFileContent',
			'getFileContent',
			'deleteFile',
			'fileExists',
			'copyFileIn',
			'copyFileOut',
			'copyFileInternal',
			'moveFileIn',
			'moveFileOut',
			'moveFileInternal',
			'getFileUrl',
		);
		$bucket = $this->getMock('QsFileStorageBucket', $methodsList);
		return $bucket;
	}

	public function testSetGet() {
		$bucket = $this->createFileStorageBucket();

		$testName = 'test_bucket_name';
		$this->assertTrue($bucket->setName($testName), 'Unable to set name!');
		$this->assertEquals($bucket->getName(), $testName, 'Unable to set name! correctly');

		$testStorage = $this->getMock('QsFileStorage');
		$this->assertTrue($bucket->setStorage($testStorage), 'Unable to set storage!');
		$this->assertEquals($bucket->getStorage(), $testStorage, 'Unable to set storage correctly!');
	}
}
