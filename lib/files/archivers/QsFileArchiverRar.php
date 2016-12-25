<?php
/**
 * QsFileArchiverRar class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsFileArchiverRar is a file archiver for the ZIP format.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
class QsFileArchiverRar extends QsFileArchiverConsoleCommand {
	/**
	 * @var string name (or path and name) of the command,
	 * which should pack files into the archive.
	 */
	protected $_packCommandName = 'rar';
	/**
	 * @var string name (or path and name) of the command,
	 * which should unpack archive files.
	 */
	protected $_unpackCommandName = 'rar x';

	/**
	 * Determines the pack console command params.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return array pack console command params set.
	 */
	protected function determinePackConsoleCommandParams($sourcePath, $outputFileName) {
		$consoleCommandParams = array();
		$consoleCommandParams[] = 'a';
		$consoleCommandParams[] = '-m5';
		//$consoleCommandParams[] = '-1024';
		$consoleCommandParams[] = '-r';
		$consoleCommandParams[] = '-ep1';
		$consoleCommandParams[] = escapeshellarg($outputFileName);
		$consoleCommandParams[] = escapeshellarg($sourcePath);
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
		$consoleCommandParams[] = escapeshellarg($outputPath);
		return $consoleCommandParams;
	}
}
