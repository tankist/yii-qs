<?php
 
/**
 * Test case for the extension "qs.test.modules.phpunit.models.PhpUnitFileSystemUnit".
 * @see PhpUnitFileSystemUnit
 */
class PhpUnitFileSystemUnitTest extends CTestCase {
	public static function setUpBeforeClass() {
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
		$model = new PhpUnitFileSystemUnit();

		$testPath = '/test/path/file.tmp';
		$this->assertTrue($model->setPath($testPath), 'Unable to set path!');
		$this->assertEquals($testPath, $model->getPath(), 'Unable to set path correctly!');

		$testName = 'test_name.tmp';
		$this->assertTrue($model->setName($testName), 'Unable to set name!');
		$this->assertEquals($testName, $model->getName(), 'Unable to set name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineName() {
		$model = new PhpUnitFileSystemUnit();

		$testDirName = '/test/dir/name';
		$testFileName = 'test_file_name.tmp';
		$model->setPath($testDirName.DIRECTORY_SEPARATOR.$testFileName);

		$this->assertEquals($testFileName, $model->getName(), 'Unable to determine name from path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDirName() {
		$model = new PhpUnitFileSystemUnit();

		$testPath = '/test/path';
		$testName = 'test_name.tmp';

		$model->setPath($testPath.DIRECTORY_SEPARATOR.$testName);

		$this->assertEquals($testPath, $model->getDirName(), 'Unable to get correct directory name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetRelativePath() {
		$model = new PhpUnitFileSystemUnit();

		$testBasePath = '/test/base/path';
		$testSubPath = 'test/sub/path';

		$testModelName = 'test_model_name.tmp';
		$testModelPath = $testBasePath.DIRECTORY_SEPARATOR.$testSubPath.DIRECTORY_SEPARATOR.$testModelName;
		$model->setPath($testModelPath);

		$relativePath = $model->getRelativePath($testBasePath);
		$expectedRelativePath = $testSubPath.DIRECTORY_SEPARATOR.$testModelName;

		$this->assertEquals($expectedRelativePath, $relativePath , 'Wrong relative path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetIsFile() {
		$model = new PhpUnitFileSystemUnit();

		$testPath = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_is_file.tmp';
		$model->setPath($testPath);

		file_put_contents($testPath, 'Test file content');
		$this->assertTrue($model->getIsFile(), 'Unable to determine file!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetIsDir() {
		$model = new PhpUnitFileSystemUnit();

		$testPath = self::getTestDirectoryName().DIRECTORY_SEPARATOR.'test_is_dir_tmp';
		$model->setPath($testPath);

		mkdir($testPath, 0777, true);
		$this->assertTrue($model->getIsDir(), 'Unable to determine dir!');
	}
}
