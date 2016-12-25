<?php
/**
 * ImageTranslationFilter class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * ImageTranslationFilter is a model, which is used as filter for the
 * {@link ImageTranslation::findAll()}.
 * This model introduces dynamic attributes, which names match pattern "exist_$lang",
 * where $lang - language locale code.
 *
 * @see ImageTranslation
 *
 * @property array $languages public alias of {@link _languages}.
 * @property array $existences public alias of {@link _existences}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.imagetranslation
 */
class ImageTranslationFilter extends CModel {
	const EXISTENCE_PRESENT = 'present';
	const EXISTENCE_MISSING = 'missing';

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
	 * @var array set of existence conditions.
	 * Array key is a language locale code, array value - existence condition,
	 * which may be equal {@link EXISTENCE_PRESENT} or {@link EXISTENCE_MISSING}.
	 * For example:
	 * <code>
	 * array(
	 *    'en' => 'present',
	 *    'ge' => 'missing',
	 * );
	 * </code>
	 */
	protected $_existences = array();

	// Attributes:

	/**
	 * @var string file self name.
	 */
	public $name;
	/**
	 * @var integer image width in pixels.
	 */
	public $width;
	/**
	 * @var integer image height in pixels.
	 */
	public $height;

	public function __set($name, $value) {
		if (in_array($name, $this->existAttributeNames())) {
			$language = str_replace('exist_', '', $name);
			if (in_array($language, $this->getLanguages())) {
				$this->_existences[$language] = $value;
			} else {
				parent::__set($name,$value);
			}
		} else {
			parent::__set($name,$value);
		}
	}

	public function __get($name) {
		if (in_array($name, $this->existAttributeNames())) {
			$language = str_replace('exist_', '', $name);
			if (in_array($language, $this->getLanguages())) {
				return array_key_exists($language, $this->_existences) ? $this->_existences[$language] : null;
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

	public function setExistences(array $existences) {
		$this->_existences = $existences;
		return true;
	}

	public function getExistences() {
		return $this->_existences;
	}

	public function setExistence($language, $existence) {
		$this->_existences[$language] = $existence;
		return true;
	}

	public function getExistence($language) {
		return $this->_existences[$language];
	}

	/**
	 * Initializes {@link languages} value.
	 * @return boolean success.
	 */
	protected function initLanguages() {
		$languages = array();
		$languageManager = $this->getImageTranslationModule()->getComponent('languageManager');
		$languageModels = $languageManager->getLanguages();
		foreach ($languageModels as $languageModel) {
			$languages[] = $languageModel->locale_code;
		}
		$this->_languages = $languages;
		return true;
	}

	/**
	 * Returns image translation module.
	 * @return CModule image translation module.
	 */
	public function getImageTranslationModule() {
		$module = null;
		$currentController = Yii::app()->getController();
		if (is_object($currentController)) {
			$module = $currentController->getModule();
		}
		if (!is_object($module)) {
			$module = Yii::app()->getModule('imagetranslation');
		}
		return $module;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		$attributeNames = array(
			'name',
			'width',
			'height',
		);
		$attributeNames = array_merge($attributeNames, $this->existAttributeNames());
		return $attributeNames;
	}

	/**
	 * Returns the list of attribute names of the model,
	 * which mark translation existence.
	 * @return array list of attribute names.
	 */
	public function existAttributeNames() {
		$attributeNames = array();
		foreach ($this->getLanguages() as $language) {
			$attributeNames[] = 'exist_' . $language;
		}
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
	 * Applies filter for the models list.
	 * @param array $models raw models list
	 * @return array filtered models list.
	 */
	public function apply(array $models) {
		return array_filter($models, array($this, 'checkModelAllowed'));
	}

	/**
	 * Checks if given model is allowed by this filter.
	 * @param CModel $model model for checking
	 * @return boolean model allowed.
	 */
	public function checkModelAllowed(CModel $model) {
		if (!empty($this->name)) {
			if (strpos($model->name, $this->name)===false) {
				return false;
			}
		}
		if (!empty($this->width) && is_numeric($this->width)) {
			if ($model->width != $this->width) {
				return false;
			}
		}
		if (!empty($this->height) && is_numeric($this->height)) {
			if ($model->height != $this->height) {
				return false;
			}
		}

		foreach ($this->_existences as $language => $existence) {
			switch ($existence) {
				case self::EXISTENCE_PRESENT: {
					if (!$model->exists($language)) {
						return false;
					}
					break;
				}
				case self::EXISTENCE_MISSING: {
					if ($model->exists($language)) {
						return false;
					}
					break;
				}
			};
		}

		return true;
	}
}
