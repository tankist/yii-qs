<?php
/**
 * QsOAuthSignatureMethodPlainText class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthSignatureMethodPlainText
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth.signature
 */
class QsOAuthSignatureMethodPlainText extends QsOAuthSignatureMethod {
	/**
	 * Return the canonical name of the Signature Method.
	 * @return string method name.
	 */
	public function getName() {
		return 'PLAINTEXT';
	}

	/**
	 * Generates OAuth request signature.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return string signature string.
	 */
	public function generateSignature($baseString, $key) {
		return $key;
	}
}
