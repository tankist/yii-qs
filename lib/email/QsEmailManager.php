<?php
/**
 * QsEmailManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailManager the main application component, which is required for the email extension usage.
 * This class contains all settings related to the email extension.
 * Application config example:
 * <code>
 * 'components' => array(
 *     ...
 *     'email' => array(
 *         'class' => 'qs.email.QsEmailManager',
 *         'testMode' => 'bcc',
 *         'testEmail' => 'develqs@quartsoft.com',
 *         //'transport' => array(
 *             //'type' => 'smtp',
 *             //'host' => 'localhost',
 *             //'username' => 'username',
 *             //'password' => 'password',
 *             //'port' => '587',
 *             //'encryption' => 'tls',
 *         //),
 *     ),
 *     ...
 * ),
 * </code>
 * 
 * Usage example:
 * <code>
 * $emailMessage = Yii::app()->email->createEmailByPattern('contact', $data);
 * $emailMessage->addTo(Yii::app()->params['admin_email']);
 * $emailMessage->send();
 * </code>
 * 
 * @see QsEmailMessage
 * @see QsEmailPatternStorage
 * @see QsEmailPatternComposerCompile
 *
 * @property boolean $logging public alias of {@link _logging}.
 * @property mixed $testMode public alias of {@link _testMode}.
 * @property string $testEmail public alias of {@link _testEmail}.
 * @property Swift_Transport|array $transport public alias of {@link _transport}.
 * @property Swift_Mailer $mailer public alias of {@link _mailer}.
 * @property QsEmailPatternStorageBase|array $patternStorage public alias of {@link _patternStorage}.
 * @property QsEmailPatternComposerBase|array $patternComposer public alias of {@link _patternComposer}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email
 */
class QsEmailManager extends CApplicationComponent {
	const TestModeBcc = 'bcc';
	const TestModeRedirect = 'redirect';
	const TestModeSilence = 'silence';
	const TestModeNone = false;

	/**
	 * @var boolean determines if email sends should be logged.
	 */
	protected $_logging = false;
	/**
	 * @var mixed email sending test mode.
	 * List of possible modes:
	 * bcc - sends bcc copy to the {@link testEmail}
	 * redirect - sends all email to the {@link testEmail}, instead of original receivers
	 * silence - do not send email at all, use {@link logging} with this mode
	 */
	protected $_testMode = self::TestModeNone;
	/**
	 * @var string email address, which is used in test modes.
	 * @see testMode
	 */
	protected $_testEmail = 'somename@somedomain.com';
	/**
	 * @var Swift_Transport|array the configuration for the email transport.
	 * Defaults to <code>array('type'=>'php')</code>.
	 * Specify 'type' parameter in order to set up transport type.
	 * For example: in order to use SMTP transport use:
	 * array(
	 *     'type' => 'smtp',
	 *     'host' => 'localhost',
	 *     'username' => 'username',
	 *     'password' => 'password',
	 *     'port' => '587',
	 *     'encryption' => 'tls',
	 *     'timeout' => '',
	 *     'extensionHandlers' => '',
	 * )
	 */
	protected $_transport = array(
		'type' => 'php'
	);
	/**
	 * @var Swift_Mailer mailer instance.
	 */
	protected $_mailer = null;
	/**
	 * @var QsEmailPatternStorageBase|array the configuration for the email pattern storage.
	 * @see QsEmailPatternStorageDb
	 */
	protected $_patternStorage = array(
		'class' => 'QsEmailPatternStorageDb'
	);
	/**
	 * @var QsEmailPatternComposerBase|array the configuration for the email pattern composer.
	 * @see QsEmailPatternComposerCompile
	 */
	protected $_patternComposer = array(
		'class' => 'QsEmailPatternComposerCompile'
	);

	// Set / Get :

	public function setLogging($logging) {
		$this->_logging = $logging;
		return true;
	}

	public function getLogging() {
		return $this->_logging;
	}

	public function setTestMode($testMode) {
		$this->_testMode = $testMode;
		return true;
	}

	public function getTestMode() {
		return $this->_testMode;
	}

	public function setTestEmail($testEmail) {
		if (!is_string($testEmail) && !is_array($testEmail)) {
			return false;
		}
		$this->_testEmail = $testEmail;
		return true;
	}

	public function getTestEmail() {
		return $this->_testEmail;
	}

	public function setTransport($transport) {
		if (is_scalar($transport)) {
			return false;
		}
		$this->_transport = $transport;
		return true;
	}

	public function getTransport() {
		$this->initTransport();
		return $this->_transport;
	}

	public function setMailer($mailer) {
		if (is_scalar($mailer)) {
			return false;
		}
		$this->_mailer = $mailer;
		return true;
	}

	public function getMailer() {
		$this->initMailer();
		return $this->_mailer;
	}

	public function setPatternStorage($patternStorage) {
		if (is_scalar($patternStorage)) {
			return false;
		}
		$this->_patternStorage = $patternStorage;
		return true;
	}

	public function getPatternStorage() {
		$this->initPatternStorage();
		return $this->_patternStorage;
	}

	public function setPatternComposer($patternComposer) {
		if (is_scalar($patternComposer)) {
			return false;
		}
		$this->_patternComposer = $patternComposer;
		return true;
	}

	public function getPatternComposer() {
		$this->initPatternComposer();
		return $this->_patternComposer;
	}

	/**
	 * Initializes component.
	 */
	public function init() {
		$this->importResourses();
		parent::init();
	}

	/**
	 * Imports {@link SwiftMailer} library.
	 * @return boolean success.
	 */
	public function importResourses() {
		if (!$this->getIsInitialized()) {
			Yii::import('qs.email.includes.*');
			Yii::import('qs.email.includes.storages.*');
			Yii::import('qs.email.includes.composers.*');

			require_once dirname(__FILE__) . '/SwiftMailer/classes/Swift.php';
			Yii::registerAutoloader(array('Swift', 'autoload'));
			require_once dirname(__FILE__) . '/SwiftMailer/swift_init.php';
		}
		return true;
	}

	/**
	 * Initializes mailer object.
	 * @return boolean success.
	 */
	protected function initMailer() {
		if (!is_object($this->_mailer)) {
			$this->_mailer = Swift_Mailer::newInstance($this->getTransport());
		}
		return true;
	}

	/**
	 * Initializes transport object.
	 * Transport will be created according to transport type.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	protected function initTransport() {
		if (!is_object($this->_transport)) {
			$transportOptions = $this->_transport;
			$transportType = $transportOptions['type'];
			unset($transportOptions['type']);

			switch ($transportType) {
				case 'php':
					$this->_transport = Swift_MailTransport::newInstance();
					if (!empty($transportOptions))
						$this->transport->setExtraParams($transportOptions);
					break;
				case 'smtp':
					$this->_transport = Swift_SmtpTransport::newInstance();
					foreach ($transportOptions as $option => $value)
						$this->_transport->{'set' . ucfirst($option)}($value); // sets option with the setter method
					break;
				default: {
					throw new CException('Class "' . __CLASS__ . '" error: unrecognized transport type "' . $transportOptions['type'] . '"!');
				}
			}
		}
		return true;
	}

	/**
	 * Initializes pattern storage object.
	 * @return boolean success.
	 */
	protected function initPatternStorage() {
		if (!is_object($this->_patternStorage)) {
			$this->_patternStorage = Yii::createComponent($this->_patternStorage);
		}
		return true;
	}

	/**
	 * Initializes pattern composer object.
	 * @return boolean success.
	 */
	protected function initPatternComposer() {
		if (!is_object($this->_patternComposer)) {
			$this->_patternComposer = Yii::createComponent($this->_patternComposer);
		}
		return true;
	}

	/**
	 * Sends email message.
	 * This method automatically applies {@link testMode}.
	 * @param QsEmailMessage $emailMessage - email message to be sent.
	 * @return integer number of recipients, who were accepted for email delivery.
	 */
	public function send(QsEmailMessage $emailMessage) {
		$this->log($emailMessage);

		$failedRecipients = array();

		// Apply Test Mode:
		switch ($this->getTestMode()) {
			case self::TestModeBcc: {
				$emailMessage->addBcc($this->getTestEmail());
				break;
			}
			case self::TestModeRedirect: {
				$receivers = array_keys($emailMessage->getTo());
				$receiversString = implode(', ', $receivers);
				$originalSubject = $emailMessage->getSubject();
				$subject = "Email redirect | {$receiversString} | {$originalSubject}";
				$emailMessage->setSubject($subject);
				$emailMessage->setTo($this->getTestEmail());
				$this->getMailer()->send($emailMessage->getRawEmailMessage(), $failedRecipients);
				return count($receivers);
			}
			case self::TestModeSilence: {
				$toCount = count($emailMessage->getTo());
				return $toCount;
			}
		}
		try {
			return $this->getMailer()->send($emailMessage->getRawEmailMessage(), $failedRecipients);
		} catch (Exception $e) {
		}
		return false;
	}

	/**
	 * Logs email message sending.
	 * @param QsEmailMessage $emailMessage - email message to be sent.
	 * @return boolean success.
	 */
	public function log(QsEmailMessage $emailMessage) {
		if ($this->getLogging()) {
			$msg = 'Sending email to ' . implode(', ', array_keys($emailMessage->to)) . "\n" . implode('', $emailMessage->headers->getAll()) . "\n" . $emailMessage->body;
			Yii::log($msg, CLogger::LEVEL_INFO, 'qs.email.QsEmailManager');
		}
		return true;
	}

	/**
	 * Finds email pattern in the storage by its id.
	 * @param mixed $patternId - id of the email pattern to be found.
	 * @return QsEmailPattern - email pattern object.
	 */
	public function getPattern($patternId) {
		return $this->getPatternStorage()->getPattern($patternId);
	}

	/**
	 * Composes email pattern, transfering internal simple markup into valid PHP code.
	 * @param mixed $patternOrId - instance of the {@link QsEmailPattern} or pattern id.
	 * @param array $data - list of params, which should be parsed.
	 * @return QsEmailPattern - composed email pattern object.
	 */
	public function composePattern($patternOrId, $data = null) {
		if (is_object($patternOrId)) {
			$pattern = $patternOrId;
		} else {
			$pattern = $this->getPattern($patternOrId);
		}
		return $this->getPatternComposer()->compose($pattern, $data);
	}

	/**
	 * Create email message object, filled with data from pattern found by its id.
	 * @param mixed $patternOrId - instance of the {@link QsEmailPattern} or pattern id.
	 * @param array $data - list of params, which should be parsed.
	 * @return QsEmailMessage - email message object.
	 */
	public function createEmailByPattern($patternOrId, $data = null) {
		$message = new QsEmailMessage();
		$pattern = $this->composePattern($patternOrId, $data);
		$message->applyPattern($pattern);
		return $message;
	}
} 