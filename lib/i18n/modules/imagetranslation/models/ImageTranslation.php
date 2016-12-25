<?php
/**
 * ImageTranslation class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * ImageTranslation is a model for the image translations.
 * Each image, which should be translated can be represented with this class.
 *
 * @see ImageTranslationFilter
 *
 * @property string $defaultBasePath public alias of {@link _defaultBasePath}.
 * @property string $defaultBaseUrl public alias of {@link _defaultBaseUrl}.
 * @property string $missingImageUrl public alias of {@link _missingImageUrl}.
 * @property CUploadedFile $file public alias of {@link _file}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.imagetranslation
 */
class ImageTranslation extends CModel {
	/**
	 * @var array static model finders.
	 */
	private static $_models = array();

	/**
	 * @var string default image translation base file path.
	 */
	protected $_defaultBasePath = '';
	/**
	 * @var string default image translation base file URL.
	 */
	protected $_defaultBaseUrl = '';
	/**
	 * @var string URL for the image, which should indicates the translation is missing.
	 */
	protected $_missingImageUrl = '';

	// Attributes:

	/**
	 * @var string file self name.
	 */
	public $name;
	/**
	 * @var string language locale code.
	 */
	public $language;
	/**
	 * @var integer image width in pixels.
	 */
	public $width;
	/**
	 * @var integer image height in pixels.
	 */
	public $height;
	/**
	 * @var CUploadedFile uploaded file.
	 */
	protected $_file;

	/**
	 * Returns the static model finder of the specified image translation class.
	 * @param string $className image translation class name.
	 * @return ImageTranslation image translation model finder.
	 */
	public static function model($className = __CLASS__) {
		if (array_key_exists($className, self::$_models)) {
			return self::$_models[$className];
		} else {
			$model = self::$_models[$className] = new $className(null);
			return $model;
		}
	}

	/**
	 * Constructor.
	 * @param string $scenario name of the scenario that this model is used in.
	 * See {@link CModel::scenario} on how scenario is used by models.
	 * @see getScenario
	 */
	public function __construct($scenario = '') {
		$this->setScenario($scenario);
		$this->language = Yii::app()->language;
		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();
	}

	// Set / Get :

	public function setDefaultBasePath($defaultImageBasePath) {
		$this->_defaultBasePath = $defaultImageBasePath;
		return true;
	}

	public function getDefaultBasePath() {
		if (empty($this->_defaultBasePath)) {
			$this->initDefaultBasePath();
		}
		return $this->_defaultBasePath;
	}

	public function setDefaultBaseUrl($defaultImageBaseUrl) {
		$this->_defaultBaseUrl = $defaultImageBaseUrl;
		return true;
	}

	public function getDefaultBaseUrl() {
		if (empty($this->_defaultBaseUrl)) {
			$this->initDefaultBaseUrl();
		}
		return $this->_defaultBaseUrl;
	}

	public function setMissingImageUrl($missingImageUrl) {
		if (!is_string($missingImageUrl)) {
			throw new CException('"' . get_class($this) . '::missingImageUrl" should be a string!');
		}
		$this->_missingImageUrl = $missingImageUrl;
		return true;
	}

	public function getMissingImageUrl() {
		if (empty($this->_missingImageUrl)) {
			$this->initMissingImageUrl();
		}
		return $this->_missingImageUrl;
	}

	public function setFile($file) {
		$this->_file = $file;
		return true;
	}

	public function getFile() {
		if (!is_object($this->_file)) {
			$this->_file = $this->getUploadedFile();
		}
		return $this->_file;
	}

	/**
	 * Returns the model related uploaded file.
	 * @return CUploadedFile related uploaded file.
	 */
	public function getUploadedFile() {
		return CUploadedFile::getInstance($this, 'file');
	}

	/**
	 * Initializes value of {@link defaultBasePath}.
	 * @return boolean true.
	 */
	protected function initDefaultBasePath() {
		$this->_defaultBasePath = $this->getTranslationSource()->getNormalizedDefaultBasePath();
		return true;
	}

	/**
	 * Initializes value of {@link defaultBaseUrl}.
	 * @return boolean true.
	 */
	protected function initDefaultBaseUrl() {
		$this->_defaultBaseUrl = $this->getTranslationSource()->getNormalizedDefaultBaseUrl();
		return true;
	}

	/**
	 * Initializes value of {@link missingImageUrl}.
	 * @return boolean success.
	 */
	protected function initMissingImageUrl() {
		$module = $this->getImageTranslationModule();
		$assetsUrl = $module->getAssetsUrl();
		$this->_missingImageUrl = $assetsUrl . '/images/missing_image.jpg';
		return true;
	}

	/**
	 * Returns image translation source application component.
	 * @return QsImageTranslationSource image translation source component.
	 */
	public function getTranslationSource() {
		$module = $this->getImageTranslationModule();
		return $module->getImageTranslationSource();
	}

	/**
	 * Returns image translation module.
	 * @return ImagetranslationModule image translation module.
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
		return array(
			'name',
			'language',
			'width',
			'height',
			'file',
		);
	}

	/**
	 * Defines the validation rules.
	 * @return array list of validation rules.
	 */
	public function rules() {
		return array(
			array('name, language, file', 'required'),
			array('file', 'file', 'skipOnError'=>true),
			array('file', 'validateImageSize', 'skipOnError'=>true),
		);
	}

	/**
	 * Validate uploaded image file size.
	 * @param string $attribute attribute name
	 * @param array $params validation parameters
	 */
	public function validateImageSize($attribute, $params) {
		$file = $this->$attribute;
		if (is_object($file)) {
			$fileName = $file->getTempName();
			@list($imageWidth, $imageHeight) = getimagesize($fileName, $imageInfo);
			if (empty($imageWidth) || empty($imageHeight)) {
				$this->addError($attribute, "Uploaded file should be an image.");
			} elseif ( ($this->width>0 && $this->width!=$imageWidth) || ($this->height>0 && $this->height!=$imageHeight) ) {
				$this->addError($attribute, "Uploaded image should be {$this->width}x{$this->height} size.");
			}
		}
	}

	/**
	 * Returns the default (without translation) image URL
	 * @return string default image URL.
	 */
	public function getDefaultUrl() {
		return $this->getDefaultBaseUrl() . '/' . $this->name;
	}

	/**
	 * Checks if translation exists.
	 * @param string $language - required language, if empty {@link language} value will be used.
	 * @return boolean translation exists.
	 */
	public function exists($language = null) {
		if (!$language) {
			$language = $this->language;
		}
		return $this->getTranslationSource()->translationExists($this->name, $language);
	}

	/**
	 * Get URL for the image, including translation
	 * @param string $language - required language, if empty {@link language} value will be used.
	 * @return string image URL.
	 */
	public function getUrl($language = null) {
		if (!$language) {
			$language = $this->language;
		}
		return $this->getTranslationSource()->translate($this->name, $language);
	}

	/**
	 * Fetches the image URL.
	 * If translation exists its URL will be returned,
	 * if translation is missing the URL for the "missing image" icon will be returned.
	 * @param string $language - required language, if empty {@link language} value will be used.
	 * @return string image URL.
	 */
	public function fetchUrl($language = null) {
		if ($this->exists($language)) {
			return $this->getUrl($language);
		} else {
			$missingImageUrl = $this->getMissingImageUrl();
			return $missingImageUrl;
		}
	}

	/**
	 * Finds all image translation models, which are exist at
	 * {@link defaultBasePath}.
	 * Filter can be specified using {@link ImageTranslationFilter} instance.
	 * @param mixed $filter filter model instance or attribute list.
	 * @return array list of models.
	 */
	public function findAll($filter = null) {
		$models = array();

		$filePath = $this->getDefaultBasePath();
		$findFileOptions = array(
			'exclude' => array(
				'.svn',
				'CVS',
				'.cvsignore',
			),
		);
		$files = CFileHelper::findFiles($filePath, $findFileOptions);
		if (is_array($files)) {
			$filterModel = $this->fetchFilterModel($filter);
			foreach ($files as $file) {
				$model = $this->populateModel($file, $filePath);
				if (!is_object($filterModel) || $filterModel->checkModelAllowed($model)) {
					$models[] = $model;
				}
			}
		}
		return $models;
	}

	/**
	 * Fetches filter model instance.
	 * @param mixed $filter filter model instance or array of filter attributes
	 * @return ImageTranslationFilter
	 */
	protected function fetchFilterModel($filter) {
		if (is_null($filter)) {
			return $filter;
		}
		if (is_scalar($filter)) {
			$filter = array(
				'name' => $filter
			);
		}
		if (is_object($filter)) {
			return $filter;
		} elseif (is_array($filter)) {
			$filterModel = new ImageTranslationFilter();
			$filterModel->attributes = $filter;
			return $filterModel;
		}
	}

	/**
	 * Creates new model instance and fills it up.
	 * @param string $fileFullName full file name.
	 * @param string $fileBasePath file path.
	 * @return ImageTranslation model instance.
	 */
	protected function populateModel($fileFullName, $fileBasePath) {
		$modelClassName = get_class($this);
		$model = new $modelClassName();

		$fileSelfName = str_replace($fileBasePath . DIRECTORY_SEPARATOR, '', $fileFullName);

		$model->setDefaultBasePath($this->getDefaultBasePath());
		$model->setDefaultBaseUrl($this->getDefaultBaseUrl());
		$model->setMissingImageUrl($this->getMissingImageUrl());

		$model->name = $fileSelfName;
		$model->language = $this->language;

		@list($imageWidth, $imageHeight) = getimagesize($fileFullName);
		$model->width = $imageWidth;
		$model->height = $imageHeight;

		return $model;
	}

	/**
	 * Finds the image translation by its file name.
	 * @param string $name - file self name.
	 * @return ImageTranslation if model exists, null otherwise.
	 */
	public function findByName($name) {
		$filePath = $this->getDefaultBasePath();
		$fullFileName = $filePath . DIRECTORY_SEPARATOR . $name;
		if (!file_exists($fullFileName)) {
			return null;
		}
		return $this->populateModel($fullFileName, $filePath);
	}

	/**
	 * Saves the current image translation.
	 * @param boolean $runValidation whether to perform validation before saving the model.
	 * If the validation fails, the model will not be saved.
	 * @return boolean whether the saving succeeds.
	 */
	public function save($runValidation = true) {
		if (!$runValidation || $this->validate()) {
			$uploadedFile = $this->file;
			if (is_object($uploadedFile)) {
				$translationSource = $this->getTranslationSource();
				$srcFileName = $uploadedFile->getTempName();
				return $translationSource->saveTranslation($srcFileName, $this->name, $this->language);
			}
		}
		return false;
	}

	/**
	 * Creates new data provider for the model list.
	 * @param mixed $filter filter model instance or attribute list.
	 * @return CArrayDataProvider data provider.
	 */
	public function dataProvider($filter = null) {
		$rawData = $this->findAll($filter);

		$options = array(
			'id' => get_class($this),
			'keyField' => 'name',
			'sort' => array(
				'attributes' => array(
					'name', 'width', 'height',
				),
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		);
		$dataProvider = new CArrayDataProvider($rawData, $options);
		return $dataProvider;
	}
}
