<?php
/**
 * QsOAuthSignatureMethod class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthSignatureMethod base class for the OAuth signature methods.
 *
 * @property string $name method canonical name.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth.signature
 */
abstract class QsOAuthSignatureMethod extends CComponent {
	/**
	 * Return the canonical name of the Signature Method.
	 * @return string method name.
	 */
	abstract public function getName();

	/**
	 * Generates OAuth request signature.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return string signature string.
	 */
	abstract public function generateSignature($baseString, $key);

	/**
	 * Verifies given OAuth request.
	 * @param string $signature signature to be verified.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return boolean success.
	 */
	public function verify($signature, $baseString, $key) {
		$expectedSignature = $this->generateSignature($baseString, $key);
		if (empty($signature) || empty($expectedSignature)) {
			return false;
		}
		return (strcmp($expectedSignature, $signature) === 0);
	}
}
