<?php
/**
 * MessageTranslationFilter class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * MessageTranslationFilter is a model, which is used as filter for the
 * {@link MessageTranslationMapper::findAll()}.
 *
 * @property array $languages public alias of {@link _languages}.
 * @property array $contents public alias of {@link _contents}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class MessageTranslationFilter extends CModel {
	/**
	 * @var array list of language locale codes, which require translation.
	 * For example:
	 * <code>
	 * array(
	 *     'en', 'ge', 'nl'
	 * );
	 * </code>
	 */
	protected $_languages = array();
	/**
	 * @var array set of content conditions.
	 * Array key is a language locale code, array value - content search string.
	 * For example:
	 * <code>
	 * array(
	 *     'en' => 'some text',
	 *     'ge' => 'einige text',
	 * );
	 * </code>
	 */
	protected $_contents = array();

	// Attributes:

	/**
	 * @var string message self name.
	 */
	public $name;
	/**
	 * @var string message category name.
	 */
	public $category_name;
	/**
	 * @var string message default content.
	 */
	public $default_content;

	public function __set($name, $value) {
		if (in_array($name, $this->contentAttributeNames())) {
			$language = str_replace('content_', '', $name);
			if (in_array($language, $this->getLanguages())) {
				return $this->setContent($language, $value);
			} else {
				return parent::__set($name, $value);
			}
		} else {
			return parent::__set($name, $value);
		}
	}

	public function __get($name) {
		if (in_array($name, $this->contentAttributeNames())) {
			$language = str_replace('content_', '', $name);
			if (in_array($language, $this->getLanguages())) {
				return $this->getContent($language);
			} else {
				return parent::__get($name);
			}
		}
		return parent::__get($name);
	}

	// Set / Get :
	
	public function setLanguages(array $languages) {
		$this->_languages = $languages;
		return true;
	}

	public function getLanguages() {
		if (empty($this->_languages)) {
			$this->initLanguages();
		}
		return $this->_languages;
	}

	public function setContents(array $contents) {
		$this->_contents = $contents;
		return true;
	}

	public function getContents() {
		return $this->_contents;
	}

	public function setContent($language, $content) {
		$this->_contents[$language] = $content;
		return true;
	}

	public function getContent($language) {
		return array_key_exists($language, $this->_contents) ? $this->_contents[$language] : null;
	}

	/**
	 * Initializes {@link languages} value.
	 * @return boolean success.
	 */
	protected function initLanguages() {
		$languages = array();
		$languageManager = $this->getMessageTranslationModule()->getComponent('languageManager');
		$languageModels = $languageManager->getLanguages();
		foreach ($languageModels as $languageModel) {
			$languages[] = $languageModel->locale_code;
		}
		$this->_languages = $languages;
		return true;
	}

	/**
	 * Returns message translation module.
	 * @return CModule message translation module.
	 */
	public function getMessageTranslationModule() {
		$module = null;
		$currentController = Yii::app()->getController();
		if (is_object($currentController)) {
			$module = $currentController->getModule();
		}
		if (!is_object($module)) {
			$module = Yii::app()->getModule('messagetranslation');
		}
		return $module;
	}

	/**
	 * Returns the list of attribute names of the model,
	 * which refer translation content.
	 * @return array list of attribute names.
	 */
	public function contentAttributeNames() {
		$attributeNames = array();
		foreach ($this->getLanguages() as $language) {
			$attributeNames[] = 'content_' . $language;
		}
		return $attributeNames;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		$attributeNames = array(
			'name',
			'category_name',
			'default_content',
		);
		$attributeNames = array_merge($attributeNames, $this->contentAttributeNames());
		return $attributeNames;
	}

	/**
	 * Defines the validation rules.
	 * @return array list of validation rules.
	 */
	public function rules() {
		return array(
			array(implode(',', $this->attributeNames()), 'safe'),
		);
	}

	/**
	 * Returns the attribute labels.
	 * Attribute labels are mainly used in error messages of validation.
	 * By default an attribute label is generated using {@link generateAttributeLabel}.
	 * This method allows you to explicitly specify attribute labels.
	 *
	 * Note, in order to inherit labels defined in the parent class, a child class needs to
	 * merge the parent labels with child labels using functions like array_merge().
	 *
	 * @return array attribute labels (name=>label)
	 * @see generateAttributeLabel
	 */
	public function attributeLabels() {
		return array(
			'name' => 'Name',
			'category_name' => 'Category',
		);
	}

	/**
	 * Checks is filter instance is empty,
	 * which means all its attributes are empty.
	 * @return boolean filter is empty.
	 */
	public function isEmpty() {
		foreach ($this->attributeNames() as $attributeName) {
			$attributeValue = $this->$attributeName;
			if (!empty($attributeValue)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Applies filter for the models list.
	 * @param array $models raw models list
	 * @return array filtered models list.
	 */
	public function apply(array $models) {
		if ($this->isEmpty()) {
			return $models;
		}
		$models = array_filter($models, array($this, 'checkModelAllowed'));
		return $models;
	}

	/**
	 * Checks if given model is allowed by this filter.
	 * @param CModel $model model for checking
	 * @return boolean model allowed.
	 */
	public function checkModelAllowed(CModel $model) {
		if (!empty($this->name)) {
			if (strpos($model->name, $this->name) === false) {
				return false;
			}
		}
		if (!empty($this->category_name)) {
			if (strpos($model->category_name, $this->category_name) === false) {
				return false;
			}
		}
		if (!empty($this->default_content)) {
			if (strpos($model->default_content, $this->default_content) === false) {
				return false;
			}
		}

		foreach ($this->_contents as $language => $content) {
			$translationContent = $model->getTranslation($language);
			if (!empty($content)) {
				if (empty($translationContent) || strpos($translationContent, $content) === false) {
					return false;
				}
			}
		}

		return true;
	}
}
