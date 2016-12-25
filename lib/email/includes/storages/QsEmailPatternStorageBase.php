<?php
/**
 * QsEmailPatternStorageBase class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternStorageBase is the email extension component,
 * which maintains email patterns storage. 
 * This class finds the email pattern for the requested patternId.
 * This class is abstract and should be extended to specify the explicit method of storing and retrieving of patterns.
 *
 * @property array $cachedPatterns public alias of {@link _cachedPatterns}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.storages
 */
abstract class QsEmailPatternStorageBase extends CComponent {
	/**
	 * @var array cache of already found patterns.
	 */
	protected $_cachedPatterns = array();

	// Cached Patterns:
	public function setCachedPatterns(array $cachedPatterns) {
		$this->_cachedPatterns = $cachedPatterns;
		return true;
	}

	public function getCachedPatterns() {
		return $this->_cachedPatterns;
	}

	public function clearCachedPatterns() {
		$this->_cachedPatterns = array();
		return true;
	}

	public function addCachedPattern($patternInstance) {
		if (!is_object($patternInstance)) {
			return false;
		}
		$this->_cachedPatterns[$patternInstance->getId()] = clone $patternInstance;
		return true;
	}

	public function getCachedPattern($patternId) {
		if (!array_key_exists($patternId, $this->_cachedPatterns)) {
			return null;
		}
		$patternInstance = $this->_cachedPatterns[$patternId];
		if (!is_object($patternInstance)) {
			return null;
		}
		return clone $patternInstance;
	}

	/**
	 * Finds email pattern by its id.
	 * @param mixed $patternId - id of the pattern.
	 * @return QsEmailPattern - email pattern object.
	 */
	public function getPattern($patternId) {
		$patternInstance = $this->getCachedPattern($patternId);
		if (!is_object($patternInstance)) {
			$patternInstance = $this->createEmailPatternInstance($patternId);
			$this->initEmailPatternInstance($patternInstance);
			$this->addCachedPattern($patternId, $patternInstance);
		}
		return $patternInstance;
	}

	/**
	 * Creates new email pattern instance and sets its id.
	 * @param mixed $patternId pattern id.
	 * @return QsEmailPattern email pattern instance.
	 */
	protected function createEmailPatternInstance($patternId) {
		$patternInstance = new QsEmailPattern();
		$patternInstance->setId($patternId);
		return $patternInstance;
	}

	/**
	 * Initializes email pattern instance, filling up its attributes with values
	 * found in storage.
	 * This method is abstract and should be overridden depending the storage kind.
	 * @throws Exception on fail.
	 * @param QsEmailPattern $patternInstance email pattern instance.
	 * @return boolean success.
	 */
	abstract protected function initEmailPatternInstance(QsEmailPattern $patternInstance);
}