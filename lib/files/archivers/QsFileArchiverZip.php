<?php
/**
 * QsFileArchiverZip class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsFileArchiverZip is a file archiver for the ZIP format.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
class QsFileArchiverZip extends QsFileArchiverConsoleCommand {
	/**
	 * @var string name (or path and name) of the command,
	 * which should pack files into the archive.
	 */
	protected $_packCommandName = 'zip';
	/**
	 * @var string name (or path and name) of the command,
	 * which should unpack archive files.
	 */
	protected $_unpackCommandName = 'unzip';

	/**
	 * Packs the files at the given path into the
	 * single archive file.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return boolean success.
	 */
	public function pack($sourcePath, $outputFileName) {
		$this->log("Pack '{$sourcePath}' into '{$outputFileName}'");
		$params = $this->determinePackConsoleCommandParams($sourcePath, $outputFileName);
		$packCommandName = $this->getPackCommandName();
		$this->setPackCommandName('');
		try {
			$result = $this->executeConsoleCommand($this->getPackCommandName(), $params);
		} catch (Exception $exception) {
			$this->setPackCommandName($packCommandName);
			throw $exception;
		}
		return $result;
	}

	/**
	 * Determines the pack console command params.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return array pack console command params set.
	 */
	protected function determinePackConsoleCommandParams($sourcePath, $outputFileName) {
		$consoleCommandParams = array();
		$sourceBasePath = dirname($sourcePath);
		$sourceSelfName = basename($sourcePath);
		$mainCommand = '(cd ' . escapeshellarg($sourceBasePath) . '; ' . $this->getPackCommandName() . ' -r -q - ' . escapeshellarg($sourceSelfName) . ') > ' . escapeshellarg($outputFileName);
		$consoleCommandParams[] = $mainCommand;
		return $consoleCommandParams;
	}

	/**
	 * Determines the unpack console command params.
	 * @param string $sourceFileName - source archive file name
	 * @param string $outputPath - output files path.
	 * @return array pack console command params set.
	 */
	protected function determineUnpackConsoleCommandParams($sourceFileName, $outputPath) {
		$consoleCommandParams = array();
		$consoleCommandParams[] = escapeshellarg($sourceFileName);
		$consoleCommandParams['d'] = $outputPath;
		return $consoleCommandParams;
	}
}
