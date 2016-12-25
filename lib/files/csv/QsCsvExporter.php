<?php
/**
 * QsCsvExporter class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.files.csv.QsCsvFile');

/**
 * QsCsvExporter is a helper, which allows export of the large data sets into CSV format.
 * Such ability granted by usage of {@link CDataProvider} instances with the pagination.
 * In result the component produces a list of CSV files, which can be empty, have one file or several files.
 * The result files list can be accessed via {@link exportFiles} and can be composed into single archive file,
 * using {@link archiveExportFiles()} method.
 *
 * Usage example:
 * <code>
 * $csvExporter = new QsCsvExporter();
 * $dataProvider = new CActiveDataProvider('Item', array('pagination' => array('pageSize' => 100)));
 * $csvExporter->exportDataProvider($dataProvider);
 * $csvExporter->output();
 * </code>
 *
 * Note: in order to adjust the CSV files options use {@link csvFileConfig}.
 * For example:
 * <code>
 * $csvExporter = new QsCsvExporter();
 * $csvExporter->setCsvFileConfig(
 *     array(
 *           'maxEntriesCount' => QsCsvFile::MAX_ENTRIES_COUNT_MS_EXCEL,
 *           'rowDelimiter' => "\r\n",
 *           'cellDelimiter' => ";",
 *     )
 * );
 * $dataProvider = new CActiveDataProvider('Item', array('pagination' => array('pageSize' => 100)));
 * $csvExporter->exportDataProvider($dataProvider);
 * $csvExporter->output();
 * </code>
 *
 * @see QsCsvFile
 * @see CDataProvider
 *
 * @property array $csvFileConfig public alias of {@link _csvFileConfig}.
 * @property array $exportFiles public alias of {@link _exportFiles}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.csv
 */
class QsCsvExporter extends CComponent {
	/**
	 * @var array configuration of the CSV file instance.
	 * @see QsCsvFile
	 */
	protected $_csvFileConfig = array();
	/**
	 * @var array export full file names.
	 */
	protected $_exportFiles = array();
	/**
	 * @var string name of the application component, which should be used to pack
	 * export files into the archive file.
	 * @see IQsFileArchiver
	 */
	public $fileArchiverApplicationComponentName = 'fileArchiver';
	/**
	 * @var string default archive file extension.
	 */
	public $archiveFileExtension = 'tar.gz';

	public function setCsvFileConfig(array $csvFileConfig) {
		$this->_csvFileConfig = $csvFileConfig;
		return true;
	}

	public function getCsvFileConfig() {
		return $this->_csvFileConfig;
	}

	public function getExportFiles() {
		return $this->_exportFiles;
	}

	/**
	 * Logs a message.
	 * @see CLogRouter
	 * @param string $message message to be logged.
	 * @param string $level level of the message (e.g. 'trace', 'warning', 'error'). It is case-insensitive.
	 * @return boolean success.
	 */
	protected function log($message, $level = CLogger::LEVEL_INFO) {
		$category = 'qs.files.csv.' . get_class($this);
		Yii::log($message, $level, $category);
		return true;
	}

	/**
	 * Creates new CSV file.
	 * @return QsCsvFile CSV file instance.
	 */
	protected function newCsvFile() {
		$csvFileConfig = $this->getCsvFileConfig();
		if (!array_key_exists('class', $csvFileConfig)) {
			$csvFileConfig['class'] = 'QsCsvFile';
		}
		$csvFileName = Yii::app()->name . '_' . str_pad((count($this->getExportFiles()) + 1), 3, '0', STR_PAD_LEFT);
		$csvFileConfig['fileName'] = $csvFileName;
		$csvFile = Yii::createComponent($csvFileConfig);
		$csvFileFullName = $csvFile->getFullFileName();
		$this->_exportFiles[] = $csvFileFullName;
		$this->log('New CSV file created at "' . $csvFileFullName . '".');
		return $csvFile;
	}

	/**
	 * Exports given data provider data into CSV.
	 * @param CDataProvider $dataProvider data provider.
	 * @throws CException on failure
	 * @return boolean success.
	 */
	public function exportDataProvider(CDataProvider $dataProvider) {
		$this->log('Export started.');
		$csvFile = null;
		$pageNumber = 0;
		while (true) {
			$dataProvider->getPagination()->setCurrentPage($pageNumber);
			$rows = $dataProvider->getData(true);
			$pageCount = $dataProvider->getPagination()->getPageCount();
			$this->log('Processing page ' . ($pageNumber + 1) . ' of ' . $pageCount . '.');
			foreach ($rows as $row) {
				if (!is_object($csvFile)) {
					$csvFile = $this->newCsvFile();
				}
				try {
					$csvFile->writeRow($row);
				} catch (CException $exception) {
					if ($csvFile->isMaxEntriesLimitReached()) {
						$csvFile->close();
						$csvFile = null;
						continue;
					} else {
						throw $exception;
					}
				}
				// Ensure garbage collection:
				if (is_object($row) && is_a($row, 'CComponent')) {
					$row->detachBehaviors();
				}
			}
			$pageNumber++;
			if ($pageNumber>=$pageCount) {
				if (is_object($csvFile)) {
					$csvFile->close();
				}
				break;
			}
		}
		$this->log('Export finished.');
		return true;
	}

	/**
	 * Pack the created export files into archive file.
	 * @param string $archiveFileName output archive file name.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	public function archiveExportFiles($archiveFileName) {
		$exportFiles = $this->getExportFiles();
		if (empty($exportFiles)) {
			throw new CException('There is no export file for archiving.');
		}
		
		// All files at the same directory without any extra files:
		$archivePath = '';
		$exportFilesAreAtSameDir = false;
		foreach ($exportFiles as $exportFile) {
			$exportFileDir = dirname($exportFile);
			if (empty($archivePath)) {
				$archivePath = $exportFileDir;
				$exportFilesAreAtSameDir = true;
			} else {
				if (!strcmp($archivePath, $exportFileDir)===0) {
					$exportFilesAreAtSameDir = false;
					break;
				}
			}
		}
		if ($exportFilesAreAtSameDir) {
			$filesAtArchivePath = CFileHelper::findFiles($archivePath);
			if (count($filesAtArchivePath) == count($exportFiles)) {
				return $this->archivePath($archivePath, $archiveFileName);
			}
		}

		// Use temporary directory:
		$tmpDirectory = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . get_class($this) . uniqid('_tmp_', true);
		if (!file_exists($tmpDirectory)) {
			mkdir($tmpDirectory, 0777, true);
		}
		foreach ($exportFiles as $exportFile) {
			$tmpFile = $tmpDirectory . DIRECTORY_SEPARATOR . basename($exportFile);
			copy($exportFile, $tmpFile);
		}
		$result = $this->archivePath($tmpDirectory, $archiveFileName);
		exec('rm -rf ' . escapeshellarg($tmpDirectory));
		return $result;
	}

	/**
	 * Pack the files at given path into archive file.
	 * @param string $filePath source files path.
	 * @param string $archiveFileName output archive file name.
	 * @return boolean success.
	 */
	protected function archivePath($filePath, $archiveFileName) {
		if (Yii::app()->hasComponent($this->fileArchiverApplicationComponentName)) {
			return $this->archivePathByApplicationComponent($filePath, $archiveFileName);
		} else {
			return $this->archivePathInternal($filePath, $archiveFileName);
		}
	}

	/**
	 * Pack the files at given path into archive file, using application component,
	 * specified by {@link fileArchiverApplicationComponentName}.
	 * @param string $filePath source files path.
	 * @param string $archiveFileName output archive file name.
	 * @return boolean success.
	 */
	protected function archivePathByApplicationComponent($filePath, $archiveFileName) {
		$archiverComponent = Yii::app()->getComponent($this->fileArchiverApplicationComponentName);
		return $archiverComponent->pack($filePath, $archiveFileName);
	}

	/**
	 * Pack the files at given path into archive file, using internal resources.
	 * @param string $filePath source files path.
	 * @param string $archiveFileName output archive file name.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	protected function archivePathInternal($filePath, $archiveFileName) {
		$shellCommand = 'tar -zcvf ' . escapeshellarg($archiveFileName) . ' -C ' . escapeshellarg(dirname($filePath)) . ' ' . escapeshellarg(basename($filePath));
		exec($shellCommand);
		if (!file_exists($archiveFileName)) {
			throw new CException('Unable to create archive "' . $archiveFileName . '" from "' . $filePath . '".');
		}
		return true;
	}

	/**
	 * Deletes all export files.
	 * @return boolean success.
	 */
	public function deleteExportFiles() {
		foreach ($this->getExportFiles() as $exportFile) {
			if (file_exists($exportFile)) {
				unlink($exportFile);
			}
		}
		$this->_exportFiles = array();
		return true;
	}

	/**
	 * Sends a result file to user.
	 * If more then one export file has been created all export files will be returned as archive.
	 * Note: this method will trigger "CApplication::end()", even if $terminate parameter is set to false.
	 * @param boolean $deleteExportFiles indicates if exported files should be deleted afterwards.
	 * @param boolean $terminate whether to terminate the current application after calling this method
	 * @throws CException on failure.
	 */
	public function output($deleteExportFiles=true, $terminate=true) {
		$exportFiles = $this->getExportFiles();
		if (empty($exportFiles)) {
			throw new CException('There is no export file.');
		}

		if (count($exportFiles)==1) {
			$fullFileName = array_shift($exportFiles);
		} else {
			$archiveSelfFileName = uniqid('tmp_'/*,true*/) . '.' . $this->archiveFileExtension;
			$archiveFileName = Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . $archiveSelfFileName;
			$fullFileName = $archiveFileName;
			$this->archiveExportFiles($fullFileName);
		}
		
		$this->sendFile($fullFileName);

		if ($deleteExportFiles) {
			$this->deleteExportFiles();
		}
		if (isset($archiveFileName) && file_exists($archiveFileName)) {
			unlink($archiveFileName);
		}
		if ($terminate) {
			exit(0);
		}
	}

	/**
	 * Sends a file to user.
	 * @param string $fullFileName full file name.
	 */
	protected function sendFile($fullFileName) {
		$fileName = basename($fullFileName);
		$fileContent = file_get_contents($fullFileName);
		Yii::app()->getRequest()->sendFile($fileName, $fileContent, null, false);
	}
}
