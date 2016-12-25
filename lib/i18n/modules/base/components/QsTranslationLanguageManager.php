<?php
/**
 * QsTranslationLanguageManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsTranslationLanguageManager is the component, which determines and manages the list of all languages.
 * @see QsWebModuleTranslationBase
 *
 * @property string $languageModelClassName public alias of {@link _languageModelClassName}.
 * @property CDbCriteria|array $languageModelSearchCriteria public alias of {@link _languageModelSearchCriteria}.
 * @property CActiveRecord[] $languages public alias of {@link _languages}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.base
 */
class QsTranslationLanguageManager extends CApplicationComponent {
	/**
	 * @var string class name of the {@link CActiveRecord} model, which should retrieve the languages list.
	 */
	protected $_languageModelClassName = 'Language';
	/**
	 * @var CDbCriteria|array search criteria for the {@link languageModelClassName} model,
	 * which should be applied, while retrieving the list of languages.
	 * For example:
	 * <code>
	 * array(
	 *     'condition' => 'status_id = 2',
	 * );
	 * ...
	 * array(
	 *     'scopes' => array('active'),
	 * );
	 * <code>
	 */
	protected $_languageModelSearchCriteria = array();
	/**
	 * @var CActiveRecord[] set of all available languages.
	 */
	protected $_languages = null;

	// Set / Get :

	public function setLanguageModelClassName($languageModelClassName) {
		if (!is_string($languageModelClassName)) {
			throw new CException('"' . get_class($this) . '::languageModelClassName" should be a string!');
		}
		$this->_languageModelClassName = $languageModelClassName;
		return true;
	}

	public function getLanguageModelClassName() {
		return $this->_languageModelClassName;
	}

	public function setLanguageModelSearchCriteria($languageModelSearchCriteria) {
		$this->_languageModelSearchCriteria = $languageModelSearchCriteria;
		return true;
	}

	public function getLanguageModelSearchCriteria() {
		return $this->_languageModelSearchCriteria;
	}

	public function setLanguages(array $languages) {
		$this->_languages = $languages;
		return true;
	}

	public function getLanguages() {
		if (!is_array($this->_languages)) {
			$this->initLanguages();
		}
		return $this->_languages;
	}

	/**
	 * Initializes {@link languages} value, using {@link languageModelClassName} model.
	 * @return boolean success.
	 */
	protected function initLanguages() {
		$languageFinder = CActiveRecord::model($this->getLanguageModelClassName());
		$languages = $languageFinder->findAll($this->getLanguageModelSearchCriteria());
		$this->_languages = $languages;
		return true;
	}
}
