<?php
/**
 * IQsFileArchiver interface file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * IQsFileArchiver is an interface for the all file archivers.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
interface IQsFileArchiver {
	/**
	 * Packs the files at the given path into the
	 * single archive file.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return boolean success.
	 */
	public function pack($sourcePath, $outputFileName);

	/**
	 * Unpacks the given archive file to the specified path.
	 * @param string $sourceFileName - source archive file name
	 * @param string $outputPath - output files path.
	 * @return boolean success.
	 */
	public function unpack($sourceFileName, $outputPath);
}
