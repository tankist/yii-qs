<?php
/**
 * QsFileArchiverTar class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsFileArchiverTar is a file archiver for the tar formats.
 * This archiver handles the following archive formats: *.tar. *.tbz
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
class QsFileArchiverTar extends QsFileArchiverConsoleCommand {
	/**
	 * @var string name (or path and name) of the command,
	 * which should pack files into the archive.
	 */
	protected $_packCommandName = 'tar';
	/**
	 * @var string name (or path and name) of the command,
	 * which should unpack archive files.
	 */
	protected $_unpackCommandName = 'tar';

	/**
	 * Determines the pack console command params.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return array pack console command params set.
	 */
	protected function determinePackConsoleCommandParams($sourcePath, $outputFileName) {
		$consoleCommandParams = array();

		$mainParam = 'cf';
		$archiveFileExtension = CFileHelper::getExtension($outputFileName);
		if (strcasecmp($archiveFileExtension, 'tar') !== 0) {
			$mainParam .= 'j';
		}
		$consoleCommandParams[] = $mainParam;
		$consoleCommandParams[] = escapeshellarg($outputFileName);
		$consoleCommandParams['C'] = dirname($sourcePath);
		$consoleCommandParams[] = escapeshellarg(basename($sourcePath));
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

		$mainParam = 'xvf';
		$consoleCommandParams[] = $mainParam;
		$consoleCommandParams[] = escapeshellarg($sourceFileName);
		$consoleCommandParams['C'] = $outputPath;
		return $consoleCommandParams;
	}
}
