<?php
/**
 * QsEmailPatternComposerFile class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternComposerFile is an extension of {@link QsEmailPatternComposerBase}.
 * This composer threats email pattern attribute as name of the file, which contains view code.
 * This file will be rendered in order to receive particular email content.
 * This composer is the best to use with the {@link QsEmailPatternStorageFile}.
 * 
 * @see QsEmailPatternStorageFile
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.composers
 */
class QsEmailPatternComposerFile extends QsEmailPatternComposerBase {
	/**
	 * Fills email pattern attributes with the content, created while rendering of files,
	 * given as initial value of the attribute.
	 * @param string $attributeName - name of the email pattern attribute
	 * @return boolean - success.
	 */
	protected function renderEmailPatternAttribute($attributeName) {
		$attributeFileName = $this->_emailPattern->__get($attributeName);
		if (!empty($attributeFileName)) {
			$attributeContent = $this->renderInternal($attributeFileName, $this->_data, true);
			return $this->_emailPattern->__set($attributeName, $attributeContent);
		}
		return true;
	}

	/**
	 * Composes bodyHtml part of the email pattern.
	 * @return boolean success
	 */
	protected function composeBodyHtml() {
		return $this->renderEmailPatternAttribute('bodyHtml');
	}

	/**
	 * Composes bodyText part of the email pattern.
	 * @return boolean success
	 */
	protected function composeBodyText() {
		return $this->renderEmailPatternAttribute('bodyText');
	}

	/**
	 * Composes subject part of the email pattern.
	 * @return boolean success
	 */
	protected function composeSubject() {
		return $this->renderEmailPatternAttribute('subject');
	}

	/**
	 * Composes fromEmail part of the email pattern.
	 * @return boolean success
	 */
	protected function composeFromEmail() {
		return $this->renderEmailPatternAttribute('fromEmail');
	}

	/**
	 * Composes fromName part of the email pattern.
	 * @return boolean success
	 */
	protected function composeFromName() {
		return $this->renderEmailPatternAttribute('fromName');
	}
}