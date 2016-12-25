<?php

Yii::import('qs.utils.QsCronTab', true);

/**
 * Test case for the extension "qs.utils.QsCronTab".
 * @see QsCronTab
 */
class QsCronTabTest extends CTestCase {
	public function setUp() {
		$testFilePath = $this->getTestFilePath();
		if (!file_exists($testFilePath)) {
			mkdir($testFilePath, 0777, true);
		}
		self::createCronTabBackup();
	}

	public function tearDown() {
		$testFilePath = $this->getTestFilePath();
		if (file_exists($testFilePath)) {
			exec("rm -rf {$testFilePath}");
		}
		self::restoreCronTabBackup();
	}

	/**
	 * Returns the test file path.
	 * @return string file path.
	 */
	protected static function getTestFilePath() {
		$filePath = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . '_' . getmypid();
		return $filePath;
	}

	/**
	 * Returns the test file path.
	 * @return string file path.
	 */
	protected static function getCronTabBackupFileName() {
		$filePath = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . '_crontab_backup.tmp';
		return $filePath;
	}

	/**
	 * Backs up the current crontab content.
	 */
	protected static function createCronTabBackup() {
		$outputLines = array();
		exec('crontab -l 2>&1', $outputLines);
		if (!empty($outputLines[0]) && stripos($outputLines[0], 'no crontab') !== 0) {
			$fileName = self::getCronTabBackupFileName();
			file_put_contents($fileName, implode("\n", $outputLines) . "\n");
		}
	}

	/**
	 * Restore the crontab from backup.
	 */
	protected static function restoreCronTabBackup() {
		$fileName = self::getCronTabBackupFileName();
		if (file_exists($fileName)) {
			exec('crontab ' . escapeshellarg($fileName));
			unlink($fileName);
		} else {
			exec('crontab -r 2>&1');
		}
	}

	// Tests :

	public function testSetGet() {
		$cronTab = new QsCronTab();

		$jobs = array(
			array(
				'min' => '*',
				'hour' => '*',
				'command' => 'ls --help',
			),
			array(
				'line' => '* * * * * ls --help',
			),
		);
		$cronTab->setJobs($jobs);
		$this->assertEquals($jobs, $cronTab->getJobs(), 'Unable to setup jobs!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetLines() {
		$cronTab = new QsCronTab();

		$jobs = array(
			array(
				'command' => 'command/line/1',
			),
			array(
				'command' => 'command/line/2',
			),
		);
		$cronTab->setJobs($jobs);

		$lines = $cronTab->getLines();
		$this->assertNotEmpty($lines, 'Unable to get lines!');

		foreach ($lines as $number => $line) {
			$this->assertContains($jobs[$number]['command'], $line, 'Wrong line composed!');
		}
	}

	/**
	 * @depends testGetLines
	 */
	public function testSaveToFile() {
		$cronTab = new QsCronTab();

		$jobs = array(
			array(
				'command' => 'command/line/1',
			),
			array(
				'command' => 'command/line/2',
			),
		);
		$cronTab->setJobs($jobs);

		$filename = self::getTestFilePath() . DIRECTORY_SEPARATOR . 'testfile.tmp';

		$cronTab->saveToFile($filename);

		$this->assertFileExists($filename, 'Unable to save file!');

		$fileContent = file_get_contents($filename);
		foreach ($jobs as $job) {
			$this->assertContains($job['command'], $fileContent, 'Job is missing!');
		}
	}

	/**
	 * @depends testSaveToFile
	 */
	public function testApply() {
		$cronTab = new QsCronTab();

		$jobs = array(
			array(
				'min' => '0',
				'hour' => '0',
				'command' => 'pwd',
			),
		);
		$cronTab->setJobs($jobs);

		$cronTab->apply();

		$currentLines = $cronTab->getCurrentLines();
		$this->assertNotEmpty($currentLines, 'Unable to setup crontab.');

		$cronTabContent = implode("\n", $currentLines);
		foreach ($jobs as $job) {
			$this->assertContains($job['command'], $cronTabContent, 'Job not present!');
		}
	}

	/**
	 * @depends testApply
	 */
	public function testMerge() {
		$cronTab = new QsCronTab();

		$firstJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'pwd',
		);
		$cronTab->setJobs(array($firstJob));
		$cronTab->apply();

		$beforeMergeCronJobCount = count($cronTab->getCurrentLines());

		$secondJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'ls',
		);
		$cronTab->setJobs(array($secondJob));
		$cronTab->apply();

		$currentLines = $cronTab->getCurrentLines();
		$this->assertNotEmpty($currentLines, 'Unable to merge crontab.');

		$afterMergeCronJobCount = count($currentLines);
		$this->assertEquals($afterMergeCronJobCount, $beforeMergeCronJobCount + 1, 'Wrong cron jobs count!');

		$cronTabContent = implode("\n", $currentLines);
		$this->assertContains($firstJob['command'], $cronTabContent, 'First job not present!');
		$this->assertContains($secondJob['command'], $cronTabContent, 'Second job not present!');
	}

	/**
	 * @depends testMerge
	 */
	public function testApplyTwice() {
		$cronTab = new QsCronTab();
		$firstJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'pwd',
		);
		$cronTab->setJobs(array($firstJob));

		$cronTab->apply();
		$beforeMergeCronJobCount = count($cronTab->getCurrentLines());

		$cronTab->apply();
		$afterMergeCronJobCount = count($cronTab->getCurrentLines());

		$this->assertEquals($afterMergeCronJobCount, $beforeMergeCronJobCount, 'Wrong cron jobs count!');
	}

	/**
	 * @depends testApply
	 */
	public function testRemoveAll() {
		$cronTab = new QsCronTab();

		$firstJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'pwd',
		);
		$cronTab->setJobs(array($firstJob));
		$cronTab->apply();

		$cronTab->removeAll();

		$currentLines = $cronTab->getCurrentLines();
		$this->assertEmpty($currentLines, 'Unable to remove cron jobs!');
	}

	/**
	 * @depends testApply
	 */
	public function testRemove() {
		$cronTab = new QsCronTab();

		$firstJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'pwd',
		);
		$secondJob = array(
			'min' => '0',
			'hour' => '0',
			'command' => 'ls',
		);
		$cronTab->setJobs(array($firstJob, $secondJob));
		$cronTab->apply();

		$cronTab->setJobs(array($firstJob));
		$cronTab->remove();

		$currentLines = $cronTab->getCurrentLines();
		$cronTabContent = implode("\n", $currentLines);

		$this->assertNotContains($firstJob['command'], $cronTabContent, 'Removed job present!');
		$this->assertContains($secondJob['command'], $cronTabContent, 'Remaining job not present!');
	}

	public function testCheckShellCommandAvailability() {
		$cronTab = new QsCronTab();

		$commandFullName = $cronTab->getCronTabCommandFullName();
		$this->assertNotEmpty($commandFullName, 'Unable to get existing command!');

		$cronTab->binPath = '/some/unexisting/path';
		$this->setExpectedException('CException');
		$cronTab->getCronTabCommandFullName();
	}
}