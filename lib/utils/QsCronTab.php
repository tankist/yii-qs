<?php
/**
 * QsCronTab class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsCronTab allows management of the cron jobs.
 *
 * Note: Each system user has his own crontab, make sure you always run this class
 * for the same user. For the web application it is usually 'apache', for the console
 * application - current local user or root.
 *
 * Example usage:
 * <code>
 * $cronTab = new QsCronTab();
 * $cronTab->setJobs(array(
 *     array(
 *         'min' => '0',
 *         'hour' => '0',
 *         'command' => 'php /path/to/project/protected/yiic somecron',
 *     ),
 *     array(
 *         'line' => '0 0 * * * php /path/to/project/protected/yiic anothercron'
 *     )
 * ));
 * $cronTab->apply();
 * </code>
 *
 * @see QsCronJob
 * @see http://en.wikipedia.org/wiki/Crontab
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.utils
 */
class QsCronTab extends CComponent {
	/**
	 * @var array shell command availability checks results in format: command => availability
	 */
	protected static $_shellCommandAvailabilities = array();
	/**
	 * @var string path to the directory, which holds 'crontab' command, for example: '/usr/bin'.
	 * You may leave this field blank if 'crontab' commands is available in OS shell.
	 */
	public $binPath;
	/**
	 * @var QsCronJob[]|array[] list of {@link QsCronJob} instances or their array configurations.
	 */
	protected $_jobs = array();
	/**
	 * @var string default class name of the cron job objects.
	 */
	public $defaultJobClass = 'qs.utils.QsCronJob';

	/**
	 * Checks if given shell command available.
	 * This function caches the check results.
	 * @param string $commandName command name.
	 * @return boolean whether command is available.
	 */
	protected static function checkShellCommandAvailable($commandName) {
		if (array_key_exists($commandName, self::$_shellCommandAvailabilities)) {
			$result = self::$_shellCommandAvailabilities[$commandName];
		} else {
			$output = array();
			@$output = exec('which ' . $commandName . ' 2>/dev/null', $output);
			$result = (!empty($output));
			self::$_shellCommandAvailabilities[$commandName] = $result;
		}
		return $result;
	}

	/**
	 * @param array $jobs
	 * @return QsCronTab self reference
	 */
	public function setJobs($jobs) {
		$this->_jobs = $jobs;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getJobs() {
		return $this->_jobs;
	}

	/**
	 * Composes the full crontab command name.
	 * @throws CException if command is not available in shell.
	 * @return string crontab command full name.
	 */
	public function getCronTabCommandFullName() {
		$commandName = 'crontab';
		$binPath = $this->binPath;
		if (!empty($binPath)) {
			$commandName = $binPath . '/' . $commandName;
		}
		if (!self::checkShellCommandAvailable($commandName)) {
			throw new CException("Command '{$commandName}' is not available in shell.");
		}
		return $commandName;
	}

	/**
	 * Composes cron job line from configuration.
	 * @param QsCronJob|array $job cron job line or configuration.
	 * @return string cron job line.
	 * @throws CException on invalid job format
	 */
	protected function composeJobLine($job) {
		if (is_array($job)) {
			$job = $this->createJob($job);
		}
		if (!is_object($job)) {
			throw new CException('Cron job should be an instance of "QsCronJob" or its array configuration - "' . gettype($job) . '" given');
		}
		return $job->getLine();
	}

	/**
	 * Creates cron job instance from its array configuration.
	 * @param array $config cron job configuration.
	 * @return QsCronJob cron job instance.
	 */
	protected function createJob(array $config) {
		if (empty($config['class'])) {
			$config['class'] = $this->defaultJobClass;
		}
		return Yii::createComponent($config);
	}

	/**
	 * Returns the crontab lines composed from {@link jobs}.
	 * @return array crontab lines.
	 */
	public function getLines() {
		$lines = array();
		foreach ($this->getJobs() as $job) {
			$lines[] = $this->composeJobLine($job);
		}
		return $lines;
	}

	/**
	 * Returns current cron jobs setup in the system fro current user.
	 * @return array cron job lines.
	 * @throws CException on failure.
	 */
	public function getCurrentLines() {
		$command = $this->getCronTabCommandFullName() . ' -l 2>&1';
		$outputLines = array();
		exec($command, $outputLines);
		$lines = array();
		foreach ($outputLines as $outputLine) {
			if (stripos($outputLine, 'no crontab') !== 0) {
				$lines[] = trim($outputLine);
			}
		}
		return $lines;
	}

	/**
	 * Setup the cron jobs from given file.
	 * @param string $filename file name.
	 * @return QsCronTab self reference.
	 * @throws CException on failure.
	 */
	public function applyFile($filename) {
		if (!file_exists($filename)) {
			throw new CException("File '{$filename}' does not exist.");
		}
		$command = $this->getCronTabCommandFullName() . ' ' . escapeshellarg($filename);
		exec($command, $outputLines);
		return $this;
	}

	/**
	 * Saves the current jobs into the text file.
	 * @param string $fileName output file name.
	 * @return integer number of written bytes.
	 */
	public function saveToFile($fileName) {
		$lines = $this->getLines();
		$content = $this->composeFileContent($lines);
		return file_put_contents($fileName, $content);
	}

	/**
	 * Composes the crontab file content from given lines.
	 * @param array $lines crontab lines.
	 * @return string crontab file content.
	 */
	protected function composeFileContent(array $lines) {
		return implode("\n", $lines) . "\n";
	}

	/**
	 * Applies the current {@link jobs} to the current user crontab.
	 * This method will merge new jobs with the ones already set in the system.
	 * @throws CException on failure.
	 * @return QsCronTab self reference.
	 */
	public function apply() {
		$lines = $this->mergeLines($this->getCurrentLines(), $this->getLines());
		$this->applyLines($lines);
		return $this;
	}

	/**
	 * Applies given lines to current user crontab.
	 * @param array $lines crontab lines.
	 * @throws CException on failure.
	 */
	protected function applyLines(array $lines) {
		$content = $this->composeFileContent($lines);
		$fileName = tempnam(Yii::getPathOfAlias('application.runtime'), get_class($this));
		if ($fileName === false) {
			throw new CException('Unable to create temporary file.');
		}
		file_put_contents($fileName, $content);
		$this->applyFile($fileName);
		unlink($fileName);
	}

	/**
	 * Removes current {@link jobs} from the current user crontab.
	 * @return QsCronTab self reference.
	 */
	public function remove() {
		$currentLines = $this->getCurrentLines();
		$lines = $this->getLines();
		$remainingLines = array_diff($currentLines, $lines);
		if (empty($remainingLines)) {
			$this->removeAll();
		} else {
			$this->applyLines($remainingLines);
		}
		return $this;
	}

	/**
	 * Removes all cron jobs for the current user.
	 * @return QsCronTab self reference.
	 */
	public function removeAll() {
		$command = $this->getCronTabCommandFullName() . ' -r 2>&1';
		exec($command);
		return $this;
	}

	/**
	 * Merges given crontab lines.
	 * @param array $a lines to be merged to
	 * @param array $b lines to be merged from. You can specify additional
	 * arrays via third argument, fourth argument etc.
	 * @return array merged lines
	 */
	protected function mergeLines($a, $b) {
		$args = func_get_args();
		$result = array_shift($args);
		while (!empty($args)) {
			$next = array_shift($args);
			foreach ($next as $value) {
				if (!in_array($value, $result)) {
					$result[] = $value;
				}
			}
		}
		return $result;
	}
}