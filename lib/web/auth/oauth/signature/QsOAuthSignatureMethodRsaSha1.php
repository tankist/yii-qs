<?php
/**
 * QsOAuthSignatureMethodRsaSha1 class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthSignatureMethodRsaSha1
 *
 * Note: This class require PHP "OpenSSL" extension({@link http://php.net/manual/en/book.openssl.php}).
 *
 * @property array $privateCertificate public alias of {@link _privateCertificate}.
 * @property array $publicCertificate public alias of {@link _publicCertificate}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth.signature
 */
class QsOAuthSignatureMethodRsaSha1 extends QsOAuthSignatureMethod {
	/**
	 * @var string OpenSSL private key certificate content.
	 * This value can be fetched from file specified by {@link privateCertificateFile}.
	 */
	protected $_privateCertificate;
	/**
	 * @var string OpenSSL public key certificate content.
	 * This value can be fetched from file specified by {@link publicCertificateFile}.
	 */
	protected $_publicCertificate;
	/**
	 * @var string path to the file, which holds private key certificate.
	 */
	public $privateCertificateFile = '';
	/**
	 * @var string path to the file, which holds public key certificate.
	 */
	public $publicCertificateFile = '';

	/**
	 * Constructor.
	 * Checks if the environment allows this method run.
	 */
	public function __construct() {
		if (!function_exists('openssl_sign')) {
			throw new CException('PHP "OpenSSL" extension is required.');
		}
	}

	public function setPublicCertificate($publicCertificate) {
		$this->_publicCertificate = $publicCertificate;
	}

	public function getPublicCertificate() {
		if ($this->_publicCertificate === null) {
			$this->_publicCertificate = $this->initPublicCertificate();
		}
		return $this->_publicCertificate;
	}

	public function setPrivateCertificate($privateCertificate) {
		$this->_privateCertificate = $privateCertificate;
	}

	public function getPrivateCertificate() {
		if ($this->_privateCertificate === null) {
			$this->_privateCertificate = $this->initPrivateCertificate();
		}
		return $this->_privateCertificate;
	}

	/**
	 * Return the canonical name of the Signature Method.
	 * @return string method name.
	 */
	public function getName() {
		return 'RSA-SHA1';
	}

	/**
	 * Creates initial value for {@link publicCertificate}.
	 * This method will attempt to fetch the certificate value from {@link publicCertificateFile} file.
	 * @throws CException on failure.
	 * @return string public certificate content.
	 */
	protected function initPublicCertificate() {
		if (!empty($this->publicCertificateFile)) {
			if (!file_exists($this->publicCertificateFile)) {
				throw new CException("Public certificate file '{$this->publicCertificateFile}' does not exist!");
			}
			return file_get_contents($this->publicCertificateFile);
		} else {
			return '';
		}
	}

	/**
	 * Creates initial value for {@link privateCertificate}.
	 * This method will attempt to fetch the certificate value from {@link privateCertificateFile} file.
	 * @throws CException on failure.
	 * @return string private certificate content.
	 */
	protected function initPrivateCertificate() {
		if (!empty($this->privateCertificateFile)) {
			if (!file_exists($this->privateCertificateFile)) {
				throw new CException("Private certificate file '{$this->privateCertificateFile}' does not exist!");
			}
			return file_get_contents($this->privateCertificateFile);
		} else {
			return '';
		}
	}

	/**
	 * Generates OAuth request signature.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return string signature string.
	 */
	public function generateSignature($baseString, $key) {
		$privateCertificateContent = $this->getPrivateCertificate();
		// Pull the private key ID from the certificate
		$privateKeyId = openssl_pkey_get_private($privateCertificateContent);
		// Sign using the key
		openssl_sign($baseString, $signature, $privateKeyId);
		// Release the key resource
		openssl_free_key($privateKeyId);
		return base64_encode($signature);
	}

	/**
	 * Verifies given OAuth request.
	 * @param string $signature signature to be verified.
	 * @param string $baseString signature base string.
	 * @param string $key signature key.
	 * @return boolean success.
	 */
	public function verify($signature, $baseString, $key) {
		$decodedSignature = base64_decode($signature);
		// Fetch the public key cert based on the request
		$publicCertificate = $this->getPublicCertificate();
		// Pull the public key ID from the certificate
		$publicKeyId = openssl_pkey_get_public($publicCertificate);
		// Check the computed signature against the one passed in the query
		$verificationResult = openssl_verify($baseString, $decodedSignature, $publicKeyId);
		// Release the key resource
		openssl_free_key($publicKeyId);
		return ($verificationResult == 1);
	}
}
