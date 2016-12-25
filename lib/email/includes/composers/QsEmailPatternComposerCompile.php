<?php
/**
 * QsEmailPatternComposerCompile class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternComposerCompile is an extension of {@link QsEmailPatternComposerBase}.
 * This composer creates view files "on the fly", which will be rendered by standard method.
 * 
 * Composer translates expressions of internal simple markup into valid PHP code.
 * For example:
 * '{userName}' to '<?php echo nl2br(htmlspecialchars($userName)); ?>',
 * '{user->name}' to '<?php echo nl2br(htmlspecialchars($user->name)); ?>',
 * '{user[name]}' to '<?php echo nl2br(htmlspecialchars($user['name'])); ?>',
 * If you mark placeholder with the leading '$', no 'htmlspecialchars' will be append.
 * For example:
 * '{$userName}' to '<?php echo $userName; ?>'
 *
 * See {@link QsEmailPatternComposerCompile::compileText()} for more details.
 * Pay attention: {@link compilePath} should be writeable (or allowed to be created) for the web server.
 *
 * @property string $compilePath public alias of {@link _compilePath}.
 * @property integer $filePermission public alias of {@link _filePermission}.
 * @property string $leftDelimiter public alias of {@link _leftDelimiter}.
 * @property string $rightDelimiter public alias of {@link _rightDelimiter}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.composers
 */
class QsEmailPatternComposerCompile extends QsEmailPatternComposerBase {
	/**
	 * @var string path to the directory, which will store compiled view files.
	 * This directory should exist and be writeable for the web server.
	 * If the directory does not exist, it will be attempt to be created.
	 * By default this parameter will be set to "application.runtime.email_compiled".
	 */
	protected $_compilePath = 'email_compiled';
	/**
	 * @var integer the chmod permission for directories and files,
	 * created in the process. Defaults to 0755 (owner rwx, group rx and others rx).
	 */
	protected $_filePermission = 0755;
	/**
	 * @var string left delimiter in the internal pattern markup.
	 */
	protected $_leftDelimiter = '{';
	/**
	 * @var string right delimiter in the internal pattern markup.
	 */
	protected $_rightDelimiter = '}';

	/**
	 * Class constructor.
	 * Sets up default {@link compilePath}.
	 */
	public function __construct() {
		parent::__construct();
		$this->setCompilePath(Yii::app()->getBasePath() . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'email_compiled');
	}

	// Property Access:

	public function setCompilePath($compilePath) {
		if (!is_string($compilePath)) {
			return false;
		}
		$this->_compilePath = $compilePath;
		return true;
	}

	public function getCompilePath() {
		return $this->_compilePath;
	}

	public function setFilePermission($filePermission) {
		$this->_filePermission = $filePermission;
		return true;
	}

	public function getFilePermission() {
		return $this->_filePermission;
	}

	public function setLeftDelimiter($leftDelimiter) {
		if (!is_string($leftDelimiter)) {
			return false;
		}
		$this->_leftDelimiter = $leftDelimiter;
		return true;
	}

	public function getLeftDelimiter() {
		return $this->_leftDelimiter;
	}

	public function setRightDelimiter($rightDelimiter) {
		if (!is_string($rightDelimiter)) {
			return false;
		}
		$this->_rightDelimiter = $rightDelimiter;
		return true;
	}

	public function getRightDelimiter() {
		return $this->_rightDelimiter;
	}

	/**
	 * Checks if compile path exists and writeable.
	 * Tries to create missing directories, but only from beginning of the application base path.
	 * @throws CException on failure.
	 * @return string path to the compiled directory.
	 */
	protected function resolveCompiledPath() {
		$compilePath = $this->getCompilePath();
		if (!empty($compilePath)) {
			if (!file_exists($compilePath)) {
				$oldUmask = umask(0);
				mkdir($compilePath, $this->getFilePermission(), true);
				umask($oldUmask);
			}
		}
		if (!file_exists($compilePath) || !is_dir($compilePath) || !is_writable($compilePath)) {
			throw new CException("Compile path '{$compilePath}' is invalid. Please check this path exists and is writeable for the web server.");
		}
		return $compilePath;
	}

	/**
	 * Clear compiled view files.
	 * Warning: this method will remove all *.php files from the compile path.
	 * @return boolean success.
	 */
	public function clearCompiled() {
		$compilePath = $this->getCompilePath();
		if (file_exists($compilePath)) {
			$command = 'rm -f ' . escapeshellarg($compilePath . '/*.php');
			exec($command);
		}
		return true;
	}

	/**
	 * Compiles single internal markup tag.
	 * @param array $matches regular expression matches.
	 * @return string compiled tag.
	 * @see compileText()
	 */
	protected function compileTag($matches) {
		$tag = $matches[3];
		$searches = array(
			'[', ']'
		);
		$replaces = array(
			"['", "']"
		);
		$tag = str_replace($searches, $replaces, $tag);

		$tagPrefix = $matches[2];
		switch ($tagPrefix) {
			case '$': {
				$echoString = '$' . $tag;
				break;
			}
			default: {
				$echoString = 'nl2br(htmlspecialchars($' . $tag . '))';
			}
		}
		$compiledTag = '<?php echo ' . $echoString . '; ?>';
		return $compiledTag;
	}

	/**
	 * Compiles text transforming internal simple markup into valid PHP code.
	 * For example:
	 * '{userName}' to '<?php echo nl2br(htmlspecialchars($userName)); ?>',
	 * '{user->name}' to '<?php echo nl2br(htmlspecialchars($user->name)); ?>',
	 * '{user[name]}' to '<?php echo nl2br(htmlspecialchars($user['name'])); ?>',
	 * If you mark placeholder with the leading '$', no 'htmlspecialchars' will be append.
	 * For example:
	 * '{$userName}' to '<?php echo $userName; ?>'
	 * @see leftDelimiter
	 * @see rightDelimiter
	 * @param string $text - pattern text to be compiled.
	 * @return string - compiled PHP code.
	 */
	public function compileText($text) {
		if (!is_string($text)) {
			return false;
		}
		$pattern = '/(' . $this->getLeftDelimiter() . ')(\$?)([A-Za-z0-9_(\->)\[\]]+)(' . $this->getRightDelimiter() . ')/m';
		$compiledCode = preg_replace_callback($pattern, array($this, 'compileTag'), $text);
		return $compiledCode;
	}

	/**
	 * Compiles view file.
	 * File will be overwritten only if it is out of date determined by $timestamp.
	 * @param string $content - content of the file
	 * @param string $fileName - full name of the file
	 * @param integer $timestamp - UNIX timestamp of last updated date
	 * @return boolean success.
	 */
	protected function compileFile($content, $fileName, $timestamp=0) {
		if (file_exists($fileName)) {
			if ($timestamp > 0 && $timestamp <= filemtime($fileName)) {
				return true;
			}
			unlink($fileName);
		}
		$fileContent = $this->compileText($content);
		$savedBytes = file_put_contents($fileName, $fileContent);
		if ($savedBytes) {
			chmod($fileName, $this->getFilePermission());
			return true;
		}
		return false;
	}

	/**
	 * Compiles entire email pattern object.
	 * Each part of the pattern will be compiled in a separate file.
	 * File will be overwritten only if it is out of date determined by $emailPattern->timestamp.
	 * @param QsEmailPattern $emailPattern - email pattern object
	 * @return boolean success
	 * @see QsEmailPattern
	 */
	public function compilePattern(QsEmailPattern $emailPattern) {
		$compilePath = $this->resolveCompiledPath();

		$patternFileNameBase = $compilePath . DIRECTORY_SEPARATOR . $emailPattern->getId();

		$this->compilePatternAttribute($patternFileNameBase, $emailPattern, 'bodyHtml');

		$bodyText = $emailPattern->getBodyText();
		if (!empty($bodyText)) {
			$this->compilePatternAttribute($patternFileNameBase, $emailPattern, 'bodyText');
		}

		$this->compilePatternAttribute($patternFileNameBase, $emailPattern, 'subject');

		$this->compilePatternAttribute($patternFileNameBase, $emailPattern, 'fromEmail');

		$fromName = $emailPattern->getFromName();
		if (!empty($fromName)) {
			$this->compilePatternAttribute($patternFileNameBase, $emailPattern, 'fromName');
		}

		return true;
	}

	/**
	 * Compiles single part (attribute) of the email pattern.
	 * In the result new view file will be created.
	 * @param string $patternFileNameBase - base pattern file name.
	 * @param QsEmailPattern $emailPattern - email pattern instance
	 * @param string $attributeName - email pattern attribute name
	 * @return boolean success
	 */
	protected function compilePatternAttribute($patternFileNameBase, QsEmailPattern $emailPattern, $attributeName) {
		$fileExtension = '.php';
		$fileName = $patternFileNameBase . '.' . $attributeName . $fileExtension;
		$fileContent = call_user_func(array($emailPattern, 'get' . $attributeName));
		return $this->compileFile($fileContent, $fileName, $emailPattern->getTimestamp());
	}

	/**
	 * Composes single part (attribute) of the email pattern.
	 * @param string $attributeName name of email pattern attribute
	 * @return boolean success
	 */
	protected function composePatternAttribute($attributeName) {
		$viewFileName = $this->getCompilePath() . DIRECTORY_SEPARATOR . $this->_emailPattern->getId() . '.' . $attributeName . '.php';
		$composedValue = $this->renderInternal($viewFileName, $this->_data, true);
		return call_user_func(array($this->_emailPattern, 'set' . $attributeName), $composedValue);
	}

	/**
	 * Composes bodyHtml part of the email pattern.
	 * @return boolean success
	 */
	protected function composeBodyHtml() {
		return $this->composePatternAttribute('bodyHtml');
	}

	/**
	 * Composes bodyText part of the email pattern.
	 * @return boolean success
	 */
	protected function composeBodyText() {
		return $this->composePatternAttribute('bodyText');
	}

	/**
	 * Composes subject part of the email pattern.
	 * @return boolean success
	 */
	protected function composeSubject() {
		return $this->composePatternAttribute('subject');
	}

	/**
	 * Composes fromEmail part of the email pattern.
	 * @return boolean success
	 */
	protected function composeFromEmail() {
		return $this->composePatternAttribute('fromEmail');
	}

	/**
	 * Composes fromName part of the email pattern.
	 * @return boolean success
	 */
	protected function composeFromName() {
		return $this->composePatternAttribute('fromName');
	}

	/**
	 * This method is invoked before email pattern composing.
	 * The default implementation raises the {@link onBeforeCompose} event.
	 * You may override this method to do pre processing before email pattern composing.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function beforeCompose() {
		$this->compilePattern($this->_emailPattern);
		parent::beforeCompose();
	}
}