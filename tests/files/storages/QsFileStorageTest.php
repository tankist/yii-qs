<?php

/**
 * Test case for the extension "qs.files.storages.QsFileStorage".
 * @see QsFileStorage
 */
class QsFileStorageTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.storages.*');
	}

	protected function createFileStorage() {
		$methodsList = array(
			'init',
		);
		$fileStorage = $this->getMock('QsFileStorage', $methodsList);
		return $fileStorage;
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
		$fileStorage = $this->createFileStorage();

		$testBucketClassName = 'TestBucketClassName';
		$this->assertTrue($fileStorage->setBucketClassName($testBucketClassName), 'Unable to set bucket class name!');
		$this->assertEquals($fileStorage->getBucketClassName(), $testBucketClassName, 'Unable to set bucket class name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testAddBucket() {
		$fileStorage = $this->createFileStorage();

		$testBucketName = 'testBucketName';
		$testBucket = $this->createFileStorageBucket();

		$this->assertTrue($fileStorage->addBucket($testBucketName, $testBucket), 'Unable to add bucket object!');

		$returnedBucket = $fileStorage->getBucket($testBucketName);
		$this->assertEquals($testBucketName, $returnedBucket->getName(), 'Added bucket has wrong name!');
	}

	/**
	 * @depends testAddBucket
	 */
	public function testAddBucketAsConfig() {
		$fileStorage = $this->createFileStorage();

		$testBucket = $this->createFileStorageBucket();
		$testBucketClassName = get_class($testBucket);

		$testBucketName = 'test_bucket_name';
		$testBucketConfig = array(
			'class' => $testBucketClassName
		);
		$this->assertTrue($fileStorage->addBucket($testBucketName, $testBucketConfig), 'Unable to add bucket as config!');

		$returnedBucket = $fileStorage->getBucket($testBucketName);
		$this->assertTrue(is_object($returnedBucket), 'Unable to get bucket added by config!');
		$this->assertEquals($testBucketClassName, get_class($returnedBucket), 'Added by config bucket has wrong class name!');
	}

	/**
	 * @depends testAddBucketAsConfig
	 */
	public function testAddBucketOnlyByName() {
		$fileStorage = $this->createFileStorage();

		$testBucket = $this->createFileStorageBucket();
		$testBucketClassName = get_class($testBucket);
		$fileStorage->setBucketClassName($testBucketClassName);

		$testBucketName = 'test_bucket_name';
		$this->assertTrue($fileStorage->addBucket($testBucketName), 'Unable to add bucket only by name!');

		$returnedBucket = $fileStorage->getBucket($testBucketName);
		$this->assertTrue(is_object($returnedBucket), 'Unable to get bucket added only by name!');
		$this->assertEquals($testBucketClassName, get_class($returnedBucket), 'Added only by name bucket has wrong class name!');
	}

	/**
	 * @depends testAddBucket
	 */
	public function testSetBuckets() {
		$fileStorage = $this->createFileStorage();

		$bucketsCount = 5;
		$testBuckets = array();
		for ($i=1; $i<=$bucketsCount; $i++) {
			$testBucketName = 'testBucketName'.$i;
			$testBucket = $this->createFileStorageBucket();
			$testBuckets[$testBucketName] = $testBucket;
		}

		$this->assertTrue($fileStorage->setBuckets($testBuckets), 'Unable to set buckets list!');
		$returnedBuckets = $fileStorage->getBuckets();
		$this->assertEquals(count($returnedBuckets), count($testBuckets), 'Wrong count of the set buckets!');
	}

	/**
	 * @depends testSetBuckets
	 * @depends testAddBucketOnlyByName
	 */
	public function testSetBucketsOnlyByName() {
		$fileStorage = $this->createFileStorage();

		$testBucket = $this->createFileStorageBucket();
		$testBucketClassName = get_class($testBucket);
		$fileStorage->setBucketClassName($testBucketClassName);

		$bucketsCount = 5;
		$testBuckets = array();
		for ($i=1; $i<=$bucketsCount; $i++) {
			$testBucketName = 'testBucketName'.$i;
			$testBuckets[] = $testBucketName;
		}

		$this->assertTrue($fileStorage->setBuckets($testBuckets), 'Unable to set bucket names list!');
		$returnedBuckets = $fileStorage->getBuckets();
		$this->assertEquals(count($returnedBuckets), count($testBuckets), 'Wrong count of the set buckets!');

		for ($i=1; $i<=$bucketsCount; $i++) {
			$testBucketName = 'testBucketName'.$i;
			$this->assertTrue(is_object($returnedBuckets[$testBucketName]), 'Returned bucket is not an object!');
		}
	}

	/**
	 * @depends testAddBucket
	 */
	public function testHasBucket() {
		$fileStorage = $this->createFileStorage();

		$testBucketName = 'test_bucket_name';
		$this->assertFalse($fileStorage->hasBucket($testBucketName), 'Not added bucket present in the storage!');

		$fileStorage->addBucket($testBucketName, array());
		$this->assertTrue($fileStorage->hasBucket($testBucketName), 'Added bucket does not present in the storage!');
	}
}
