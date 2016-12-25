<?php

/**
 * Test case for the extension "qs.i18n.QsApplicationLanguage".
 * @see QsApplicationLanguage
 */
class QsQsApplicationLanguageTest extends CTestCase {
	/**
	 * @var CApplicationComponent request application component backup
	 */
	protected $_requestBackup = null;
	/**
	 * @var CApplicationComponent params application component backup
	 */
	protected $_paramsBackup = null;
	/**
	 * @var CApplicationComponent cache application component backup
	 */
	protected $_cacheBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.QsApplicationLanguage');

		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
			'native_name' => 'string',
			'code' => 'string',
			'locale_code' => 'string',
			'html_code' => 'string',
		);
		$dbSetUp->createTable($testTableName, $columns);

		for ($i=1; $i<=3; $i++) {
			$columns = array(
				'name' => 'test_name_'.$i,
				'native_name' => 'test_name_'.$i,
				'code' => 'c'.$i,
				'locale_code' => 'l'.$i,
				'html_code' => 'h'.$i,
			);
			$dbSetUp->insert($testTableName, $columns);
		}

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(
			array(
				'tableName' => $testTableName,
			)
		);
	}

	public static function tearDownAfterClass() {
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$this->_requestBackup = Yii::app()->getRequest();

		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);

		$this->_paramsBackup = clone Yii::app()->params;

		if (Yii::app()->hasComponent('cache')) {
			$this->_cacheBackup = Yii::app()->getComponent('cache');
			$dummyCacheComponentConfig = array(
				'class' => 'CDummyCache'
			);
			$dummyCacheComponent = Yii::createComponent($dummyCacheComponentConfig);
			Yii::app()->setComponent('cache', $dummyCacheComponent);
		}
	}

	public function tearDown() {
		Yii::app()->setComponent('request',$this->_requestBackup);
		Yii::app()->params = $this->_paramsBackup;

		if (is_object($this->_cacheBackup)) {
			Yii::app()->setComponent('cache', $this->_cacheBackup);
		}
	}

	/**
	 * Returns the name of the test table.
	 * @return string test table name.
	 */
	public static function getTestTableName() {
		return 'test_'.__CLASS__.'_'.getmypid();
	}

	/**
	 * Returns the name of the test active record class.
	 * @return string test active record class name.
	 */
	public static function getTestActiveRecordClassName() {
		return self::getTestTableName();
	}

	/**
	 * Creates test application language component.
	 * @return QsApplicationLanguage application language component instance.
	 */
	protected function createApplicationLanguage() {
		$componentConfig = array(
			'class' => 'QsApplicationLanguage',
			'languageModelClassName' => self::getTestActiveRecordClassName(),
			'cacheDuration' => -1,
		);
		$applicationLanguage = Yii::createComponent($componentConfig);
		return $applicationLanguage;
	}

	/**
	 * @return CActiveRecord test active record finder.
	 */
	protected function getTestActiveRecordFinder() {
		return CActiveRecord::model(self::getTestActiveRecordClassName());
	}

	// Tests:

	public function testSetGet() {
		$applicationLanguage = new QsApplicationLanguage();

		$testLanguageModelClassName = 'TestLanguageModelClassName';
		$this->assertTrue($applicationLanguage->setLanguageModelClassName($testLanguageModelClassName), 'Unable to set language model class name!');
		$this->assertEquals($applicationLanguage->getLanguageModelClassName(), $testLanguageModelClassName, 'Unable to set language model class name correctly!');

		$testLanguageModelSearchCriteria = array(
			'condition' => 'test condition'
		);
		$this->assertTrue($applicationLanguage->setLanguageModelSearchCriteria($testLanguageModelSearchCriteria), 'Unable to set language model search criteria!');
		$this->assertEquals($applicationLanguage->getLanguageModelSearchCriteria(), $testLanguageModelSearchCriteria, 'Unable to set language model search criteria correctly!');

		$testLanguages = $this->getTestActiveRecordFinder()->findAll();
		$this->assertTrue($applicationLanguage->setLanguages($testLanguages), 'Unable to set languages!');
		$this->assertEquals($applicationLanguage->getLanguages(), $testLanguages, 'Unable to set languages correctly!');

		$testCurrent = $this->getTestActiveRecordFinder()->find();
		$this->assertTrue($applicationLanguage->setCurrent($testCurrent), 'Unable to set current language!');
		$this->assertEquals($applicationLanguage->getCurrent(), $testCurrent, 'Unable to set current language correctly!');

		$testGetParamName = 'test_language_get_param_name';
		$this->assertTrue($applicationLanguage->setGetParamName($testGetParamName), 'Unable to set GET param name!');
		$this->assertEquals($applicationLanguage->getGetParamName(), $testGetParamName, 'Unable to set GET param name correctly!');

		$testCookieLifetime = rand(1, 1000);
		$this->assertTrue($applicationLanguage->setCookieLifetime($testCookieLifetime), 'Unable to set cookie lifetime!');
		$this->assertEquals($applicationLanguage->getCookieLifetime(), $testCookieLifetime, 'Unable to set cookie lifetime correctly!');

		$testCacheDuration = rand(1, 1000);
		$this->assertTrue($applicationLanguage->setCacheDuration($testCacheDuration), 'Unable to set cache duration!');
		$this->assertEquals($applicationLanguage->getCacheDuration(), $testCacheDuration, 'Unable to set cache duration correctly!');

		$testCountryLanguageCodes = array(
			'test_country_1' => 'test_language_1',
			'test_country_2' => 'test_language_2',
		);
		$this->assertTrue($applicationLanguage->setCountryLanguageCodes($testCountryLanguageCodes), 'Unable to set country language codes!');
		$this->assertEquals($applicationLanguage->getCountryLanguageCodes(), $testCountryLanguageCodes, 'Unable to set country language codes correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetLanguages() {
		$applicationLanguage = $this->createApplicationLanguage();

		$testCriteria = array(
			'condition' => "name LIKE '%1%'",
		);
		$dbLanguages = $this->getTestActiveRecordFinder()->findAll($testCriteria);

		$applicationLanguage->setLanguageModelSearchCriteria($testCriteria);
		$languages = $applicationLanguage->getLanguages();

		$this->assertEquals(count($languages), count($dbLanguages), 'Count of returned languages missmatch the number of records in the database!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testApplyCurrent() {
		$applicationLanguage = $this->createApplicationLanguage();

		$className = self::getTestActiveRecordClassName();
		$testCurrent = new $className();
		$testCurrent->locale_code = 'test_locale_code';

		$applicationLanguage->setCurrent($testCurrent);

		$this->assertEquals(Yii::app()->language, $testCurrent->locale_code, 'Application language does not applied!');
		//$this->assertEquals(Yii::app()->sourceLanguage, $testCurrent->locale_code, 'Application source language does not applied!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineCurrentLanguageFromGet() {
		$applicationLanguage = $this->createApplicationLanguage();

		$testGetParamName = 'test_get_param_name';
		$applicationLanguage->setGetParamName($testGetParamName);

		$testLanguage = $this->getTestActiveRecordFinder()->find(array('order'=>'RAND()'));

		$_GET[$testGetParamName] = $testLanguage->code;

		$applicationLanguage->determineCurrent();

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language from GET!');
		$this->assertEquals($determinedCurrentLanguage->id, $testLanguage->id, 'Unable to determine current language from GET correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineCurrentLanguageFromCookie() {
		$applicationLanguage = $this->createApplicationLanguage();

		$testLanguage = $this->getTestActiveRecordFinder()->find(array('order'=>'RAND()'));

		$_COOKIE[$applicationLanguage->getCookieName()] = $testLanguage->code;

		$exceptionCaught = false;
		try {
			$applicationLanguage->determineCurrent();
		} catch (QsTestExceptionRedirect $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'No redirect, while URL do not contain language!');

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language from COOKIE!');
		$this->assertEquals($determinedCurrentLanguage->id, $testLanguage->id, 'Unable to determine current language from COOKIE correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineCurrentLanguageByIp() {
		$applicationLanguage = $this->createApplicationLanguage();

		$testLanguage = $this->getTestActiveRecordFinder()->find(array('order'=>'RAND()'));

		$testClientIp = '209.85.173.147';
		$_SERVER['REMOTE_ADDR'] = $testClientIp;

		$testCountryLanguageCodes = array(
			'US' => $testLanguage->code
		);
		$applicationLanguage->setCountryLanguageCodes($testCountryLanguageCodes);


		$exceptionCaught = false;
		try {
			$applicationLanguage->determineCurrent();
		} catch (QsTestExceptionRedirect $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'No redirect, while URL do not contain language!');

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language by IP!');
		$this->assertEquals($determinedCurrentLanguage->id, $testLanguage->id, 'Unable to determine current language by IP correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testDetermineCurrentLanguageByPredefinedLanguage() {
		$applicationLanguage = $this->createApplicationLanguage();

		$_COOKIE = array();
		$_GET = array();

		$testLanguage = $this->getTestActiveRecordFinder()->find(array('order'=>'RAND()'));

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = "{$testLanguage->code}-{$testLanguage->code},{$testLanguage->code};q=0.8,en-us;q=0.5,en;q=0.3";

		$exceptionCaught = false;
		try {
			$applicationLanguage->determineCurrent();
		} catch (QsTestExceptionRedirect $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'No redirect, while URL do not contain language!');

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language from predefined language!');
		$this->assertEquals($determinedCurrentLanguage->id, $testLanguage->id, 'Unable to determine current language from predefined language correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLanguage() {
		$applicationLanguage = $this->createApplicationLanguage();

		$_COOKIE = array();
		$_GET = array();

		$testLanguage = $this->getTestActiveRecordFinder()->find(array('order'=>'RAND()'));
		Yii::app()->params['site_default_language_id'] = $testLanguage->id;

		$exceptionCaught = false;
		try {
			$applicationLanguage->determineCurrent();
		} catch (QsTestExceptionRedirect $exception) {
			$exceptionCaught = true;
		}
		$this->assertTrue($exceptionCaught, 'No redirect, while URL do not contain language!');

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language as default language!');
		$this->assertEquals($testLanguage->id, $determinedCurrentLanguage->id, 'Unable to determine current language as default language correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testCreateSwitchUrl() {
		$applicationLanguage = $this->createApplicationLanguage();

		$languages = $this->getTestActiveRecordFinder()->findAll(array('order'=>'RAND()'));
		list($currentlanguage, $newLanguage) = $languages;
		if (empty($newLanguage)) {
			$newLanguage = $currentlanguage;
		}

		$testRequestUri = '/'.$currentlanguage->code.'/test/uri/tail';
		$_SERVER['REQUEST_URI'] = $testRequestUri;
		$_GET[$applicationLanguage->getGetParamName()] = $currentlanguage->code;

		$applicationLanguage->determineCurrent();

		$switchUrl = $applicationLanguage->createSwitchUrl($newLanguage);

		$expectedSwitchUrl = str_replace("/{$currentlanguage->code}/", "/{$newLanguage->code}/", $testRequestUri);

		$this->assertEquals($expectedSwitchUrl, $switchUrl , 'Unable to create switch URL!');
	}

	/**
	 * @depends testGetDefaultLanguage
	 * @depends testDetermineCurrentLanguageFromGet
	 */
	public function testDetermineCurrentLanguageInPassiveMode() {
		$applicationLanguage = $this->createApplicationLanguage();

		$applicationLanguage->setIsPassiveMode(true);
		$testGetParamName = 'test_get_param_name';
		$applicationLanguage->setGetParamName($testGetParamName);

		$defaultLanguage = $applicationLanguage->getDefault();

		$criteria = array(
			'condition' => 'id <> :defaultLanguageId',
			'params' => array(
				'defaultLanguageId' => $defaultLanguage->getPrimaryKey()
			),
			'order' => 'RAND()'
		);
		$testLanguage = $this->getTestActiveRecordFinder()->find($criteria);

		$_GET[$testGetParamName] = $testLanguage->code;

		$applicationLanguage->determineCurrent();

		$determinedCurrentLanguage = $applicationLanguage->getCurrent();

		$this->assertTrue(is_object($determinedCurrentLanguage), 'Unable to determine current language in passive mode!');
		$this->assertEquals($determinedCurrentLanguage->id, $defaultLanguage->id, 'Determined current language does not equals to default one!');
	}
}
