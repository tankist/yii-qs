<?php

/**
 * Test case for the extension "qs.files.storages.filesystem.QsFileStorageBucketFileSystem".
 * @see QsFileStorageBucketFileSystem
 */
class QsFileStorageBucketFileSystemTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.files.storages.*');
		Yii::import('qs.files.storages.filesystem.*');
	}

	public function tearDown() {
		$testBasePath = $this->getTestBasePath();
		if (file_exists($testBasePath)) {
			$command = "rm -r {$testBasePath}";
			exec($command);
		}

		$testTmpPath = $this->getTestTmpPath();
		if (file_exists($testTmpPath)) {
			$command = "rm -r {$testTmpPath}";
			exec($command);
		}
	}

	/**
	 * Returns the test file storage base path.
	 * @return string file storage base path.
	 */
	protected function getTestBasePath() {
		$basePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'test_file_storage';
		return $basePath;
	}

	/**
	 * Returns the path for the temporary files.
	 * @return string temporary path
	 */
	protected function getTestTmpPath() {
		$tmpPath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'test_file_storage_tmp';
		if (!file_exists($tmpPath)) {
			@mkdir($tmpPath,0777, true);
		}
		return $tmpPath;
	}

	/**
	 * Creates file storage.
	 * @return QsFileStorageFileSystem file storage instance
	 */
	protected function createFileStorage() {
		$componentConfig = array(
			'class' => 'QsFileStorageFileSystem',
			'basePath' => $this->getTestBasePath(),
			'baseUrl' => 'http://test/base/url',
			'filePermission' => 0777
		);
		$storage = Yii::createComponent($componentConfig);
		return $storage;
	}

	/**
	 * Creates new file storage bucket.
	 * @return QsFileStorageBucketFileSystem file storage bucket instance
	 */
	protected function createFileStorageBucket() {
		$storage = $this->createFileStorage();

		$componentConfig = array(
			'class' => 'QsFileStorageBucketFileSystem',
			'storage' => $storage,
		);
		$bucket = Yii::createComponent($componentConfig);
		return $bucket;
	}

	// Tests:
	public function testSetGet() {
		$bucket = $this->createFileStorageBucket();

		$testBaseSubPath = 'test/base/sub/path';
		$this->assertTrue($bucket->setBaseSubPath($testBaseSubPath), 'Unable to set base sub path!');
		$this->assertEquals($bucket->getBaseSubPath(), $testBaseSubPath, 'Unable to set base sub path correctly!');

		$testFileSubDirTemplate = 'test/file/subdir/template';
		$this->assertTrue($bucket->setFileSubDirTemplate($testFileSubDirTemplate), 'Unable to set file sub dir template!');
		$this->assertEquals($bucket->getFileSubDirTemplate(), $testFileSubDirTemplate, 'Unable to set file sub dir template correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultBaseSubPath() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_bucket_name';
		$bucket->setName($testBucketName);

		$defaultBaseSubPath = $bucket->getBaseSubPath();
		$this->assertEquals($testBucketName, $defaultBaseSubPath, 'Default base sub path has incorrect value!' );
	}

	/**
	 * @depends testSetGet
	 */
	public function testResolveFileSubDirTemplate() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_bucket_name';
		$bucket->setName($testBucketName);

		$testFileSubDirTemplate = '{ext}/{^name}/{^^name}';
		$bucket->setFileSubDirTemplate($testFileSubDirTemplate);

		$testFileSelfName = 'test_file_self_name';
		$testFileExtention = 'tmp';
		$testFileName = $testFileSelfName.'.'.$testFileExtention;

		$returnedFullFileName = $bucket->getFullFileName($testFileName);

		$expectedFullFileName = $bucket->getStorage()->getBasePath().DIRECTORY_SEPARATOR;
		$expectedFullFileName .= $bucket->getBaseSubPath().DIRECTORY_SEPARATOR;
		$expectedFullFileName .= $testFileExtention.DIRECTORY_SEPARATOR;
		$expectedFullFileName .= substr($testFileName, 0, 1).DIRECTORY_SEPARATOR;
		$expectedFullFileName .= substr($testFileName, 1, 1).DIRECTORY_SEPARATOR;
		$expectedFullFileName .= $testFileName;

		$this->assertEquals($expectedFullFileName, $returnedFullFileName, 'Unable to resolve file sub dir correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testCreateBucket() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_bucket_name';
		$bucket->setName($testBucketName);

		$this->assertTrue($bucket->create(), 'Unable to create bucket!');

		$bucketFullBasePath = $bucket->getFullBasePath();
		$this->assertTrue(file_exists($bucketFullBasePath) && is_dir($bucketFullBasePath) , 'Unable to create bucket full path directory!');
	}

	/**
	 * @depends testCreateBucket
	 */
	public function testBucketDestroy() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_destroy_bucket';
		$bucket->setName($testBucketName);
		$bucket->create();

		$this->assertTrue($bucket->destroy(), 'Unable to destroy bucket!');

		$bucketFullBasePath = $bucket->getFullBasePath();
		$this->assertFalse(file_exists($bucketFullBasePath), 'Unable to destory bucket full path directory!');
	}

	/**
	 * @depends testBucketDestroy
	 */
	public function testBucketExists() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_exists_bucket';
		$bucket->setName($testBucketName);

		$this->assertFalse($bucket->exists(), 'Not yet created bucket exists!');

		$bucket->create();
		$this->assertTrue($bucket->exists(), 'Created bucket does not exists!');

		$bucket->destroy();
		$this->assertFalse($bucket->exists(), 'Destroyed bucket exists!');
	}

	/**
	 * @depends testBucketExists
	 */
	public function testSaveFileContent() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_save_file_content_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$this->assertTrue($bucket->saveFileContent($testFileName, $testFileContent), 'Unable to save file content!');

		$bucketFileName = $bucket->getFullFileName($testFileName);
		$this->assertTrue(file_exists($bucketFileName), 'Unable to create file in the bucket!');
	}

	/**
	 * @depends testSaveFileContent
	 */
	public function testGetFileContent() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_get_file_content_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);

		$returnedFileContent = $bucket->getFileContent($testFileName);

		$this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get file content!');
	}

	/**
	 * @depends testSaveFileContent
	 */
	public function testDeleteFile() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_delete_file_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);

		$this->assertTrue($bucket->deleteFile($testFileName), 'Unable to delete file!');
		$bucketFullBasePath = $bucket->getFullBasePath();
		$bucketFileName = $bucketFullBasePath.DIRECTORY_SEPARATOR.$testFileName;
		$this->assertFalse(file_exists($bucketFileName), 'Unable to delete file in the bucket!');
	}

	/**
	 * @depends testDeleteFile
	 */
	public function testFileExists() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_exists_file_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';

		$this->assertFalse($bucket->fileExists($testFileName), 'Not saved yet file exists!');

		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);
		$this->assertTrue($bucket->fileExists($testFileName), 'Saved file does not exist!');

		$bucket->deleteFile($testFileName);
		$this->assertFalse($bucket->fileExists($testFileName), 'Deleted file exists!');
	}

	/**
	 * @depends testFileExists
	 */
	public function testCopyFileIn() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_copy_file_in_bucket';
		$bucket->setName($testBucketName);

		$testSrcFileSelfName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$tmpPath = $this->getTestTmpPath();
		$testSrcFileName = $tmpPath.DIRECTORY_SEPARATOR.$testSrcFileSelfName;
		file_put_contents($testSrcFileName, $testFileContent);

		$testBucketFileName = 'test_bucket_file_name.tmp';

		$this->assertTrue($bucket->copyFileIn($testSrcFileName, $testBucketFileName), 'Unable to copy file into the bucket!');

		$returnedFileContent = $bucket->getFileContent($testBucketFileName);
		$this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get copied file content!');
	}

	/**
	 * @depends testFileExists
	 */
	public function testCopyFileOut() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_copy_file_out_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);

		$testDestFileSelfName = 'test_dest_file.tmp';
		$tmpPath = $this->getTestTmpPath();
		$testDestFileName = $tmpPath.DIRECTORY_SEPARATOR.$testDestFileSelfName;

		$this->assertTrue($bucket->copyFileOut($testFileName, $testDestFileName), 'Unable to copy file out from the bucket!');
		$this->assertTrue(file_exists($testDestFileName), 'Destination file has not been created!');
		$this->assertEquals($testFileContent, file_get_contents($testDestFileName), 'Destination file has wrong content!');
	}

	/**
	 * @depends testCopyFileIn
	 */
	public function testMoveFileIn() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_move_file_in_bucket';
		$bucket->setName($testBucketName);

		$testSrcFileSelfName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$tmpPath = $this->getTestTmpPath();
		$testSrcFileName = $tmpPath.DIRECTORY_SEPARATOR.$testSrcFileSelfName;
		file_put_contents($testSrcFileName, $testFileContent);

		$testBucketFileName = 'test_bucket_file_name.tmp';

		$this->assertTrue($bucket->moveFileIn($testSrcFileName, $testBucketFileName), 'Unable to move file into the bucket!');
		$this->assertFalse(file_exists($testSrcFileName), 'Source file has not been deleted!');

		$returnedFileContent = $bucket->getFileContent($testBucketFileName);
		$this->assertEquals($testFileContent, $returnedFileContent, 'Unable to get moved file content!');
	}

	/**
	 * @depends testCopyFileOut
	 */
	public function testMoveFileOut() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_move_file_out_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);

		$testDestFileSelfName = 'test_dest_file.tmp';
		$tmpPath = $this->getTestTmpPath();
		$testDestFileName = $tmpPath.DIRECTORY_SEPARATOR.$testDestFileSelfName;

		$this->assertTrue($bucket->moveFileOut($testFileName, $testDestFileName), 'Unable to move file out from the bucket!');
		$this->assertTrue(file_exists($testDestFileName), 'Destination file has not been created!');
		$this->assertEquals($testFileContent, file_get_contents($testDestFileName), 'Destination file has wrong content!');
		$this->assertFalse($bucket->fileExists($testFileName), 'Source file has not been deleted!');
	}

	/**
	 * @depends testCopyFileIn
	 */
	public function testCopyFileInternalSameBucket() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_copy_file_internal_bucket';
		$bucket->setName($testBucketName);

		$testSrcFileName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testSrcFileName,$testFileContent);

		$testDestFileName = 'test_dest_file.tmp';
		$this->assertTrue($bucket->copyFileInternal($testSrcFileName,$testDestFileName), 'Unable to copy file internally in the same bucket!');
		$this->assertTrue($bucket->fileExists($testDestFileName), 'Unable to create destination file!');
		$this->assertEquals($testFileContent, $bucket->getFileContent($testDestFileName), 'Destination file has wrong content!');
	}

	/**
	 * @depends testCopyFileInternalSameBucket
	 */
	public function testCopyFileInternalDifferentBuckets() {
		$fileStorage = $this->createFileStorage();
		$testSrcBucketName = 'test_copy_file_internal_src_bucket';
		$testDestBucketName = 'test_copy_file_internal_dest_bucket';
		$buckets = array(
			$testSrcBucketName,
			$testDestBucketName
		);
		$fileStorage->setBuckets($buckets);

		$srcBucket = $fileStorage->getBucket($testSrcBucketName);
		$destBucket = $fileStorage->getBucket($testDestBucketName);

		$testSrcFileName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$srcBucket->saveFileContent($testSrcFileName, $testFileContent);
		$testDestFileName = 'test_dest_file.tmp';

		$srcFileRef = array(
			$testSrcBucketName,
			$testSrcFileName
		);
		$destFileRef = array(
			$testDestBucketName,
			$testDestFileName
		);
		$this->assertTrue($srcBucket->copyFileInternal($srcFileRef,$destFileRef), 'Unable to copy file internal between different buckets!');
		$this->assertTrue($destBucket->fileExists($testDestFileName), 'Unable to create destination file!');
	}

	/**
	 * @depends testCopyFileInternalSameBucket
	 */
	public function testMoveFileInternalSameBucket() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_move_file_internal_bucket';
		$bucket->setName($testBucketName);

		$testSrcFileName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testSrcFileName,$testFileContent);

		$testDestFileName = 'test_dest_file.tmp';
		$this->assertTrue($bucket->moveFileInternal($testSrcFileName,$testDestFileName), 'Unable to move file internally in the same bucket!');
		$this->assertTrue($bucket->fileExists($testDestFileName), 'Unable to create destination file!');
		$this->assertEquals($testFileContent, $bucket->getFileContent($testDestFileName), 'Destination file has wrong content!');
		$this->assertFalse($bucket->fileExists($testSrcFileName), 'Unable to delete source file!');
	}

	/**
	 * @depends testMoveFileInternalSameBucket
	 */
	public function testMoveFileInternalDifferentBuckets() {
		$fileStorage = $this->createFileStorage();
		$testSrcBucketName = 'test_move_file_internal_src_bucket';
		$testDestBucketName = 'test_move_file_internal_dest_bucket';
		$buckets = array(
			$testSrcBucketName,
			$testDestBucketName
		);
		$fileStorage->setBuckets($buckets);

		$srcBucket = $fileStorage->getBucket($testSrcBucketName);
		$destBucket = $fileStorage->getBucket($testDestBucketName);

		$testSrcFileName = 'test_src_file.tmp';
		$testFileContent = 'Test file content';
		$srcBucket->saveFileContent($testSrcFileName, $testFileContent);
		$testDestFileName = 'test_dest_file.tmp';

		$srcFileRef = array(
			$testSrcBucketName,
			$testSrcFileName
		);
		$destFileRef = array(
			$testDestBucketName,
			$testDestFileName
		);
		$this->assertTrue($srcBucket->moveFileInternal($srcFileRef,$destFileRef), 'Unable to move file internal between different buckets!');
		$this->assertTrue($destBucket->fileExists($testDestFileName), 'Unable to create destination file!');
		$this->assertFalse($srcBucket->fileExists($testSrcFileName), 'Unable to delete the source file!');
	}

	/**
	 * @depends testFileExists
	 */
	public function testGetFileUrl() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_get_file_url_bucket';
		$bucket->setName($testBucketName);

		$testFileName = 'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$bucket->saveFileContent($testFileName, $testFileContent);

		$returnedFileUrl = $bucket->getFileUrl($testFileName);
		$this->assertTrue(!empty($returnedFileUrl), 'File URL is empty!');

		$bucketFileName = $bucket->getFullFileName($testFileName);

		$fileSubName = str_replace($bucket->getStorage()->getBasePath(), '', $bucketFileName);

		$expectedFileUrl = $bucket->getStorage()->getBaseUrl().$fileSubName;
		$this->assertEquals($expectedFileUrl, $returnedFileUrl, 'Wrong file URL returned!');
	}

	/**
	 * @depends testSaveFileContent
	 */
	public function testSaveFileNameWithDirSeparator() {
		$bucket = $this->createFileStorageBucket();
		$testBucketName = 'test_save_file_name_with_dir_separator';
		$bucket->setName($testBucketName);

		$testFileNamePath = 'test_file_name_path';
		$testFileName = $testFileNamePath.DIRECTORY_SEPARATOR.'test_file_name.tmp';
		$testFileContent = 'Test file content';
		$this->assertTrue($bucket->saveFileContent($testFileName, $testFileContent), 'Unable to save file with name, containing dir separator, content!');

		$bucketFileName = $bucket->getFullFileName($testFileName);
		$this->assertTrue( file_exists($bucketFileName), 'Unable to create file with name, containing dir separator, in the bucket!' );
	}
}
