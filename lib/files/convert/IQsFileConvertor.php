<?php
/**
 * IQsFileConvertor interface file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * IQsFileConvertor is an interface for the all file convertors.
 * File convertor converts files from one format to another.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.convert
 */
interface IQsFileConvertor {
	/**
	 * Sets up the default convert options.
	 * @param array $defaultOptions default convert options.
	 * @return boolean success.
	 */
	public function setDefaultOptions(array $defaultOptions);

	/**
	 * Returns the default convert options.
	 * @return array default convert options
	 */
	public function getDefaultOptions();

	/**
	 * Converts the file according to the given options.
	 * @param string $srcFileName - source full file name.
	 * @param string $outputFileName - output full file name.
	 * @param array $options - convert options.
	 * @return boolean - success.
	 */
	public function convert($srcFileName, $outputFileName, array $options = array());

	/**
	 * Retrieves the information about the file.
	 * The returned data set may vary for the different convertors.
	 * @param string $fileName - full file name.
	 * @return array - list of file parameters.
	 */
	public function getFileInfo($fileName);
}