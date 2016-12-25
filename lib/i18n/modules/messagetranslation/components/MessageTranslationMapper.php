<?php
/**
 * MessageTranslationMapper class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * MessageTranslationMapper is a base message translation model mapper.
 * Translation mapper uses source translation files for the default site language as a map
 * for new translations.
 * 
 * @see MessageTranslation.
 *
 * @property string $defaultMessagePath public alias of {@link _defaultMessagePath}.
 * @property array $messageCategoryNames public alias of {@link _messageCategoryNames}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
abstract class MessageTranslationMapper extends CApplicationComponent {
	/**
	 * @var string path to the default message translation files.
	 * For example: "/path/to/application/messages/en_us"
	 */
	protected $_defaultMessagePath = '';
	/**
	 * @var array translation message category names.
	 * This field will be filled from files at {@link defaultMessagePath}.
	 */
	protected $_messageCategoryNames = array();

	// Set / Get :

	public function setDefaultMessagePath($defaultMessagePath) {
		if (!is_string($defaultMessagePath)) {
			throw new CException('"' . get_class($this) . '::defaultMessagePath" should be a string!');
		}
		$this->_defaultMessagePath = $defaultMessagePath;
		return true;
	}

	public function getDefaultMessagePath() {
		if (empty($this->_defaultMessagePath)) {
			$this->initDefaultMessagePath();
		}
		return $this->_defaultMessagePath;
	}

	public function setMessageCategoryNames(array $messageCategoryNames) {
		$this->_messageCategoryNames = $messageCategoryNames;
		return true;
	}

	public function getMessageCategoryNames() {
		if (empty($this->_messageCategoryNames)) {
			$this->initMessageCategoryNames();
		}
		return $this->_messageCategoryNames;
	}

	/**
	 * Initializes {@link defaultMessagePath} value.
	 * Attempts to get default value from {@link CMessageSource} component.
	 * @return boolean success.
	 */
	protected function initDefaultMessagePath() {
		$defaultMessagePath = '';

		$messageSource = Yii::app()->getMessages();
		if (is_a($messageSource, 'CPhpMessageSource')) {
			$defaultMessagePath = $messageSource->basePath;
		}
		if (empty($defaultMessagePath)) {
			$defaultMessagePath = Yii::getPathOfAlias('application.messages');
		}

		$defaultLanguage = $messageSource->getLanguage();
		$defaultMessagePath .= DIRECTORY_SEPARATOR . $defaultLanguage;

		$this->_defaultMessagePath = $defaultMessagePath;
		return true;
	}

	/**
	 * Initializes {@link messageCategoryNames} value.
	 * @return boolean success.
	 */
	protected function initMessageCategoryNames() {
		$messageCategoryNames = array();
		$defaultMessageFiles = $this->findDefaultMessageFiles();
		foreach ($defaultMessageFiles as $defaultMessageFile) {
			$messageCategoryName = basename($defaultMessageFile, '.php');
			$messageCategoryNames[] = $messageCategoryName;
		}
		$this->_messageCategoryNames = $messageCategoryNames;
		return true;
	}

	/**
	 * Saves the message translation model.
	 * @param MessageTranslation $model message translation model.
	 * @return boolean success.
	 */
	public function save(MessageTranslation $model) {
		return $this->saveTranslation($model->category_name, $model->name, $model->language, $model->content);
	}

	/**
	 * Finds all message translation models, which match the filter.
	 * @param MessageTranslationFilter $filter search filter.
	 * @return array list of message translation models.
	 */
	public function findAll($filter=null) {
		$filter = $this->fetchFilter($filter);
		$models = $this->findDefaults();
		$messageTranslations = $this->findTranslations($filter);
		if (is_array($messageTranslations)) {
			foreach ($messageTranslations as $messageTranslation) {
				$messageId = $this->composeMessageId($messageTranslation['category'], $messageTranslation['name']);
				if (array_key_exists($messageId, $models)) {
					$model = $models[$messageId];
					$model->addTranslation($messageTranslation['language'], $messageTranslation['content']);
				}
			}
		}
		$models = $filter->apply($models);
		return array_values($models);
	}

	/**
	 * Finds the translation models from default translation files.
	 * Returns array of {@link MessageTranslation} models, which keys
	 * created by {@link composeMessageId} method.
	 * @return MessageTranslation[] list of message translation models.
	 */
	public function findDefaults() {
		$defaultMessageFiles = $this->findDefaultMessageFiles();
		$models = array();
		foreach ($defaultMessageFiles as $defaultMessageFile) {
			$defaultMessages = require($defaultMessageFile);
			$categoryName = basename($defaultMessageFile, '.php');
			foreach ($defaultMessages as $messageName => $messageContent) {
				$model = new MessageTranslation();
				$model->name = $messageName;
				$model->category_name = $categoryName;
				$model->default_content = $messageContent;
				$models[$this->composeMessageId($categoryName, $messageName)] = $model;
			}
		}
		return $models;
	}

	/**
	 * Returns the list of default translation message files from
	 * the {@link defaultMessagePath} path.
	 * @return array list of default message file names.
	 */
	protected function findDefaultMessageFiles() {
		$defaultMessagePath = $this->getDefaultMessagePath();
		$fileSearchOptions = array(
			'fileTypes' => array(
				'php',
			),
		);
		$defaultMessageFiles = CFileHelper::findFiles($defaultMessagePath, $fileSearchOptions);
		return $defaultMessageFiles;
	}

	/**
	 * Composes message category and name into the id string.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @return string message id.
	 */
	protected function composeMessageId($category, $name) {
		return $category . DIRECTORY_SEPARATOR . $name;
	}

	/**
	 * Fetches filter object from mixed filter configuration.
	 * @param mixed $filter {@link MessageTranslationFilter} instance, configuration array or name condition.
	 * @return MessageTranslationFilter filter instance.
	 */
	protected function fetchFilter($filter) {
		if (is_object($filter)) {
			$filterObject = $filter;
		} else {
			if (is_array($filter)) {
				$filterObject = new MessageTranslationFilter();
				foreach ($filter as $name => $value) {
					$filterObject->$name = $value;
				}
			} else {
				$filterObject = new MessageTranslationFilter();
				if (!empty($filter)) {
					$filterObject->name = $filter;
				}
			}
		}
		return $filterObject;
	}

	/**
	 * Saves the particular message translation content on particular language.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @param string $language language locale code.
	 * @param string $content message content on specified language.
	 * @return boolean success.
	 */
	abstract protected function saveTranslation($category, $name, $language, $content);

	/**
	 * Finds existing message translations.
	 * While results filtering is performed separately,
	 * passed search filter can be used to filter translation list at this
	 * stage to save performance.
	 * @param MessageTranslationFilter $filter search filter.
	 * @return array list of translation data, each translation data is an array
	 * with following keys: 'name', 'category', 'language', 'content'.
	 */
	abstract protected function findTranslations(MessageTranslationFilter $filter);
}
