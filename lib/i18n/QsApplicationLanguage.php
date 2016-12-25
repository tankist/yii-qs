<?php
/**
 * QsApplicationLanguage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Application component, which maintains the application current language.
 * It is responsible for the language determining and switching.
 *
 * Component analyzes request, attempting to find current language code from GET, COOKIE,
 * IP address and browser info.
 *
 * Application configuration example:
 * <code>
 * array(
 *     ...
 *     'components' => array(
 *         ...
 *         'lang' => array(
 *             'class' => 'qs.i18n.QsApplicationLanguage',
 *             'languageModelClassName' => 'Language',
 *             'languageModelSearchCriteria' => array(
 *                 'scopes' => array('active'),
 *             ),
 *         ),
 *         ...
 *     ),
 *     ...
 * );
 * </code>
 *
 * In order to use GET as the language code source you may use {@link QsUrlManagerDefaultParam} URL manager.
 *
 * This component uses active record model as the source for all possible application languages.
 * Db Migration example for the table, which stores languages:
 * <code>
 * $tableName = 'language';
 *
 * $columns = array(
 *     'id' => 'pk',
 *     'name' => 'string NOT NULL',
 *     'native_name' => 'string NOT NULL',
 *     'code' => 'varchar(5) NOT NULL',
 *     'locale_code' => 'varchar(5) NOT NULL',
 *     'html_code' => 'varchar(5) NOT NULL',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * $this->createIndex("idx_{$tableName}_code", $tableName, 'name', true);
 *
 * $data = array(
 *     'name' => 'English',
 *     'native_name' => 'English',
 *     'code' => 'en',
 *     'locale_code' => 'en_us',
 *     'html_code' => 'en-us',
 * );
 * $this->insert($tableName, $data);
 * </code>
 *
 * @see QsUrlManagerDefaultParam
 *
 * @property string $languageModelClassName public alias of {@link _languageModelClassName}.
 * @property CDbCriteria|array $languageModelSearchCriteria public alias of {@link _languageModelSearchCriteria}.
 * @property CActiveRecord[] $languages public alias of {@link _languages}.
 * @property CActiveRecord $current public alias of {@link _current}.
 * @property string $getParamName public alias of {@link _getParamName}.
 * @property integer $cookieLifetime public alias of {@link _cookieLifetime}.
 * @property integer $cacheDuration public alias of {@link _cacheDuration}.
 * @property boolean $isPassiveMode public alias of {@link _isPassiveMode}.
 * @property array $countryLanguageCodes public alias of {@link _countryLanguageCodes}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n
 */
class QsApplicationLanguage extends CApplicationComponent {
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
	/**
	 * @var CActiveRecord current language instance.
	 */
	protected $_current = null;
	/**
	 * @var string name of the GET parameter, which should determine the current language.
	 */
	protected $_getParamName = 'lang';
	/**
	 * @var integer language cookie lifetime in seconds.
	 */
	protected $_cookieLifetime = 2592000; // 60*60*24*30 - 1 month
	/**
	 * @var integer duration of cache for values, default value is 0, meaning cache is permanent.
	 */
	protected $_cacheDuration = 0;
	/**
	 * @var boolean determines if component should work in passive mode.
	 * In this mode component will not affect the application language settings and will apply
	 * current language to be equal to the default one.
	 */
	protected $_isPassiveMode = false;
	/**
	 * @var array map, which binds country code with the language code in format: countryCode => languageCode.
	 * If there is no match for particular country code the language code will be set equal to it.
	 */
	protected $_countryLanguageCodes = array();

	/**
	 * Initializes the application component.
	 * Determines current language.
	 */
	public function init() {
		if (!$this->getIsInitialized()) {
			$this->determineCurrent();
			parent::init();
		}
	}

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
		$this->initLanguages();
		return $this->_languages;
	}

	public function setCurrent(CActiveRecord $language) {
		$this->_current = $language;
		$this->applyCurrent();
		return true;
	}

	public function getCurrent() {
		return $this->_current;
	}

	public function setGetParamName($getParamName) {
		if (!is_string($getParamName)) {
			throw new CException('"' . get_class($this) . '::getParamName" should be a string!');
		}
		$this->_getParamName = $getParamName;
		return true;
	}

	public function getGetParamName() {
		return $this->_getParamName;
	}

	public function getCookieName() {
		$cookieName = str_replace(' ', '', Yii::app()->name) . 'Language';
		return $cookieName;
	}

	public function setCookieLifetime($cookieLifetime) {
		if (!is_numeric($cookieLifetime)) {
			throw new CException('"' . get_class($this) . '::cookieLifetime" should be an integer!');
		}
		$this->_cookieLifetime = $cookieLifetime;
		return true;
	}

	public function getCookieLifetime() {
		return $this->_cookieLifetime;
	}

	public function setCacheDuration($cacheDuration) {
		if (!is_numeric($cacheDuration)) {
			throw new CException('"' . get_class($this) . '::cacheDuration" should be an integer!');
		}
		$this->_cacheDuration = $cacheDuration;
		return true;
	}

	public function getCacheDuration() {
		return $this->_cacheDuration;
	}

	public function setIsPassiveMode($isPassiveMode) {
		$this->_isPassiveMode = $isPassiveMode;
		return true;
	}

	public function getIsPassiveMode() {
		return $this->_isPassiveMode;
	}

	public function setCountryLanguageCodes(array $countryLanguageCodes) {
		$this->_countryLanguageCodes = $countryLanguageCodes;
		return true;
	}

	public function getCountryLanguageCodes() {
		return $this->_countryLanguageCodes;
	}

	/**
	 * Returns languages list cache id.
	 * @return string cache id.
	 */
	public function getCacheId() {
		$cacheId = get_class($this) . 'LanguagesList';
		return $cacheId;
	}

	/**
	 * Returns default language primary key value.
	 * @return integer default language primary key.
	 */
	public function getDefaultPk() {
		return Yii::app()->params['site_default_language_id'];
	}

	/**
	 * Returns default language instance.
	 * @return CActiveRecord default language instance.
	 */
	public function getDefault() {
		$defaultLanguagePk = $this->getDefaultPk();
		$language = $this->findLanguageByPk($defaultLanguagePk);
		if (empty($language)) {
			list($language) = $this->getLanguages();
		}
		return $language;
	}

	/**
	 * Initializes application languages list.
	 * @return boolean success.
	 */
	protected function initLanguages() {
		if (!is_array($this->_languages)) {
			$languageFinder = CActiveRecord::model($this->getLanguageModelClassName());
			$languages = $this->getCacheLanguages();
			if (!is_array($languages)) {
				$languages = $languageFinder->findAll($this->getLanguageModelSearchCriteria());
				$this->setCacheLanguages($languages);
			}
			$this->_languages = $languages;
		}
		return true;
	}

	/**
	 * Set values into the cache.
	 * @param array $languages - list of language models.
	 * @return boolean success.
	 */
	protected function setCacheLanguages(array $languages) {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getCacheId();
			return Yii::app()->cache->set($cacheId, $languages, $this->getCacheDuration());
		}
		return false;
	}

	/**
	 * Returns values saved in cache.
	 * @return mixed array of cached records if success or false otherwise.
	 */
	protected function getCacheLanguages() {
		if (Yii::app()->hasComponent('cache')) {
			$cacheId = $this->getCacheId();
			return Yii::app()->cache->get($cacheId);
		}
		return false;
	}

	/**
	 * Applies current language to the application.
	 * @return boolean success.
	 */
	protected function applyCurrent() {
		if ($this->getIsPassiveMode()) {
			return false;
		}
		$language = $this->_current;
		Yii::app()->setLanguage($language->locale_code);
		$this->setCookie($language->code);
		return true;
	}

	/**
	 * Set cookie with the language code, so it can be restored later.
	 * @param string $languageCode language code to be saved.
	 * @return boolean success.
	 */
	protected function setCookie($languageCode) {
		if (headers_sent() || $this->getIsPassiveMode()) {
			return false;
		}
		$cookieName = $this->getCookieName();
		$cookie = new CHttpCookie($cookieName, $languageCode);
		$cookie->expire = time() + $this->getCookieLifetime();
		Yii::app()->request->cookies[$cookieName] = $cookie;
		return true;
	}

	/**
	 * Determines current application language,
	 * applies default language if fail to find language in environment.
	 * @return boolean success.
	 */
	public function determineCurrent() {
		if ($this->getIsPassiveMode()) {
			return $this->setCurrent($this->getDefault());
		}

		$languageCode = $this->determineCurrentCode();
		if ($languageCode) {
			$currentLanguage = $this->findLanguageByCode($languageCode);
		} else {
			$currentLanguage = null;
		}
		if (empty($currentLanguage)) {
			$currentLanguage = $this->getDefault();
		}

		$result = $this->setCurrent($currentLanguage);

		if (strcmp($this->determineCurrentCodeFromGet(), $currentLanguage->code) != 0) {
			$url = Yii::app()->createUrl('site/index', array($this->getGetParamName() => $currentLanguage->code));
			Yii::app()->request->redirect($url);
		}

		return $result;
	}

	/**
	 * Determines current application language code using GET, COOKIE and browser information.
	 * @return string language code.
	 */
	protected function determineCurrentCode() {
		$languageCode = $this->determineCurrentCodeFromGet();
		if (!$languageCode) {
			$languageCode = $this->determineCurrentCodeFromCookie();
			if (!$languageCode) {
				$languageCode = $this->determineCurrentCodeFromIp();
				if (!$languageCode || !$this->findLanguageByCode($languageCode)) {
					$languageCode = $this->determineCurrentCodeFromPreferredLanguage();
				}
			}
		}
		return $languageCode;
	}

	/**
	 * Determines current application language code using GET.
	 * @return string language code.
	 */
	protected function determineCurrentCodeFromGet() {
		$getParamName = $this->getGetParamName();
		return isset($_GET[$getParamName]) ? $_GET[$getParamName] : '';
	}

	/**
	 * Determines current application language code using COOKIE.
	 * @return string language code.
	 */
	protected function determineCurrentCodeFromCookie() {
		$cookie = Yii::app()->getRequest()->cookies[$this->getCookieName()];
		if (is_object($cookie)) {
			$code = $cookie->value;
		} else {
			$code = '';
		}
		return $code;
	}

	/**
	 * Determines current application language code using client IP address.
	 * @return string language code.
	 */
	protected function determineCurrentCodeFromIp() {
		$countryCode = $this->determineCurrentCountryCode();
		if (empty($countryCode)) {
			return false;
		}
		$countryCode = strtolower($countryCode);
		$countryLanguageCodes = array_change_key_case($this->getCountryLanguageCodes(), CASE_LOWER);
		if (array_key_exists($countryCode, $countryLanguageCodes)) {
			$code = $countryLanguageCodes[$countryCode];
		} else {
			$code = $countryCode;
		}
		return $code;
	}

	/**
	 * Determines the current request country code by remote IP address.
	 * @return string two letter country code.
	 */
	protected function determineCurrentCountryCode() {
		if (!isset($_SERVER['REMOTE_ADDR'])) {
			return '';
		}
		$clientIp = $_SERVER['REMOTE_ADDR'];
		if (extension_loaded('geoip')) {
			$countryCode = geoip_country_code_by_name($clientIp);
		} else {
			$countryCode = exec('whois '.escapeshellarg($clientIp).' | grep -i country');
			$countryCode = str_ireplace('country:', '', $countryCode);
			$countryCode = trim($countryCode);
		}
		return $countryCode;
	}

	/**
	 * Determines current application language code using browser information.
	 * @return string language code.
	 */
	protected function determineCurrentCodeFromPreferredLanguage() {
		$preferredLanguage = Yii::app()->request->getPreferredLanguage();
		$preferredLanguage = substr($preferredLanguage, 0, 2);
		return $preferredLanguage;
	}

	/**
	 * Finds the language with code matching the given one.
	 * @param string $languageCode code of searching language.
	 * @return CActiveRecord language instance.
	 */
	protected function findLanguageByCode($languageCode) {
		$languages = $this->getLanguages();
		foreach ($languages as $language) {
			if (strcasecmp($language->code, $languageCode)==0) {
				return $language;
			}
		}
		return null;
	}

	/**
	 * Finds the language with primary key matching the given one.
	 * @param integer $languagePk primary key of searching language.
	 * @return CActiveRecord language instance.
	 */
	protected function findLanguageByPk($languagePk) {
		$languages = $this->getLanguages();
		foreach ($languages as $language) {
			if (strcasecmp($language->getPrimaryKey(), $languagePk)==0) {
				return $language;
			}
		}
		return null;
	}

	/**
	 * Creates URL, which leads to the same page with the given language.
	 * @param CActiveRecord $language language to be switched .
	 * @return string URL leading to the page on given language.
	 */
	public function createSwitchUrl(CActiveRecord $language) {
		$currentLanguage = $this->getCurrent();
		if (empty($currentLanguage)) {
			$switchUrl = Yii::app()->createUrl('site/index', array($this->getGetParamName() => $language->code));
		} else {
			$controller = Yii::app()->getController();
			if (is_object($controller)) {
				$route = $controller->getRoute();
				$params = $_GET;
				$params[$this->getGetParamName()] = $language->code;
				$switchUrl = Yii::app()->createUrl($route, $params);
			} else {
				if (isset($_SERVER['REQUEST_URI'])) {
					$currentRequestUri = $_SERVER['REQUEST_URI'];
					$switchUrl = str_replace( "/{$this->current->code}/", "/{$language->code}/", $currentRequestUri.'/' );
					$switchUrl = rtrim($switchUrl, '/');
				} else {
					$switchUrl = "/{$language->code}/";
				}
			}
		}
		return $switchUrl;
	}

	/**
	 * A shortcut method to receive the current language primary key.
	 * @return integer current language primary key value.
	 */
	public function getCurrentPk() {
		return $this->getCurrent()->getPrimaryKey();
	}
}