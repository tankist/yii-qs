<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.components.PhpUnitFileSystemManager".
 * @see PhpUnitFileSystemManager
 */
class PhpUnitFileSystemManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.test.modules.phpunit.components.PhpUnitFileSystemManager');
		Yii::import('qs.test.modules.phpunit.models.PhpUnitFileSystemUnit');

		$testDirectoryName = self::getTestDirectoryName();
		if (!file_exists($testDirectoryName)) {
			mkdir($testDirectoryName, 0777, true);
		}
	}

	public static function tearDownAfterClass() {
		$testDirectoryName = self::getTestDirectoryName();
		if (file_exists($testDirectoryName)) {
			exec("rm -rf {$testDirectoryName}");
		}
	}

	/**
	 * Returns the temporary test directory name
	 * @return string temporary test directory name.
	 */
	public static function getTestDirectoryName() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.__CLASS__.getmypid();
	}

	// Tests :

	public function testSetGet() {
		$fileSystemManager = new PhpUnitFileSystemManager();

		$testExcludeNames = array(
			'exclude_file_name_1',
			'exclude_file_name_2',
		);
		$this->assertTrue($fileSystemManager->setExcludeNames($testExcludeNames), 'Unable to set exclude names!');
		$this->assertEquals($testExcludeNames, $fileSystemManager->getExcludeNames(), 'Unable to set exclude names correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testResolvePath() {
		$fileSystemManager = new PhpUnitFileSystemManager();

		$testPath = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_path_to_be_resolved';

		$this->assertTrue($fileSystemManager->resolvePath($testPath), 'Unable to resolve path!');

		$this->assertTrue(file_exists($testPath), 'Resolved path does not exist!');
	}

	/**
	 * @depends testResolvePath
	 */
	public function testExplorePath() {
		$fileSystemManager = new PhpUnitFileSystemManager();

		$testExplorePath = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_explore_path';
		$fileSystemManager->resolvePath($testExplorePath);

		$testFileName = $testExplorePath.DIRECTORY_SEPARATOR.'test_file_name.tmp';
		file_put_contents($testFileName, 'Test file content');

		$fileSystemObjects = $fileSystemManager->explore($testExplorePath);
		$this->assertFalse(empty($fileSystemObjects), 'No file found while exploring path!');
	}

	/**
	 * @depends testExplorePath
	 */
	public function testExplorePathExclude() {
		$fileSystemManager = new PhpUnitFileSystemManager();

		$testExplorePath = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_explore_path_exclude';
		$fileSystemManager->resolvePath($testExplorePath);

		$testExcludeName = 'test_exclude_name.tmp';

		$testFileName = $testExplorePath.DIRECTORY_SEPARATOR.$testExcludeName;
		file_put_contents($testFileName, 'Test file content');

		$fileSystemManager->setExcludeNames(array($testExcludeName));
		$fileSystemObjects = $fileSystemManager->explore($testExplorePath);

		$this->assertTrue(empty($fileSystemObjects), 'Unable to exclude files while exploring path!');
	}
}
