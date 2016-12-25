<?php
/**
 * QsOAuthSignatureMethodHmacSha1 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthSignatureMethodHmacSha1
 *
 * Note: This class require PHP "Hash" extension({@link http://php.net/manual/en/book.hash.php}).
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth.signature
 */
class QsOAuthSignatureMethodHmacSha1 extends QsOAuthSignatureMethod {
	/**
	 * Constructor.
	 * Checks if the environment allows this method run.
	 */
	public function __construct() {
		if (!function_exists('hash_hmac')) {
			throw new CException('PHP "Hash" extension is required.');
		}
	}

	/**
	 * Return the canonical name of the Signature Method.
	 * @return string method name.
	 */
	public function getName() {
		return 'HMAC-SHA1';
	}

	/**
	 * Generates OAuth request signature.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return string signature string.
	 */
	public function generateSignature($baseString, $key) {
		return base64_encode(hash_hmac('sha1', $baseString, $key, true));
	}
}
