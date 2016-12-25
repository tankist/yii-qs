<?php
/**
 * QsEmailMessage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailMessage is used for building emails.
 * This class inherits {@link Swift_Message} as a mixing.
 * Refer to the SwiftEmail manual for the full methods list.
 * Example:
 * <code>
 * $emailMessage = new QsEmailMessage();
 * $emailMessage->addTo( Yii::app()->params['adminEmail'] );
 * $emailMessage->setFrom($model->email);
 * $emailMessage->setSubject($model->subject);
 * $emailMessage->addBodyHtml($model->body);
 * $emailMessage->send();
 * </code>
 * 
 * @see Swift_Message
 *
 * @property Swift_Message $rawEmailMessage public alias of {@link _rawEmailMessage}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email
 */
class QsEmailMessage extends CComponent {
	/**
	 * @var Swift_Message instance of the vendor message object.
	 */
	protected $_rawEmailMessage;

	public function __get($name) {
		try {
			return parent::__get($name);
		} catch (CException $exception) {
			$getter = 'get' . $name;
			$rawEmailMessage = $this->getRawEmailMessage();
			if (method_exists($rawEmailMessage, $getter)) {
				return $rawEmailMessage->$getter();
			} else {
				throw $exception;
			}
		}
	}
	
	public function __set($name, $value) {
		try {
			return parent::__set($name, $value);
		} catch (CException $exception) {
			$setter = 'set' . $name;
			$rawEmailMessage = $this->getRawEmailMessage();
			if (method_exists($rawEmailMessage, $setter)) {
				$rawEmailMessage->$setter($value);
			} else {
				throw $exception;
			}
		}
	}
	
	public function __call($name, $parameters) {
		try {
			return parent::__call($name, $parameters);
		} catch (CException $exception) {
			$rawEmailMessage = $this->getRawEmailMessage();
			if (method_exists($rawEmailMessage, $name)) {
				return call_user_func_array(array($rawEmailMessage, $name), $parameters);
			} else {
				throw $exception;
			}
		}
	}

	/**
	 * Returns the raw swift message instance.
	 * @throws CException on failure.
	 * @return Swift_Message swift message instance.
	 */
	public function getRawEmailMessage() {
		if (!is_object($this->_rawEmailMessage)) {
			// make sure email component is loaded:
			if (!Yii::app()->getComponent('email')) {
				throw new CException('Unable to find application component "email".');
			}
			$this->_rawEmailMessage = new Swift_Message();
		}
		return $this->_rawEmailMessage;
	}

	/**
	 * Sends the email message.
	 * @return integer number of recipients, who were accepted for email delivery.
	 */
	public function send() {
		return Yii::app()->getComponent('email')->send($this);
	}

	/**
	 * Sets the from address of this message.
	 * Note: this method automatically sets "reply-to" header to the same value.
	 * @param string|array $address email address or set of email addresses.
	 * @param string $name name associated with address.
	 * @return QsEmailMessage self reference.
	 */
	public function setFrom($address, $name = null) {
		$this->getRawEmailMessage()->setFrom($address, $name);
		$this->getRawEmailMessage()->setReplyTo($address, $name);
		return $this;
	}

	/**
	 * Sets the subject of this message.
	 * @param string $subject message subject.
	 * @return QsEmailMessage self reference.
	 */
	public function setSubject($subject) {
		$this->getRawEmailMessage()->setSubject($subject);
		return $this;
	}

	/**
	 * Sets the HTML body content.
	 * @param string $html HTML body content.
	 * @return Swift_Mime_MimePart email message mime part.
	 */
	public function setBodyHtml($html) {
		return $this->getRawEmailMessage()->setBody($html, 'text/html');
	}

	/**
	 * Sets the plain text body content.
	 * @param string $text plain text body content.
	 * @return Swift_Mime_MimePart email message mime part.
	 */
	public function setBodyText($text) {
		return $this->getRawEmailMessage()->setBody($text, 'text/plain');
	}

	/**
	 * Adds the HTML content part.
	 * @param string $html HTML content.
	 * @return Swift_Mime_MimePart email message mime part.
	 */
	public function addBodyHtml($html) {
		return $this->getRawEmailMessage()->addPart($html, 'text/html');
	}

	/**
	 * Adds the plain text content part.
	 * @param string $text HTML content.
	 * @return Swift_Mime_MimePart email message mime part.
	 */
	public function addBodyText($text) {
		return $this->getRawEmailMessage()->addPart($text, 'text/plain');
	}

	/**
	 * Attaches existing file to the email.
	 * @param string $fileName - full file name
	 * @param string $contentType - MIME type of the attachment file, if empty 'application/octet-stream' will be used
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	public function attachFile($fileName, $contentType = null) {
		if (!file_exists($fileName)) {
			throw new CException('Unable to attach file "' . $fileName . '": file does not exists!');
		}
		$content = file_get_contents($fileName);
		return $this->createAttachment($content, basename($fileName), $contentType);
	}

	/**
	 * Create file attachment for the email.
	 * @param string $content - attachment file content
	 * @param string $fileName - attachment file name
	 * @param string $contentType - MIME type of the attachment file, if empty 'application/octet-stream' will be used
	 * @return boolean success.
	 */
	public function createAttachment($content, $fileName, $contentType = null) {
		if (empty($contentType)) {
			$contentType = 'application/octet-stream';
		}
		$attachment = Swift_Attachment::newInstance($content, $fileName, $contentType);
		$this->getRawEmailMessage()->attach($attachment);
		return true;
	}

	/**
	 * Fills the own attributes with the data of passed email pattern.
	 * @param QsEmailPattern $emailPattern - source email pattern.
	 * @throws CException on failure.
	 * @return boolean success.
	 */
	public function applyPattern($emailPattern) {
		if (!is_object($emailPattern)) {
			throw new CException('"EmailPattern" should be a "QsEmailPattern" instance, ' . gettype($emailPattern) . ' is given.');
		}

		$this->setFrom($emailPattern->getFrom());
		$this->setSubject($emailPattern->getSubject());

		$bodyHtml = $emailPattern->getBodyHtml();
		if ($bodyHtml) {
			$this->addBodyHtml($bodyHtml);
		}
		$bodyText = $emailPattern->getBodyText();
		if ($bodyText) {
			$this->addBodyText($bodyText);
		}
		return true;
	}
}