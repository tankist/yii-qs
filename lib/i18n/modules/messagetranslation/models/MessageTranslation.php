 <?php
/**
 * MessageTranslation class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * MessageTranslation is a model for the message translations.
 * Each message, which should be translated can be represented with this class.
 *
 * @property array $translations public alias of {@link _translations}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class MessageTranslation extends CModel {
	/**
	 * @var array translations set in format: language => content.
	 */
	protected $_translations = array();

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
	/**
	 * @var string language locale code.
	 */
	public $language;
	/**
	 * @var string content of the message on the current language.
	 */
	public $content;

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

	public function __get($name) {
		if (stripos($name, 'content_')===0) {
			$language = str_replace('content_', '', $name);
			return $this->getTranslation($language);
		}
		return parent::__get($name);
	}

	// Set / Get :

	public function setId($id) {
		$decodedId = $this->decodeId($id);
		$this->category_name = $decodedId['category_name'];
		$this->name = $decodedId['name'];
		return true;
	}

	public function getId() {
		$rawId = $this->category_name . DIRECTORY_SEPARATOR . $this->name;
		$id = base64_encode($rawId);
		return $id;
	}

	public function setTranslations(array $translations) {
		foreach ($translations as $language => $content) {
			$this->addTranslation($language, $content);
		}
		return true;
	}

	public function getTranslations() {
		return $this->_translations;
	}

	public function addTranslation($language, $content) {
		$this->_translations[$language] = $content;
		return true;
	}

	public function getTranslation($language) {
		return $this->_translations[$language];
	}

	/**
	 * Encodes given name and category name into base 64 encoded string,
	 * which can be used to safely identifier the particular model.
	 * @param string $name message name.
	 * @param string $categoryName message category name.
	 * @return string encoded id value.
	 */
	protected function encodeId($name, $categoryName) {
		$rawId = $categoryName . DIRECTORY_SEPARATOR . $name;
		$id = base64_encode($rawId);
		return $id;
	}

	/**
	 * Decodes given id, determining model name and category name.
	 * @throws CException if unable to decode id correctly.
	 * @param string $id id value.
	 * @return array of 2 elements: 'name' and 'category_name'
	 */
	protected function decodeId($id) {
		$rawId = base64_decode($id);
		list($category_name, $name) = explode(DIRECTORY_SEPARATOR, $rawId, 2);
		if (empty($category_name) || empty($name)) {
			throw new CException('Wrong value for the "' . get_class($this) . '::id" has been set!');
		}
		$result = array(
			'category_name' => $category_name,
			'name' => $name,
		);
		return $result;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		$attributeNames = array(
			'id',
			'name',
			'category_name',
			'language',
			'content',
		);
		return $attributeNames;
	}

	/**
	 * Defines the validation rules.
	 * @return array list of validation rules.
	 */
	public function rules() {
		return array(
			array('name, category_name, language, content', 'required'),
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
	 * Returns model mapper instance.
	 * @return MessageTranslationMapper mapper instance.
	 */
	protected function getMapper() {
		$module = $this->getMessageTranslationModule();
		$mapper = $module->getComponent('messageTranslationMapper');
		return $mapper;
	}

	/**
	 * Finds all message translation models.
	 * Filter can be specified using {@link MessageTranslationFilter} instance.
	 * @param mixed $filter filter model instance or attribute list.
	 * @return array list of models.
	 */
	public function findAll($filter=null) {
		$mapper = $this->getMapper();
		return $mapper->findAll($filter);
	}

	/**
	 * Finds the message translation model by id.
	 * @param string $id composed message translation id.
	 * @return MessageTranslation message translation model.
	 * @see decodeId
	 */
	public function findById($id) {
		$decodedId = $this->decodeId($id);
		$name = $decodedId['name'];
		$category_name = $decodedId['category_name'];

		$filter = array(
			'name' => $name,
			'category_name' => $category_name,
		);
		list($model) = $this->findAll($filter);
		return $model;
	}

	/**
	 * Saves the current message translation.
	 * @param boolean $runValidation whether to perform validation before saving the model.
	 * If the validation fails, the model will not be saved.
	 * @return boolean whether the saving succeeds.
	 */
	public function save($runValidation = true) {
		if (!$runValidation || $this->validate()) {
			$mapper = $this->getMapper();
			return $mapper->save($this);
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
		$sortAttributes = array(
			'name',
			'category_name',
			'default_content'
		);
		$module = $this->getMessageTranslationModule();
		$languageManager = $module->getComponent('languageManager');
		foreach ($languageManager->getLanguages() as $language) {
			$sortAttributes[] = 'content_' . $language->locale_code;
		}
		$options = array(
			'id' => get_class($this),
			'keyField' => 'name',
			'sort' => array(
				'attributes' => $sortAttributes,
			),
			'pagination' => array(
				'pageSize' => 20,
			),
		);
		$dataProvider = new CArrayDataProvider($rawData, $options);
		return $dataProvider;
	}
}
