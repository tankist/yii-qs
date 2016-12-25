<?php
 
/**
 * Test case for the extension "qs.files.csv.QsCsvFile".
 * @see QsCsvFile
 */
class QsCsvFileTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.files.csv.QsCsvFile');
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
	 * Creates the CSV file instance.
	 * @return QsCsvFile
	 */
	protected function createCsvFile() {
		$csvFile = new QsCsvFile();
		$csvFile->setBaseFilePath(self::getTestFilePath());
		$csvFile->autoComposeColumnHeaders = false;
		return $csvFile;
	}

	/**
	 * Declares the test model class.
	 * @param string $className new class name.
	 * @return boolean success.
	 */
	protected function declareTestModelClass($className='TestModelClassName') {
		if (class_exists($className,false)) {
			return false;
		}
		$classDefinitionCode = <<<EOD
class {$className} extends CFormModel {
	public \$name;
	public \$value;
}
EOD;
		eval($classDefinitionCode);
		return true;
	}

	// Tests:

	public function testSetGet() {
		$csvFile = new QsCsvFile();

		$testFileName = 'test_file_name';
		$this->assertTrue($csvFile->setFileName($testFileName), 'Unable to set file name!');
		$this->assertEquals($testFileName, $csvFile->getFileName(), 'Unable to set file name correctly!');

		$testBaseFilePath = '/test/file/path';
		$this->assertTrue($csvFile->setBaseFilePath($testBaseFilePath), 'Unable to set base file path!');
		$this->assertEquals($testBaseFilePath, $csvFile->getBaseFilePath(), 'Unable to set base file path correctly!');

		$testColumnNames = array(
			'test_column_1',
			'test_column_2',
		);
		$this->assertTrue($csvFile->setColumnNames($testColumnNames), 'Unable to set column names!');
		$this->assertEquals($testColumnNames, $csvFile->getColumnNames(), 'Unable to set column names correctly!');

		$testColumnHeaders = array(
			'test_column_1' => 'Test Column 1',
			'test_column_2' => 'Test Column 2',
		);
		$this->assertTrue($csvFile->setColumnHeaders($testColumnHeaders), 'Unable to set columns headers!');
		$this->assertEquals($testColumnHeaders, $csvFile->getColumnHeaders(), 'Unable to set columns headers correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultBaseFilePath() {
		$csvFile = new QsCsvFile();

		$defaultBaseFilePath = $csvFile->getBaseFilePath();
		$this->assertNotEmpty($defaultBaseFilePath, 'Unable to get default base file path!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultFileName() {
		$csvFile = new QsCsvFile();

		$defaultFileName = $csvFile->getFileName();
		$this->assertNotEmpty($defaultFileName, 'Unable to get default file name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetFullFileName() {
		$csvFile = new QsCsvFile();

		$testFileBasePath = '/test/file/path';
		$csvFile->setBaseFilePath($testFileBasePath);
		$testFileName = 'test_file_name';
		$csvFile->setFileName($testFileName);

		$expectedFullFileName = $testFileBasePath.DIRECTORY_SEPARATOR.$testFileName.'.'.$csvFile->fileExtension;
		$fullFileName = $csvFile->getFullFileName();
		$this->assertEquals($expectedFullFileName, $fullFileName, 'Unable to get full file name correctly!');
	}

	/**
	 * @depends testGetFullFileName
	 */
	public function testWriteRow() {
		$csvFile = $this->createCsvFile();

		$testRowData = array(
			'test_value_1',
			'test_value_2',
		);
		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$fullFileName = $csvFile->getFullFileName();
		$this->assertTrue(file_exists($fullFileName), 'Unable to create a file!');

		$fileActualContent = file_get_contents($fullFileName);
		foreach ($testRowData as $cellData) {
			$this->assertContains($cellData, $fileActualContent, 'File does not contain the cell data!');
		}
	}

	/**
	 * @depends testWriteRow
	 */
	public function testDelete() {
		$csvFile = $this->createCsvFile();

		$testRowData = array(
			'test_value_1',
			'test_value_2',
		);
		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$csvFile->delete();

		$fullFileName = $csvFile->getFullFileName();
		$this->assertFalse(file_exists($fullFileName), 'Unable to delete a file!');
	}

	/**
	 * @depends testWriteRow
	 */
	public function testEntriesCountIncrement() {
		$csvFile = $this->createCsvFile();

		$originalEntriesCount = $csvFile->getEntriesCount();
		$this->assertEquals(0, $originalEntriesCount, 'Original entries count is wrong!');

		$testRowData = array(
			'test_value_1',
			'test_value_2',
		);
		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$postWriteEntriesCount = $csvFile->getEntriesCount();

		$this->assertEquals($originalEntriesCount+1, $postWriteEntriesCount, 'No entries count increment detected!');
	}

	/**
	 * @depends testEntriesCountIncrement
	 */
	public function testIsMaxEntriesLimitReached() {
		$csvFile = $this->createCsvFile();

		$testMaxEntriesCount = rand(5,10);
		$csvFile->maxEntriesCount = $testMaxEntriesCount;

		$testRowData = array(
			'test_value_1',
			'test_value_2',
		);
		$csvFile->writeRow($testRowData);

		$this->assertFalse($csvFile->isMaxEntriesLimitReached(), 'Max entries limit reached!');

		for ($i=2; $i<=$testMaxEntriesCount; $i++) {
			$csvFile->writeRow($testRowData);
		}

		$this->assertTrue($csvFile->isMaxEntriesLimitReached(), 'Max entries limit not reached!');

		$csvFile->close();
	}

	/**
	 * @depends testWriteRow
	 */
	public function testWriteRowModel() {
		$csvFile = $this->createCsvFile();

		$modelClassName = 'TestModelClassName';
		$this->declareTestModelClass($modelClassName);

		$testModel = new $modelClassName;
		$testModelName = 'test_model_name';
		$testModel->name = $testModelName;
				
		$csvFile->writeRow($testModel);
		$csvFile->close();

		$fullFileName = $csvFile->getFullFileName();
		$fileActualContent = file_get_contents($fullFileName);

		$this->assertContains($testModelName, $fileActualContent, 'File does not contain the model attribute value!');
	}

	/**
	 * @depends testWriteRow
	 */
	public function testWriteColumnHeaders() {
		$csvFile = $this->createCsvFile();

		$testColumnHeaders = array(
			'test_column_header_1',
			'test_column_header_2',
		);
		$csvFile->setColumnHeaders($testColumnHeaders);

		$testRowData = array();
		foreach ($testColumnHeaders as $index => $testColumnHeader) {
			$testRowData[] = 'test_value_'.$index;
		}

		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$fullFileName = $csvFile->getFullFileName();
		$fileActualContent = file_get_contents($fullFileName);
		foreach ($testColumnHeaders as $testColumnHeader) {
			$this->assertContains($testColumnHeader, $fileActualContent, 'File does not contain the column header!');
		}
	}

	/**
	 * @depends testWriteColumnHeaders
	 */
	public function testWriteAutomaticComposeColumnHeaders() {
		$csvFile = $this->createCsvFile();
		$csvFile->autoComposeColumnHeaders = true;

		$testColumnHeaders = array(
			'test_column_header_1',
			'test_column_header_2',
		);
		$testRowData = array();
		foreach ($testColumnHeaders as $index => $testColumnHeader) {
			$testRowData[$testColumnHeader] = 'test_value_'.$index;
		}

		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$fullFileName = $csvFile->getFullFileName();
		$fileActualContent = file_get_contents($fullFileName);
		foreach ($testColumnHeaders as $testColumnHeader) {
			$this->assertContains($testColumnHeader, $fileActualContent, 'File does not contain the column header!');
		}
	}

	/**
	 * @depends testWriteRow
	 */
	public function testWriteWithForceColumnNames() {
		$csvFile = $this->createCsvFile();

		$testAllowedColumnName = 'test_allowed_column_name';
		$testDisallowedColumnName = 'test_disallowed_column_name';

		$testColumnNames = array(
			$testAllowedColumnName
		);
		$csvFile->setColumnNames($testColumnNames);

		$testRowData = array(
			$testAllowedColumnName => $testAllowedColumnName.'_value',
			$testDisallowedColumnName => $testDisallowedColumnName.'_value',
		);
		$csvFile->writeRow($testRowData);
		$csvFile->close();

		$fullFileName = $csvFile->getFullFileName();

		$fileActualContent = file_get_contents($fullFileName);

		$this->assertContains($testRowData[$testAllowedColumnName], $fileActualContent, 'File does not contain the allowed column value!');
		$this->assertNotContains($testRowData[$testDisallowedColumnName], $fileActualContent, 'File does contain the disallowed column value!');
	}
}
