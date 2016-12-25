<?php
 
/**
 * Test case for the extension "qs.files.csv.QsCsvExporter".
 * @see QsCsvExporter
 */
class QsCsvExporterTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.csv.QsCsvExporter');
	}

	public function setUp() {
		$testFilePath = $this->getTestFilePath();
		if (!file_exists($testFilePath)) {
			mkdir($testFilePath, 0777, true);
		}
	}

	public function tearDown() {
		$testFilePath = $this->getTestFilePath();
		if (file_exists($testFilePath)) {
			exec("rm -rf {$testFilePath}");
		}
	}

	/**
	 * Returns the test file path.
	 * @return string file path.
	 */
	protected static function getTestFilePath() {
		$filePath = Yii::getPathOfAlias('application.runtime').DIRECTORY_SEPARATOR.__CLASS__.'_'.getmypid();
		return $filePath;
	}

	/**
	 * Creates the CSV exporter instance.
	 * @return QsCsvExporter
	 */
	protected function createCsvExporter() {
		$csvExporter = new QsCsvExporter();
		$csvExporter->setCsvFileConfig(
			array(
				'baseFilePath' => self::getTestFilePath().DIRECTORY_SEPARATOR.'csv'
			)
		);
		return $csvExporter;
	}

	/**
	 * Creates test file archiver instance.
	 * @return IQsFileArchiver file archiver instance.
	 */
	protected function createFileArchiver() {
		Yii::import('qs.files.archivers.*');
		$config = array(
			'class' => 'QsFileArchiverTar',
		);
		$component = Yii::createComponent($config);
		$component->init();
		return $component;
	}

	// Tests:

	public function testSetGet() {
		$csvExporter = new QsCsvExporter();

		$testCsvFileConfig = array(
			'testParam1' => 'testValue1',
			'testParam2' => 'testValue2',
		);
		$this->assertTrue($csvExporter->setCsvFileConfig($testCsvFileConfig), 'Unable to set CSV file config!');
		$this->assertEquals($testCsvFileConfig, $csvExporter->getCsvFileConfig(), 'Unable to set CSV file config correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testExportDataProvider() {
		$csvExporter = $this->createCsvExporter();

		$testRawData = array(
			array(
				'field_1' => 'value_1_1',
				'field_2' => 'value_2_1',
			),
			array(
				'field_1' => 'value_1_2',
				'field_2' => 'value_2_2',
			),
		);
		$dataProvider = new CArrayDataProvider($testRawData);

		$this->assertTrue($csvExporter->exportDataProvider($dataProvider), 'Unable to export data provider!');

		$exportFiles = $csvExporter->getExportFiles();
		$this->assertTrue(count($exportFiles)>0, 'No export file has been created!');
	}

	/**
	 * @depends testExportDataProvider
	 */
	public function testExportDataProviderSplitFiles() {
		$testCsvMaxEntriesCount = 3;
		$csvExporter = $this->createCsvExporter();
		$csvFileConfig = $csvExporter->getCsvFileConfig();
		$csvFileConfig['maxEntriesCount'] =$testCsvMaxEntriesCount;
		$csvExporter->setCsvFileConfig($csvFileConfig);

		$testRowsCount = $testCsvMaxEntriesCount*2;
		$testRawData = array();
		for ($i=1; $i<=$testRowsCount; $i++) {
			$testRawData[] = array(
				'field_1' => 'value_1_'.$i,
				'field_2' => 'value_2_'.$i,
			);
		}
		$dataProvider = new CArrayDataProvider($testRawData);

		$this->assertTrue($csvExporter->exportDataProvider($dataProvider), 'Unable to export data provider!');

		$exportFiles = $csvExporter->getExportFiles();
		$this->assertTrue(count($exportFiles)>1, 'Unable to split export files!');
	}

	/**
	 * @depends testExportDataProvider
	 */
	public function testArchiveExportFiles() {
		$csvExporter = $this->createCsvExporter();

		$testFileArchiverApplicationComponentName = 'testFileArchiver'.get_class($this);
		$csvExporter->fileArchiverApplicationComponentName = $testFileArchiverApplicationComponentName;
		$testFileArchiver = $this->createFileArchiver();
		Yii::app()->setComponent($testFileArchiverApplicationComponentName, $testFileArchiver);

		$testRawData = array(
			array(
				'field_1' => 'value_1_1',
				'field_2' => 'value_2_1',
			),
			array(
				'field_1' => 'value_1_2',
				'field_2' => 'value_2_2',
			),
		);

		$dataProvider = new CArrayDataProvider($testRawData);
		$csvExporter->exportDataProvider($dataProvider);
		
		$testArchiveFileName = self::getTestFilePath().DIRECTORY_SEPARATOR.'manual_archive_'.getmypid().'.tbz';
		$this->assertTrue($csvExporter->archiveExportFiles($testArchiveFileName), 'Unable to archive export files!');

		$this->assertTrue(file_exists($testArchiveFileName), 'No archive file has been created!');
	}

	/**
	 * @depends testArchiveExportFiles
	 */
	public function testArchiveExportFilesWithoutComponent() {
		$csvExporter = $this->createCsvExporter();

		$testFileArchiverApplicationComponentName = 'some_not_existing_component_name';
		$csvExporter->fileArchiverApplicationComponentName = $testFileArchiverApplicationComponentName;

		$testRawData = array(
			array(
				'field_1' => 'value_1_1',
				'field_2' => 'value_2_1',
			),
			array(
				'field_1' => 'value_1_2',
				'field_2' => 'value_2_2',
			),
		);
		$dataProvider = new CArrayDataProvider($testRawData);
		$csvExporter->exportDataProvider($dataProvider);

		$testArchiveFileName = self::getTestFilePath().DIRECTORY_SEPARATOR.'archive_'.getmypid().'.tar';
		$this->assertTrue($csvExporter->archiveExportFiles($testArchiveFileName), 'Unable to archive export files!');

		$this->assertTrue(file_exists($testArchiveFileName), 'No archive file has been created!');
	}

	/**
	 * @depends testExportDataProvider
	 */
	public function testDeleteExportFiles() {
		$csvExporter = $this->createCsvExporter();

		$testRawData = array(
			array(
				'field_1' => 'value_1_1',
				'field_2' => 'value_2_1',
			),
			array(
				'field_1' => 'value_1_2',
				'field_2' => 'value_2_2',
			),
		);
		$dataProvider = new CArrayDataProvider($testRawData);
		$csvExporter->exportDataProvider($dataProvider);

		$exportFiles = $csvExporter->getExportFiles();

		$this->assertTrue($csvExporter->deleteExportFiles(), 'Unable to delete export files!');
		foreach ($exportFiles as $exportFile) {
			$this->assertFalse(file_exists($exportFile), 'Unable to actually delete export file!');
		}
		$this->assertEmpty($csvExporter->getExportFiles(), 'Export files list is not empty!');
	}
}
