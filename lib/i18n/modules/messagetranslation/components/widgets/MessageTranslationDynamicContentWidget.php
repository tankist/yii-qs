<?php
/**
 * MessageTranslationDynamicContentWidget class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * MessageTranslationDynamicContentWidget is a widget, which allows to display current message translation
 * content in the input field, depending on language value, which is set up by HTML input field.
 *
 * @see MessageTranslation
 * @see MessagetranslationModule
 *
 * @property CModel $model public alias of {@link _model}.
 * @property string $languageInputId public alias of {@link _languageInputId}.
 * @property string $contentInputId public alias of {@link _contentInputId}.
 * @property array $languages public alias of {@link _languages}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class MessageTranslationDynamicContentWidget extends CWidget {
	/**
	 * @var CModel image translation model.
	 */
	protected $_model = null;
	/**
	 * @var string id of the HTML element, which should be a source of the language value.
	 * If not set this parameter will be initialized using {@link CHtml} methods from the {@link model} model.
	 */
	protected $_languageInputId = '';
	/**
	 * @var string id of the HTML element, which should be an input of the content value.
	 * If not set this parameter will be initialized using {@link CHtml} methods from the {@link model} model.
	 */
	protected $_contentInputId = '';
	/**
	 * @var array list of language locale codes.
	 */
	protected $_languages = array();

	public function setModel(CModel $model) {
		$this->_model = $model;
		return true;
	}

	public function getModel() {
		return $this->_model;
	}

	public function setLanguageInputId($languageInputId) {
		if (!is_string($languageInputId)) {
			throw new CException('"' . get_class($this) . '::languageInputId" should be a string!');
		}
		$this->_languageInputId = $languageInputId;
		return true;
	}

	public function getLanguageInputId() {
		if (empty($this->_languageInputId)) {
			$this->initLanguageInputId();
		}
		return $this->_languageInputId;
	}

	public function setContentInputId($contentInputId) {
		if (!is_string($contentInputId)) {
			throw new CException('"' . get_class($this) . '::contentInputId" should be a string!');
		}
		$this->_contentInputId = $contentInputId;
		return true;
	}

	public function getContentInputId() {
		if (empty($this->_contentInputId)) {
			$this->initContentInputId();
		}
		return $this->_contentInputId;
	}

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

	/**
	 * Initializes {@link languageInputId} value, using {@link CHtml} methods.
	 * @return boolean success.
	 */
	protected function initLanguageInputId() {
		if (is_object($this->_model)) {
			$modelAttribute = 'language';
			$languageInputName = CHtml::resolveName($this->_model, $modelAttribute);
			$languageInputId = CHtml::getIdByName($languageInputName);
			$this->_languageInputId = $languageInputId;
			return true;
		}
		return false;
	}

	/**
	 * Initializes {@link contentInputId} value, using {@link CHtml} methods.
	 * @return boolean success.
	 */
	protected function initContentInputId() {
		if (is_object($this->_model)) {
			$modelAttribute = 'content';
			$contentInputName = CHtml::resolveName($this->_model, $modelAttribute);
			$contentInputId = CHtml::getIdByName($contentInputName);
			$this->_contentInputId = $contentInputId;
			return true;
		}
		return false;
	}

	/**
	 * Initializes {@link languages} value, using 'languageManager' component.
	 * @return boolean success.
	 */
	protected function initLanguages() {
		$module = $this->getMessageTranslationModule();
		$languageManager = $module->getComponent('languageManager');
		$languageModels = $languageManager->getLanguages();
		$languages = array();
		if (is_array($languageModels)) {
			foreach ($languageModels as $languageModel) {
				$languages[] = $languageModel->locale_code;
			}
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
	 * Initializes the widget.
	 * This method is called by {@link CBaseController::createWidget}
	 * and {@link CBaseController::beginWidget} after the widget's
	 * properties have been initialized.
	 */
	public function init() {
		$model = $this->getModel();
		if (!is_object($model)) {
			throw new CException('"' . get_class($this) . '::model" should be setup!');
		}
		$this->echoJavaScript();
	}

	/**
	 * Outputs JavaScript code, which should update an image, generated by (@link echoImage()).
	 * @return void
	 */
	protected function echoJavaScript() {
		Yii::app()->getClientScript()->registerCoreScript('jquery');

		$model = $this->getModel();

		$caseStrings = array();
		foreach ($this->getLanguages() as $language ) {
			$translation = $model->getTranslation($language);
			$translation = str_replace("'","\\'", $translation);
			$caseStrings[] = "case '{$language}': contentValue = '" . $translation . "'; break;";
		}

		$updateContentInputCode = "
			var language = this.value;
			var contentValue = '';
			switch (language) { " . implode(' ', $caseStrings) . " }
			$('#" . $this->getContentInputId() . "').attr('value', contentValue);
		";

		$id = $this->getLanguageInputId();
		$event = 'change';
		$jsScript = "$('body').on('{$event}','#{$id}',function(){" . $updateContentInputCode . "})";
		echo CHtml::script($jsScript);

		$jsScript = "$('#{$id}').trigger('{$event}')";
		echo CHtml::script($jsScript);
	}
}
