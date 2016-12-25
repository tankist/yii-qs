<?php
 
/**
 * Test case for the extension "qs.files.storages.QsFileStorageBucketSubDirTemplate".
 * @see QsFileStorageBucketSubDirTemplate
 */
class QsFileStorageBucketSubDirTemplateTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.storages.*');
	}

	/**
	 * Get file storage bucket mock object.
	 * @return QsFileStorageBucketSubDirTemplate file storage bucket instance.
	 */
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
		$bucket = $this->getMock('QsFileStorageBucketSubDirTemplate', $methodsList);
		return $bucket;
	}

	// Tests :

	public function testSetGet() {
		$bucket = $this->createFileStorageBucket();

		$testFileSubDirTemplate = 'test/file/subdir/template';
		$this->assertTrue($bucket->setFileSubDirTemplate($testFileSubDirTemplate), 'Unable to set file sub dir template!');
		$this->assertEquals($bucket->getFileSubDirTemplate(), $testFileSubDirTemplate, 'Unable to set file sub dir template correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testResolveFileSubDirTemplate() {
		$bucket = $this->createFileStorageBucket();

		$testFileSubDirTemplate = '{ext}/{^name}/{^^name}';
		$bucket->setFileSubDirTemplate($testFileSubDirTemplate);

		$testFileSelfName = 'test_file_self_name';
		$testFileExtension = 'tmp';
		$testFileName = $testFileSelfName . '.' . $testFileExtension;

		$returnedFullFileName = $bucket->getFileNameWithSubDir($testFileName);

		$expectedFullFileName = $testFileExtension . '/';
		$expectedFullFileName .= substr($testFileName, 0, 1) . '/';
		$expectedFullFileName .= substr($testFileName, 1, 1) . '/';
		$expectedFullFileName .= $testFileName;

		$this->assertEquals($expectedFullFileName, $returnedFullFileName, 'Unable to resolve file sub dir correctly!');
	}
}
