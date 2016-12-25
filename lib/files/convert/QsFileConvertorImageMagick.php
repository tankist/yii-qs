<?php
/**
 * QsFileConvertorImageMagick class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsFileConvertorImageMagick is image file formats convertor based on the
 * ImageMagick tool.
 * This file convertor is a wrapper for the ImageMagic console commands.
 * Remember: you should have ImageMagick installed at your server.
 *
 * Recommended default convert options:
 * <code>
 * array(
 *     '-strip',
 *     'colorspace' => 'rgb',
 * );
 * </code>
 *
 * @see http://www.imagemagick.org/
 * @see http://www.imagemagick.org/script/command-line-processing.php
 *
 * @property string $binPath public alias of {@link _binPath}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.convert
 */
class QsFileConvertorImageMagick extends QsFileConvertor {
	/**
	 * @var string path in the directory, which stores ImageMagick binaries.
	 * For example: '/usr/local/bin'.
	 * This path will be used to compose console command name.
	 * You may leave this field blank if ImageMagick commands are available in OS shell.
	 */
	protected $_binPath = '';

	// Set / Get :

	public function setBinPath($binPath) {
		if (!is_string($binPath)) {
			throw new CException('"' . get_class($this) . '::binPath" should be a string!');
		}
		$this->_binPath = $binPath;
		return true;
	}

	public function getBinPath() {
		return $this->_binPath;
	}

	/**
	 * Executes shell console command.
	 * @param string $commandName - console command name.
	 * @param array $params - console command parameters.
	 * @return array - command output lines.
	 */
	protected function executeConsoleCommand($commandName, array $params = array()) {
		$consoleCommandString = $this->composeConsoleCommand($commandName, $params);
		$this->log("Execute command: {$consoleCommandString}");
		exec($consoleCommandString, $output);
		$this->log('Command output: ' . implode("\n", $output));
		return $output;
	}

	/**
	 * Composes shell console command string.
	 * @param string $commandName - console command name.
	 * @param array $params - console command parameters.
	 * @return string - command string.
	 */
	protected function composeConsoleCommand($commandName, array $params = array()) {
		$binPath = rtrim($this->getBinPath(), DIRECTORY_SEPARATOR);
		if (!empty($binPath)) {
			$commandFullName = $binPath . DIRECTORY_SEPARATOR . $commandName;
		} else {
			$commandFullName = $commandName;
		}
		$consoleCommandString = "{$commandFullName} ";
		$consoleCommandString .= ' ' . $this->composeConsoleCommandParams($params);
		$consoleCommandString .= ' 2>&1';
		return $consoleCommandString;
	}

	/**
	 * Composes console command params into a string,
	 * which is suitable to be passed to console.
	 * @param array $params - console command parameters.
	 * @return string - command params part.
	 */
	protected function composeConsoleCommandParams(array $params) {
		$consoleCommandParts = array();
		foreach ($params as $paramKey => $paramValue) {
			if (is_numeric($paramKey)) {
				$consoleCommandParts[] = $paramValue;
			} else {
				$consoleCommandParts[] = "-{$paramKey} " . escapeshellarg($paramValue);
			}
		}
		return implode(' ', $consoleCommandParts);
	}

	/**
	 * Converts the file according to the given options.
	 * @param string $srcFileName - source full file name.
	 * @param string $outputFileName - output full file name.
	 * @param array $options - convert options.
	 * @return boolean - success.
	 */
	public function convert($srcFileName, $outputFileName, array $options = array()) {
		$options = $this->composeOptions($options);

		if (array_key_exists('resize', $options)) {
			if (is_array($options['resize'])) {
				$dimensions = array_slice($options['resize'], 0, 2);
				$resizeModifiers = array_slice($options['resize'], 2);
				$resizeOption = implode('x', array_values($dimensions));
				if (!empty($resizeModifiers)) {
					$resizeOption .= implode($resizeModifiers);
				} else {
					$resizeOption .= '^';
				}
				$options['resize'] = $resizeOption;
			}
		}

		$consoleCommandParams = array(
			escapeshellarg($srcFileName)
		);
		$consoleCommandParams = array_merge($consoleCommandParams, $options);

		$consoleCommandParams[] = escapeshellarg($outputFileName);
		$this->executeConsoleCommand('convert', $consoleCommandParams);
		return true;
	}

	/**
	 * Retrieves the information about the file.
	 * The returned data set may vary for the different convertors.
	 * @param string $fileName - full file name.
	 * @return array - list of file parameters.
	 */
	public function getFileInfo($fileName) {
		$consoleCommandParams = array(
			'-verbose',
			escapeshellarg($fileName)
		);
		$consoleOutputLines = $this->executeConsoleCommand('identify', $consoleCommandParams);
		return $this->parseFileInfo($consoleOutputLines);
	}

	/**
	 * Parse console command output in order to compose file info.
	 * @param array $consoleOutputLines - console command output lines.
	 * @return array file info data.
	 */
	protected function parseFileInfo(array $consoleOutputLines) {
		$consoleOutput = implode("\n", $consoleOutputLines);
		if (count($consoleOutputLines) <= 1 || stripos($consoleOutput, '@ error') !== false) {
			throw new CException($consoleOutput);
		}

		$fileInfoString = trim($consoleOutput);
		$fileInfo = array();

		if (preg_match_all('/^([a-z0-9 ]+):(.*)$/im', $fileInfoString, $matches)) {
			foreach ($matches[1] as $paramKey => $paramName) {
				$paramName = trim($paramName);
				$paramValue = $matches[2][$paramKey];
				$fileInfo[$paramName] = $paramValue;
			}

			if (array_key_exists('Geometry',$fileInfo)) {
				if (preg_match('/([0-9]+)x([0-9]+)/is', $fileInfo['Geometry'], $matches)) {
					$imageWidth = $matches[1];
					$imageHeight = $matches[2];
					$fileInfo['imageSize'] = array(
						'width' => $imageWidth,
						'height' => $imageHeight,
					);
				}
			}
		}

		return $fileInfo;
	}

	/**
	 * Performs the file conversion with image resizing.
	 * This is a shortcut method for the {@link convert} with option 'resize'.
	 * @param string $srcFileName - source full file name.
	 * @param string $outputFileName - output full file name.
	 * @param integer $width - width of the output image.
	 * @param integer $height - height of the output image.
	 * @param array $options - additional convert options.
	 * @return boolean - success.
	 */
	public function resize($srcFileName, $outputFileName, $width, $height, array $options = array()) {
		$options['resize'] = array(
			$width,
			$height
		);
		return $this->convert($srcFileName, $outputFileName, $options);
	}
}
