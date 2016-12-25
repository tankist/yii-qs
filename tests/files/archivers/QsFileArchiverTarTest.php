<?php
 
/**
 * Test case for the extension "qs.files.archivers.QsFileArchiverTar".
 * @see QsFileArchiverTar
 */
class QsFileArchiverTarTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.archivers.*');
	}

	public function setUp() {
		$testOutputPath = $this->getTestOutputPath();
		if (!file_exists($testOutputPath)) {
			mkdir($testOutputPath,0777);
		}
	}

	public function tearDown() {
		$testOutputPath = $this->getTestOutputPath();
		if (file_exists($testOutputPath)) {
			$command = "rm -r {$testOutputPath}";
			exec($command);
		}
	}

	/**
	 * Creates the test file archiver.
	 * @return QsFileArchiverTar
	 */
	protected function createTestArchiver() {
		$fileArchiverConfig = array(
			'class' => 'QsFileArchiverTar'
		);
		return Yii::createComponent($fileArchiverConfig);
	}

	/**
	 * @return string path for the test output files.
	 */
	protected function getTestOutputPath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'archivers_test_'.getmypid();
	}

	public function testPack() {
		$fileArchiver = $this->createTestArchiver();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.tar';

		$this->assertTrue($fileArchiver->pack($testSourceFileName, $testArchiveFileName), 'Unable to run pack process!');
		$this->assertTrue(file_exists($testArchiveFileName), 'No archive file has been created!');
	}

	/**
	 * @depends testPack
	 */
	public function testUnpack() {
		$fileArchiver = $this->createTestArchiver();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.tar';
		$fileArchiver->pack($testSourceFileName, $testArchiveFileName);

		$this->assertTrue($fileArchiver->unpack($testArchiveFileName, $testOutputPath), 'Unable to run unpack process!');

		$sourceFileBaseName = basename($testSourceFileName);
		$expectedUnpackedFileName = $testOutputPath.DIRECTORY_SEPARATOR.$sourceFileBaseName;
		$this->assertTrue(file_exists($expectedUnpackedFileName), 'No output file has been created!');
	}

	/**
	 * @depends testPack
	 */
	public function testPackBZip() {
		$fileArchiver = $this->createTestArchiver();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.tbz';

		$this->assertTrue($fileArchiver->pack($testSourceFileName, $testArchiveFileName), 'Unable to run pack process!');
		$this->assertTrue(file_exists($testArchiveFileName), 'No archive file has been created!');
	}

	/**
	 * @depends testUnpack
	 * @depends testPackBZip
	 */
	public function testUnpackBZip() {
		$fileArchiver = $this->createTestArchiver();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.tbz';
		$fileArchiver->pack($testSourceFileName, $testArchiveFileName);

		$this->assertTrue($fileArchiver->unpack($testArchiveFileName, $testOutputPath), 'Unable to run unpack process!');

		$sourceFileBaseName = basename($testSourceFileName);
		$expectedUnpackedFileName = $testOutputPath.DIRECTORY_SEPARATOR.$sourceFileBaseName;
		$this->assertTrue(file_exists($expectedUnpackedFileName), 'No output file has been created!');
	}
}
