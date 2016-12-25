<?php
/**
 * QsFileArchiverConsoleCommand class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * QsFileArchiverConsoleCommand is a base class for the all file archivers,
 * which use the shell console commands to run the pack/unpack process.
 *
 * @property string $packCommandName public alias of {@link _packCommandName}.
 * @property string $unpackCommandName public alias of {@link _unpackCommandName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.archivers
 */
abstract class QsFileArchiverConsoleCommand extends QsFileArchiver {
	/**
	 * @var string name (or path and name) of the command,
	 * which should pack files into the archive.
	 */
	protected $_packCommandName = '';
	/**
	 * @var string name (or path and name) of the command,
	 * which should unpack archive files.
	 */
	protected $_unpackCommandName = '';

	// Set / Get :

	public function setPackCommandName($packCommandName) {
		if (!is_string($packCommandName)) {
			throw new CException('"' . get_class($this) . '::packCommandName" should be a string!');
		}
		$this->_packCommandName = $packCommandName;
		return true;
	}

	public function getPackCommandName() {
		return $this->_packCommandName;
	}

	public function setUnpackCommandName($unpackCommandName) {
		if (!is_string($unpackCommandName)) {
			throw new CException('"' . get_class($this) . '::unpackCommandName" should be a string!');
		}
		$this->_unpackCommandName = $unpackCommandName;
		return true;
	}

	public function getUnpackCommandName() {
		return $this->_unpackCommandName;
	}

	/**
	 * Executes shell console command.
	 * @param string $commandName - shell command name
	 * @param array $params - console command parameters.
	 * @return array - command output lines.
	 */
	protected function executeConsoleCommand($commandName, array $params = array()) {
		$consoleCommandString = $this->composeConsoleCommand($commandName, $params);
		$this->log("Execute command: {$consoleCommandString}");
		exec($consoleCommandString, $output);
		$this->log("Command output: " . implode("\n", $output));
		return $this->tryConsoleOutputError($output);
	}

	/**
	 * Composes shell console command string.
	 * @param string $commandName - shell command name
	 * @param array $params - console command parameters.
	 * @return string - command string.
	 */
	protected function composeConsoleCommand($commandName, array $params = array()) {
		$consoleCommandString = "{$commandName} ";
		$consoleCommandString .= ' ' . $this->composeConsoleCommandParams($params);
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
		foreach ($params as $paramKey=>$paramValue) {
			if (is_numeric($paramKey)) {
				$consoleCommandParts[] = $paramValue;
			} else {
				$consoleCommandParts[] = "-{$paramKey} " . escapeshellarg($paramValue);
			}
		}
		return implode(' ', $consoleCommandParts);
	}

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
		return $this->executeConsoleCommand($this->getPackCommandName(), $params);
	}

	/**
	 * Unpacks the given archive file to the specified path.
	 * @param string $sourceFileName - source archive file name
	 * @param string $outputPath - output files path.
	 * @return boolean success.
	 */
	public function unpack($sourceFileName, $outputPath) {
		$this->log("Unpack '{$sourceFileName}' into '{$outputPath}'");
		$params = $this->determineUnpackConsoleCommandParams($sourceFileName, $outputPath);
		return $this->executeConsoleCommand($this->getUnpackCommandName(), $params);
	}

	/**
	 * Checks the console command output for any error messages.
	 * @throws CException on fail.
	 * @param array $consoleCommandOutputRows - console output rows.
	 * @return boolean success.
	 */
	protected function tryConsoleOutputError(array $consoleCommandOutputRows) {
		$consoleCommandOutput = implode("\n", $consoleCommandOutputRows);
		$consoleCommandOutput .= "\n";

		// Check if command not found:
		if (stripos($consoleCommandOutput, 'command not found') !== false) {
			$composedErrorMessage = trim($consoleCommandOutput, "\n");
			$this->log($composedErrorMessage, CLogger::LEVEL_ERROR);
			throw new CException($composedErrorMessage);
		}

		// Search for error message:
		$error = '';
		if (preg_match('/error\s*(.+)\n/im', $consoleCommandOutput, $matches)) {
			$error = trim($matches[0]);
		}

		if (!empty($error)) {
			$composedErrorMessage = "Unable to perform archive command: {$error}";
			$this->log($composedErrorMessage, CLogger::LEVEL_ERROR);
			throw new CException($composedErrorMessage);
		}
		return true;
	}

	/**
	 * Determines the pack console command params.
	 * @param string $sourcePath - source files path.
	 * @param string $outputFileName - output archive file name
	 * @return array pack console command params set.
	 */
	abstract protected function determinePackConsoleCommandParams($sourcePath, $outputFileName);

	/**
	 * Determines the unpack console command params.
	 * @param string $sourceFileName - source archive file name
	 * @param string $outputPath - output files path.
	 * @return array pack console command params set.
	 */
	abstract protected function determineUnpackConsoleCommandParams($sourceFileName, $outputPath);
}
