<?php
 
/**
 * Test case for the extension "qs.files.archivers.QsFileArchiverConsoleCommand".
 * @see QsFileArchiverConsoleCommand
 */
class QsFileArchiverConsoleCommandTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.archivers.*');
	}

	/**
	 * Creates the test file archiver.
	 * @return QsFileArchiverConsoleCommand
	 */
	protected function createTestArchiver() {
		$methodsList = array(
			'determinePackConsoleCommandParams',
			'determineUnpackConsoleCommandParams',
		);
		$archiver = $this->getMock('QsFileArchiverConsoleCommand', $methodsList);
		return $archiver;
	}

	public function testSetGet() {
		$archiver = $this->createTestArchiver();

		$testPackCommandName = '/test/pack/command/name';
		$this->assertTrue($archiver->setPackCommandName($testPackCommandName), 'Unable to set pack command name!');
		$this->assertEquals($archiver->getPackCommandName(), $testPackCommandName, 'Unable to set pack command name correctly!');

		$testUnpackCommandName = '/test/unpack/command/name';
		$this->assertTrue($archiver->setUnpackCommandName($testUnpackCommandName), 'Unable to set unpack command name!');
		$this->assertEquals($archiver->getUnpackCommandName(), $testUnpackCommandName, 'Unable to set unpack command name correctly!');
	}
}
