<?php
/**
 * QsEmailPattern class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPattern is service class, which instance is used as data structure,
 * which is passed between different components.
 *
 * @property mixed $id public alias of {@link _id}.
 * @property integer $timestamp public alias of {@link _timestamp}.
 * @property string $subject public alias of {@link _subject}.
 * @property string|array $fromEmail public alias of {@link _fromEmail}.
 * @property string $fromName public alias of {@link _fromName}.
 * @property string $bodyHtml public alias of {@link _bodyHtml}.
 * @property string $bodyText public alias of {@link _bodyText}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email
 */
class QsEmailPattern extends CComponent {
	/**
	 * @var mixed id of pattern, may be string, integer or else.
	 */
	protected $_id = null;
	/**
	 * @var integer timestamp of the last update date.
	 */
	protected $_timestamp = null;
	/**
	 * @var string email subject.
	 */
	protected $_subject = '';
	/**
	 * @var string|array email address of sender, or pair senderEmail=>senderName.
	 */
	protected $_fromEmail = '';
	/**
	 * @var string sender name.
	 */
	protected $_fromName = '';
	/**
	 * @var string email content in HTML format.
	 */
	protected $_bodyHtml = '';
	/**
	 * @var string email content in plain text format.
	 */
	protected $_bodyText = '';

	/**
	 * Constructor.
	 * @param array|null $fields filed values.
	 */
	public function __construct($fields = null) {
		if (is_array($fields)) {
			foreach ($fields as $name => $value) {
				$this->__set($name, $value);
			}
		}
	}

	// Set / Get :

	public function setId($id) {
		$this->_id = $id;
		return true;
	}

	public function getId() {
		return $this->_id;
	}

	public function setTimestamp($timestamp) {
		if (!is_numeric($timestamp)) {
			return false;
		}
		$this->_timestamp = $timestamp;
		return true;
	}

	public function getTimestamp() {
		return $this->_timestamp;
	}

	public function setSubject($subject) {
		if (!is_string($subject)) {
			return false;
		}
		$this->_subject = $subject;
		return true;
	}

	public function getSubject() {
		return $this->_subject;
	}

	public function setFromEmail($fromEmail) {
		if (!is_string($fromEmail)) {
			return false;
		}
		$this->_fromEmail = $fromEmail;
		return true;
	}

	public function getFromEmail() {
		return $this->_fromEmail;
	}

	public function setFromName($fromName) {
		if (!is_string($fromName)) {
			return false;
		}
		$this->_fromName = $fromName;
		return true;
	}

	public function getFromName() {
		return $this->_fromName;
	}

	public function setBodyHtml($bodyHtml) {
		if (!is_string($bodyHtml)) {
			return false;
		}
		$this->_bodyHtml = $bodyHtml;
		return true;
	}

	public function getBodyHtml() {
		return $this->_bodyHtml;
	}

	public function setBodyText($bodyText) {
		if (!is_string($bodyText)) {
			return false;
		}
		$this->_bodyText = $bodyText;
		return true;
	}

	public function getBodyText() {
		return $this->_bodyText;
	}

	public function setFrom($from) {
		if (is_array($from)) {
			if (count($from)>1) {
				list($fromEmail, $fromName) = $from;
			} else {
				list($fromName) = array_values($from);
				list($fromEmail) = array_keys($from);
			}
			return ($this->setFromEmail($fromEmail) && $this->setFromName($fromName));
		} else {
			return $this->setFromEmail($from);
		}
	}

	public function getFrom() {
		if (empty($this->_fromName)) {
			$result = $this->_fromEmail;
		} else {
			$result = array(
				$this->_fromEmail => $this->_fromName
			);
		}
		return $result;
	}
}