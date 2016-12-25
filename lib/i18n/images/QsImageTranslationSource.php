<?php
/**
 * QsImageTranslationSource class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsImageTranslationSource is a base class for all locale image translation sources.
 *
 * A image translation source is an application component that provides images internationalization (i18n).
 * It stores image URLs translated in different languages and provides
 * these translated versions when requested.
 *
 * A concrete class must implement {@link loadImageTranslation} or override {@link translateImage}.
 *
 * Application configuration example:
 * <code>
 * array(
 *     'components' => array(
 *         ...
 *         'imageTranslationSource' => array(
 *             'class' => '...',
 *         ),
 *         ...
 *     )
 * );
 * </code>
 * After this component has been attached to the application, you should use {@link translate} method to
 * get actual image URL. For example:
 * <code>
 * Yii::app()->imageTranslationSource->translate('buttons/add.gif');
 * </code>
 *
 * @property string $language public alias of {@link _language}.
 * @property array $imageUrls public alias of {@link _imageUrls}.
 * @property string $normalizedDefaultBaseUrl public alias of {@link _normalizedDefaultBaseUrl}.
 * @property string $normalizedDefaultBasePath public alias of {@link _normalizedDefaultBasePath}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.images
 */
abstract class QsImageTranslationSource extends CApplicationComponent {
	/**
	 * @var boolean whether to force image translation when the source and target languages are the same.
	 * Defaults to false, meaning translation is only performed when source and target languages are different.
	 */
	public $forceTranslation = false;
	/**
	 * @var boolean whether to check image translation existence.
	 * While enabled this feature allows to return default image URL if its translation not found.
	 * You can increase the performance by disabling this feature.
	 */
	public $checkTranslationExists = true;
	/**
	 * @var string current language code.
	 */
	protected $_language = null;
	/**
	 * @var array list of the translated image URLs.
	 */
	protected $_imageUrls = array();
	/**
	 * @var string default base URL.
	 * This URL is used to compose image URL if its translation missing or not required.
	 * If the value is not an absolute link, it will be append to the {@link CHttpRequest::baseUrl} value.
	 */
	public $defaultBaseUrl = 'images';
	/**
	 * @var string base file path for the default images.
	 * This path may be used by components, which manage the image translations.
	 * If the value is not an absolute path, it will be append to the value of <code>Yii::getPathOfAlias('webroot')</code>.
	 */
	public $defaultBasePath = 'images';
	/**
	 * @var string stores normalized value of {@link defaultBaseUrl}.
	 */
	protected $_normalizedDefaultBaseUrl = '';
	/**
	 * @var string stores normalized value of {@link defaultBasePath}.
	 */
	protected $_normalizedDefaultBasePath = '';

	/**
	 * @return string the language that the source messages are written in.
	 * Defaults to {@link CApplication::language application language}.
	 */
	public function getLanguage() {
		return $this->_language===null ? Yii::app()->sourceLanguage : $this->_language;
	}

	/**
	 * @param string $language the language that the source messages are written in.
	 * @return boolean success
	 */
	public function setLanguage($language) {
		$this->_language = CLocale::getCanonicalID($language);
		return true;
	}

	/**
	 * Returns the normalized value of (@link defaultBaseUrl).
	 * @return string normalized default base URL.
	 */
	public function getNormalizedDefaultBaseUrl() {
		if (empty($this->_normalizedDefaultBaseUrl)) {
			$this->_normalizedDefaultBaseUrl = $this->normalizeDefaultBaseUrl();
		}
		return $this->_normalizedDefaultBaseUrl;
	}

	/**
	 * Returns the normalized value of (@link defaultBasePath).
	 * @return string normalized default base path.
	 */
	public function getNormalizedDefaultBasePath() {
		if (empty($this->_normalizedDefaultBasePath)) {
			$this->_normalizedDefaultBasePath = $this->normalizeDefaultBasePath();
		}
		return $this->_normalizedDefaultBasePath;
	}

	/**
	 * Initializes {@link normalizedDefaultBaseUrl}, with the normalized value of
	 * {@link defaultBaseUrl}.
	 * If {@link defaultBaseUrl} is not an absolute URL, it will be append to the value of
	 * {@link CHttpRequest::baseUrl}.
	 * @return string normalized default base URL.
	 */
	protected function normalizeDefaultBaseUrl() {
		$baseUrl = $this->defaultBaseUrl;
		if (!preg_match('%^[a-z]+://.+$%is',$baseUrl)) {
			$baseUrl = Yii::app()->getRequest()->getBaseUrl() . '/' . ltrim($baseUrl, '/');
		}
		return $baseUrl;
	}

	/**
	 * Initializes {@link normalizedDefaultBasePath}, with the normalized value of
	 * {@link defaultBasePath}.
	 * If {@link defaultBasePath} is not an absolute path, it will be append to the value of
	 * <code>Yii::getPathOfAlias('webroot')</code>.
	 * @return string normalized default base path.
	 */
	protected function normalizeDefaultBasePath() {
		$basePath = $this->defaultBasePath;
		if (!preg_match('%^/.+$%is', $basePath)) {
			$basePath = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . $basePath;
		}
		return $basePath;
	}

	/**
	 * Translates an image to the specified language, which means find its actual URL.
	 *
	 * Note, if the specified language is the same as
	 * the {@link getLanguage source message language}, images will NOT be translated.
	 *
	 * If the image is not found in the translations, an {@link onMissingTranslation}
	 * event will be raised. Handlers can mark this message or do some
	 * default handling.
	 *
	 * @param string $imageName the image self name
	 * @param string $language the target language. If null (default), the {@link CApplication::getLanguage application language} will be used.
	 * @return string the translated message (or the original message if translation is not needed)
	 */
	public function translate($imageName, $language = null) {
		if ($language===null) {
			$language = Yii::app()->getLanguage();
		}
		if ($this->forceTranslation || $language !== $this->getLanguage()) {
			return $this->translateImage($imageName, $language);
		} else {
			return $this->getDefaultImageUrl($imageName);
		}
	}

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return boolean image translation exists.
	 */
	public function translationExists($imageName, $language = null) {
		if ($language === null) {
			$language = Yii::app()->getLanguage();
		}
		return $this->imageTranslationExists($imageName, $language);
	}

	/**
	 * Saves the translation for the particular image.
	 * @param string $srcFileName the source file name.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return boolean image translation exists.
	 */
	public function saveTranslation($srcFileName, $imageName, $language = null) {
		if ($language === null) {
			$language = Yii::app()->getLanguage();
		}
		return $this->saveImageTranslation($imageName, $language, $srcFileName);
	}

	/**
	 * Returns the default image URL, without translation.
	 * @param string $imageName image self name.
	 * @return string
	 */
	public function getDefaultImageUrl($imageName) {
		return $this->getNormalizedDefaultBaseUrl() . '/' . $imageName;
	}

	/**
	 * Translates the specified image, retrieving its actual URL.
	 * If the image URL is not found, an {@link onMissingTranslation}
	 * event will be raised.
	 * @param string $imageName the image self name
	 * @param string $language the target language
	 * @return string the translated image URL
	 */
	protected function translateImage($imageName, $language) {
		$key = $language;
		if (!array_key_exists($key, $this->_imageUrls)) {
			$this->_imageUrls[$key] = array();
		}
		if (array_key_exists($imageName, $this->_imageUrls[$key])) {
			return $this->_imageUrls[$key][$imageName];
		} else {
			if (!$this->checkTranslationExists || $this->imageTranslationExists($imageName, $language)) {
				$imageUrl = $this->loadImageTranslation($imageName, $language);
				if (!empty($imageUrl)) {
					$this->_imageUrls[$key][$imageName] = $imageUrl;
					return $imageUrl;
				}
			}
			if ($this->hasEventHandler('onMissingTranslation')) {
				$eventParams = array(
					'imageName' => $imageName,
					'language' => $language,
				);
				$event = new CEvent($this, $eventParams);
				$this->onMissingTranslation($event);
				if (is_array($event->params) && array_key_exists('imageUrl', $event->params)) {
					return $event->params['imageUrl'];
				}
			}
		}
		return $this->getDefaultImageUrl($imageName);
	}

	/**
	 * Raised when an image cannot be translated.
	 * Handlers may log this image or do some default handling.
	 * The event related data is passed through {@link CEvent::params} property.
	 * If value CEvent::params['imageUrl'] is setup it will returned as image translation.
	 * @param CEvent $event the event parameter
	 */
	public function onMissingTranslation($event) {
		$this->raiseEvent('onMissingTranslation', $event);
	}

	/**
	 * Loads the image translation for the specified language.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return string the target image URL.
	 */
	abstract protected function loadImageTranslation($imageName, $language);

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @return boolean image translation exists.
	 */
	abstract protected function imageTranslationExists($imageName, $language);

	/**
	 * Checks if the image translation for the specified language exists.
	 * @param string $imageName the image self name.
	 * @param string $language the target language.
	 * @param string $srcFileName the source file name.
	 * @return boolean image translation exists.
	 */
	abstract protected function saveImageTranslation($imageName, $language, $srcFileName);
}
