<?php
 
/**
 * Test case for the extension "qs.files.archivers.QsFileArchiverHub".
 * @see QsFileArchiverHub
 */
class QsFileArchiverHubTest extends CTestCase {
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
	 * Creates the test file archiver hub.
	 * @return QsFileArchiverHub
	 */
	protected function createTestFileArchiverHub() {
		$fileArchiverConfig = array(
			'class' => 'QsFileArchiverHub'
		);
		return Yii::createComponent($fileArchiverConfig);
	}

	/**
	 * Creates file archiver.
	 * @return QsFileArchiver
	 */
	protected function createFileArchiver() {
		$methodsList = array(
			'pack',
			'unpack',
		);
		$fileStorage = $this->getMock('QsFileArchiver', $methodsList);
		return $fileStorage;
	}

	/**
	 * @return string path for the test output files.
	 */
	protected function getTestOutputPath() {
		return Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.'archivers_test_'.getmypid();
	}

	// Tests :

	public function testAddArchiver() {
		$fileArchiverHub = $this->createTestFileArchiverHub();

		$testArchiverName = 'testArchiverName';
		$testArchiver = $this->createFileArchiver();

		$this->assertTrue($fileArchiverHub->addArchiver($testArchiverName, $testArchiver), 'Unable to add archiver object!');

		$returnedArchiver = $fileArchiverHub->getArchiver($testArchiverName);
		$this->assertTrue(is_object($returnedArchiver), 'Unable to get added archiver!');
	}

	/**
	 * @depends testAddArchiver
	 */
	public function testAddArchiverAsConfig() {
		$fileArchiverHub = $this->createTestFileArchiverHub();

		$testArchiver = $this->createFileArchiver();
		$testArchiverClassName = get_class($testArchiver);

		$testArchiverName = 'test_archiver_name';
		$testArchiverConfig = array(
			'class' => $testArchiverClassName
		);
		$this->assertTrue($fileArchiverHub->addArchiver($testArchiverName, $testArchiverConfig), 'Unable to add archiver as config!');

		$returnedArchiver = $fileArchiverHub->getArchiver($testArchiverName);
		$this->assertTrue(is_object($returnedArchiver), 'Unable to get archiver added by config!');
		$this->assertEquals($testArchiverClassName, get_class($returnedArchiver), 'Added by config archiver has wrong class name!' );
	}

	/**
	 * @depends testAddArchiver
	 */
	public function testSetArchivers() {
		$fileArchiverHub = $this->createTestFileArchiverHub();

		$archiversCount = 5;
		$testArchivers = array();
		for ($i=1; $i<=$archiversCount; $i++) {
			$testArchiverName = 'testArchiverName'.$i;
			$testArchiver = $this->createFileArchiver();
			$testArchivers[$testArchiverName] = $testArchiver;
		}

		$this->assertTrue($fileArchiverHub->setArchivers($testArchivers), 'Unable to set archivers list!');
		$returnedArchivers = $fileArchiverHub->getArchivers();
		$this->assertEquals(count($returnedArchivers), count($testArchivers), 'Wrong count of the set archivers!');
	}

	/**
	 * @depends testAddArchiver
	 */
	public function testHasArchiver() {
		$fileArchiverHub = $this->createTestFileArchiverHub();

		$testArchiverName = 'test_archiver_name';
		$this->assertFalse($fileArchiverHub->hasArchiver($testArchiverName), 'Not added archiver present in the archiver!');

		$testArchiver = $this->createFileArchiver();
		$fileArchiverHub->addArchiver($testArchiverName, $testArchiver);
		$this->assertTrue($fileArchiverHub->hasArchiver($testArchiverName), 'Added archiver does not present in the archiver!');
	}

	/**
	 * @depends testHasArchiver
	 */
	public function testPack() {
		$fileArchiver = $this->createTestFileArchiverHub();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.zip';

		$this->assertTrue($fileArchiver->pack($testSourceFileName, $testArchiveFileName), 'Unable to run pack process!');
		$this->assertTrue(file_exists($testArchiveFileName), 'No archive file has been created!');
	}

	/**
	 * @depends testPack
	 */
	public function testUnpack() {
		$fileArchiver = $this->createTestFileArchiverHub();

		$testSourceFileName = __FILE__;
		$testOutputPath = $this->getTestOutputPath();
		$testArchiveFileName = $testOutputPath.DIRECTORY_SEPARATOR.'test_archive.zip';
		$fileArchiver->pack($testSourceFileName, $testArchiveFileName);

		$this->assertTrue($fileArchiver->unpack($testArchiveFileName, $testOutputPath), 'Unable to run unpack process!');

		$sourceFileBaseName = basename($testSourceFileName);
		$expectedUnpackedFileName = $testOutputPath.DIRECTORY_SEPARATOR.$sourceFileBaseName;
		$this->assertTrue(file_exists($expectedUnpackedFileName), 'No output file has been created!');
	}
}
