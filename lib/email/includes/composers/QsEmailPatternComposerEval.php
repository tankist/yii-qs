<?php
/**
 * QsEmailPatternComposerEval class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternComposerEval is an extension of {@link QsEmailPatternComposerBase}.
 * This composer translates email pattern using standard PHP function {@link eval}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.composers
 */
class QsEmailPatternComposerEval extends QsEmailPatternComposerBase {
	/**
	 * Composes bodyHtml part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeBodyHtml() {
		$bodyHtml = $this->evalRender($this->_emailPattern->getBodyHtml(), $this->_data);
		return $this->_emailPattern->setBodyHtml($bodyHtml);
	}

	/**
	 * Composes bodyText part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeBodyText() {
		$bodyText = $this->evalRender($this->_emailPattern->getBodyText(), $this->_data);
		return $this->_emailPattern->setBodyText($bodyText);
	}

	/**
	 * Composes subject part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeSubject() {
		$subject = $this->evalRender($this->_emailPattern->getSubject(), $this->_data);
		return $this->_emailPattern->setSubject($subject);
	}

	/**
	 * Composes fromEmail part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeFromEmail() {
		$fromEmail = $this->evalRender($this->_emailPattern->getFromEmail(), $this->_data);
		return $this->_emailPattern->setFromEmail($fromEmail);
	}

	/**
	 * Composes fromName part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeFromName() {
		$fromName = $this->evalRender($this->_emailPattern->getFromName(), $this->_data);
		return $this->_emailPattern->setFromName($fromName);
	}
}