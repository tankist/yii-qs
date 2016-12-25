<?php
/**
 * QsCsvFile class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsCsvFile represents the CSV file.
 * This class can be used to export the data into the CSV file, using any custom formatting
 * and tracking the entries per file restriction.
 * This class allows to write data into the file row by row.
 * Single row data can be either an array or an object convertable to array.
 * 
 * Example:
 * <code>
 * $csvFile = new QsCsvFile();
 * $items = Item::model()->findAll();
 * foreach ($items as $item) {
 *     $csvFile->writeRow($item);
 * }
 * $csvFile->close();
 * $createdCsvFileName = $csvFile->getFullFileName();
 * Yii::app()->getRequest()->sendFile(basename($createdCsvFileName), file_get_contents($createdCsvFileName), 'text/csv', false);
 * $csvFile->delete();
 * exit(0);
 * </code>
 *
 * @property string $fileName public alias of {@link _fileName}.
 * @property string $baseFilePath public alias of {@link _baseFilePath}.
 * @property integer $entriesCount public alias of {@link _entriesCount}.
 * @property array $columnNames public alias of {@link _columnNames}.
 * @property array $columnHeaders public alias of {@link _columnHeaders}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.csv
 */
class QsCsvFile extends CComponent {
	const MAX_ENTRIES_COUNT_MS_EXCEL = 1048576; // MS Excel 2007
	const MAX_ENTRIES_COUNT_MS_EXCEL_OLD = 65536; // MS Excel 97-2003
	const MAX_ENTRIES_COUNT_OPEN_OFFICE = 65536; // Open office

	/**
	 * @var string name of the site map file without extension.
	 */
	protected $_fileName = '';
	/**
	 * @var string file extension.
	 */
	public $fileExtension = 'csv';
	/**
	 * @var integer the chmod permission for directories and files,
	 * created in the process. Defaults to 0777 (owner rwx, group rwx and others rwx).
	 */
	public $filePermissions = 0777;
	/**
	 * @var string directory, which should be used to store generated CSV file.
	 * By default 'application.runtime.QsCsvFile' will be used.
	 */
	protected $_baseFilePath = '';
	/**
	 * @var resource file resource handler.
	 */
	protected $_fileHandler = null;
	/**
	 * @var integer the count of entries written into the file.
	 */
	protected $_entriesCount = 0;
	/**
	 * @var integer the maximum entries count allowed in this file.
	 * If this value is equal or less than zero, no limit checking will be performed.
	 */
	public $maxEntriesCount = self::MAX_ENTRIES_COUNT_OPEN_OFFICE;
	/**
	 * @var string delimiter between the CSV file rows.
	 */
	public $rowDelimiter = "\r\n";
	/**
	 * @var string delimiter between the CSV file cells.
	 */
	public $cellDelimiter = ',';
	/**
	 * @var string the cell content enclosure.
	 */
	public $enclosure = '"';
	/**
	 * @var array list of the columns, which should be actually written into the file.
	 * You can setup this parameter to filter or force the saving of certain data keys.
	 * For example:
	 * <code>
	 * array(
	 *     'name',
	 *     'email'
	 * );
	 * </code>
	 */
	protected $_columnNames = array();
	/**
	 * @var array list of headers for the CSV columns.
	 * For example:
	 * <code>
	 * array(
	 *     'name' => 'User name',
	 *     'email' => 'User email address'
	 * );
	 * </code>
	 */
	protected $_columnHeaders = array();
	/**
	 * @var boolean indicates if column headers should be generated automatically.
	 * This option will be affected only if {@link columnHeaders} are empty.
	 */
	public $autoComposeColumnHeaders = true;
	/**
	 * @var boolean indicates if column headers have been already inserted into the file.
	 * This field is for internal usage only.
	 */
	protected $_isColumnHeadersInserted = false;

	/**
	 * Destructor.
	 * Makes sure the opened file is closed.
	 */
	public function __destruct() {
		$this->close();
	}

	public function setFileName($fileName) {
		$this->_fileName = $fileName;
		return true;
	}

	public function getFileName() {
		if (empty($this->_fileName)) {
			$this->initFileName();
		}
		return $this->_fileName;
	}

	public function setBaseFilePath($baseFilePath) {
		$this->_baseFilePath = $baseFilePath;
		return true;
	}

	public function getBaseFilePath() {
		if (empty($this->_baseFilePath)) {
			$this->initBaseFilePath();
		}
		return $this->_baseFilePath;
	}

	public function getEntriesCount() {
		return $this->_entriesCount;
	}

	public function setColumnNames(array $columnNames) {
		$this->_columnNames = $columnNames;
		return true;
	}

	public function getColumnNames() {
		return $this->_columnNames;
	}

	public function setColumnHeaders(array $columnHeaders) {
		$this->_columnHeaders = $columnHeaders;
		return true;
	}

	public function getColumnHeaders() {
		return $this->_columnHeaders;
	}

	/**
	 * Increments the internal entries count.
	 * @throws CException if limit exceeded.
	 * @return integer new entries count value.
	 */
	protected function incrementEntriesCount() {
		if ($this->isMaxEntriesLimitReached()) {
			throw new CException('Entries count exceeds limit of "' . $this->maxEntriesCount . '".');
		}
		$this->_entriesCount++;
		return $this->_entriesCount;
	}

	/**
	 * Checks if max entries limit is reached.
	 * @return boolean limit is reached.
	 */
	public function isMaxEntriesLimitReached() {
		return ($this->maxEntriesCount > 0 && $this->_entriesCount >= $this->maxEntriesCount);
	}

	/**
	 * Returns the full file name.
	 * @return string full file name.
	 */
	public function getFullFileName() {
		return $this->getBaseFilePath() . DIRECTORY_SEPARATOR . $this->getFileName() . '.' . $this->fileExtension;
	}

	/**
	 * Initializes the {@link fileName} value using current process id.
	 * @return boolean success.
	 */
	protected function initFileName() {
		$this->_fileName = uniqid('csv_file_', true);
		return true;
	}

	/**
	 * Initializes the {@link baseFilePath} with the default value.
	 * @return boolean success.
	 */
	protected function initBaseFilePath() {
		$this->_baseFilePath = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . get_class($this) . DIRECTORY_SEPARATOR . 'csv';
		return true;
	}

	/**
	 * Resolves given file path, making sure it exists and writeable.
	 * @throws CException on failure.
	 * @param string $path file path.
	 * @return boolean success.
	 */
	protected function resolvePath($path) {
		if (!file_exists($path)) {
			$dirPermission = $this->filePermissions;
			$oldUmask = umask(0);
			@mkdir($path, $dirPermission, true);
			umask($oldUmask);
		}
		if (!file_exists($path) || !is_dir($path)) {
			throw new CException("Unable to resolve path: '{$path}'!");
		} elseif (!is_writable($path)) {
			throw new CException("Path: '{$path}' should be writeable!");
		}
		return true;
	}

	/**
	 * Opens the related file for writing.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	public function open() {
		if ($this->_fileHandler === null) {
			$this->resolvePath(dirname($this->getFullFileName()));
			$this->_fileHandler = fopen($this->getFullFileName(), 'w+');
			if ($this->_fileHandler === false) {
				throw new CException('Unable to create/open file "' . $this->getFullFileName() . '".');
			}
			$this->_entriesCount = 0;
		}
		return true;
	}

	/**
	 * Close the related file if it was opened.
	 * @return boolean success.
	 */
	public function close() {
		if ($this->_fileHandler) {
			fclose($this->_fileHandler);
			$this->_fileHandler = null;
		}
		return true;
	}

	/**
	 * Deletes the associated file.
	 * @return boolean success.
	 */
	public function delete() {
		$this->close();
		$fullFileName = $this->getFullFileName();
		if (file_exists($fullFileName)) {
			unlink($fullFileName);
		}
		return true;
	}

	/**
	 * Writes the given content into the file.
	 * @throws CException on failure.
	 * @param string $content content to be written.
	 * @return integer the number of bytes written.
	 */
	protected function writeContent($content) {
		$this->open();
		$this->incrementEntriesCount();
		$bytesWritten = fwrite($this->_fileHandler, $content);
		if ($bytesWritten===false) {
			throw new CException('Unable to write file "' . $this->getFullFileName() . '".');
		}
		return $bytesWritten;
	}

	/**
	 * Writes the given row data into the file in CSV format.
	 * @param mixed $rowData raw data can be array or object.
	 * @return integer the number of bytes written.
	 */
	public function writeRow($rowData) {
		$rowData = $this->convertRowData($rowData);
		$result = 0;
		if (!$this->_isColumnHeadersInserted) {
			$result += $this->writeColumnHeaders($rowData);
		}
		$content = $this->composeRowContent($rowData);
		$result += $this->writeContent($content);
		return $result;
	}

	/**
	 * Composes array set from the given raw data.
	 * @param mixed $rawRowData raw data can be array or object.
	 * @return array row data in array format.
	 */
	protected function convertRowData($rawRowData) {
		$columnNames = $this->getColumnNames();
		if (is_object($rawRowData)) {
			if (!empty($columnNames)) {
				$rowData = array();
				foreach ($columnNames as $columnName) {
					$rowData[$columnName] = $rawRowData->$columnName;
				}
			} else {
				if (is_a($rawRowData, 'CModel')) {
					$rowData = $rawRowData->getAttributes();
				} else {
					$rowData = (array)$rawRowData;
				}
			}
		} elseif (is_array($rawRowData)) {
			if (!empty($columnNames)) {
				$rowData = array();
				foreach ($columnNames as $columnName) {
					$rowData[$columnName] = array_key_exists($columnName, $rawRowData) ? $rawRowData[$columnName] : null;
				}
			} else {
				$rowData = $rawRowData;
			}
		} else {
			throw new CException('Wrong data type of the row data: array or object expected, but "' . gettype($rawRowData) . '" given.');
		}
		return $rowData;
	}

	/**
	 * Secures the given value so it can be written in CSV cell.
	 * @param string $value value to be secured
	 * @return mixed secured value.
	 */
	protected function secureValue($value) {
		$value = (string)$value;
		return str_replace($this->enclosure, str_repeat($this->enclosure, 2), $value);
	}

	/**
	 * Composes the given data into the CSV row.
	 * @param array $rowData data to be composed.
	 * @return string CSV format row
	 */
	protected function composeRowContent(array $rowData) {
		$securedRowData = array_map(array($this, 'secureValue'), $rowData);
		if ($this->_entriesCount > 0) {
			$rowContent = $this->rowDelimiter;
		} else {
			$rowContent = '';
		}
		$rowContent .= implode($this->cellDelimiter, $securedRowData);
		return $rowContent;
	}

	/**
	 * Writes the CSV file column headers.
	 * @param array $rowData data of first row to be inserted.
	 * @return integer the number of bytes written.
	 */
	protected function writeColumnHeaders(array $rowData) {
		$result = 0;
		$columnHeaders = $this->getColumnHeaders();
		if (empty($columnHeaders) && $this->autoComposeColumnHeaders) {
			$columnHeaders = array_keys($rowData);
		}
		if (!empty($columnHeaders)) {
			$content = $this->composeRowContent($columnHeaders);
			$result += $this->writeContent($content);
		}
		$this->_isColumnHeadersInserted = true;
		return $result;
	}
}
