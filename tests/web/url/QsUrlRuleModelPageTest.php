<?php

/**
 * Test case for the extension "qs.web.url.QsUrlRuleModelPage".
 * @see QsUrlRuleModelPage
 */
class QsUrlRuleModelPageTest extends CTestCase {
	/**
	 * @var CComponent backup for the {@link CHttpRequest} component.
	 */
	protected static $_requestBackup = null;
	/**
	 * @var array backup for the values of the $_SERVER super global array.
	 */
	protected $_serverBackup = null;

	public static function setUpBeforeClass() {
		Yii::app()->urlManager; // make sure "system.web.CUrlManager.php" has been included.
		Yii::import('qs.web.url.QsUrlRuleModelPage');
		Yii::import('qs.db.ar.*');

		// Components:
		self::$_requestBackup = Yii::app()->getRequest();
		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);

		// Database:
		$testTableName = self::getTestTableName();

		$dbSetUp = new QsTestDbMigration();
		$columns = array(
			'id' => 'pk',
			'name' => 'string',
		);
		$dbSetUp->createTable($testTableName, $columns);

		$activeRecordGenerator = new QsTestActiveRecordGenerator();
		$activeRecordGenerator->generate(array('tableName' => $testTableName));
	}

	public static function tearDownAfterClass() {
		// Components:
		Yii::app()->setComponent('request', self::$_requestBackup);
		// Database:
		$dbSetUp = new QsTestDbMigration();
		$dbSetUp->dropTable(self::getTestTableName());
	}

	public function setUp() {
		$this->_serverBackup = $_SERVER;

		$dbSetUp = new QsTestDbMigration();
		$testTableName = self::getTestTableName();

		$dbSetUp->truncateTable($testTableName);
		for ($i=1; $i<=10; $i++) {
			$columns = array(
				'name' => 'qqname_'.$i,
			);
			$dbSetUp->insert($testTableName, $columns);
		}
	}

	public function tearDown() {
		$_SERVER = $this->_serverBackup;
		//Yii::app()->cache->flush();
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
	 * Creates the test URL manager instance.
	 * @param array $urlRuleConfig configuration array for the test URL rule.
	 * @return CUrlManager URL manager instance
	 */
	protected function createTestUrlManager(array $urlRuleConfig=array()) {
		$urlManagerConfig = array(
			'class' => 'CUrlManager',
			'urlFormat' => 'path',
			'showScriptName' => false,
			'rules' => array(
				$this->adjustTestUrlRuleConfig($urlRuleConfig),
				'/' => 'site/index',
				'<controller:\w+>/<id:\d+>*' => '<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>*' => '<controller>/<action>',
				'<controller:\w+>/<action:\w+>*' => '<controller>/<action>',
			)
		);
		$urlManager = Yii::createComponent($urlManagerConfig);
		$urlManager->init();
		return $urlManager;
	}

	/**
	 * Adjusts the given URL rule config, filling up default data.
	 * @param array $urlRuleConfig raw URL rule config.
	 * @return array adjusted URL rule config.
	 */
	protected function adjustTestUrlRuleConfig(array $urlRuleConfig) {
		if (!array_key_exists('class', $urlRuleConfig)) {
			$urlRuleConfig['class'] = 'QsUrlRuleModelPage';
		}
		if (!array_key_exists('modelCacheDuration', $urlRuleConfig)) {
			$urlRuleConfig['modelCacheDuration'] = -1;
		}
		return $urlRuleConfig;
	}

	/**
	 * Creates a {@link CHttpRequest} instance for the give URI.
	 * @param string $requestUri request URI tail
	 * @return QsTestHttpRequest http request component instance.
	 */
	protected function createHttpRequestForUri($requestUri) {
		$originalServerRequestUri = Yii::app()->getRequest()->getRequestUri();
		$_SERVER['REQUEST_URI'] = $originalServerRequestUri.$requestUri;
		$httpRequest = Yii::createComponent(array('class'=>'QsTestHttpRequest'));
		$httpRequest->init();
		$httpRequest->getRequestUri();
		$_SERVER['REQUEST_URI'] = $originalServerRequestUri;
		return $httpRequest;
	}

	// Tests:

	public function testCreate() {
		$urlRule = new QsUrlRuleModelPage();
		$this->assertTrue(is_object($urlRule), 'Unable to create "QsUrlRuleModelPage" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$urlRule = new QsUrlRuleModelPage();

		$testModelClassName = 'TestModelClassName';
		$this->assertTrue($urlRule->setModelClassName($testModelClassName), 'Unable to set model class name!');
		$this->assertEquals($urlRule->getModelClassName(), $testModelClassName, 'Unable to set model class name correctly!');

		$testModels = array(
			'model_1' => new CFormModel(),
			'model_2' => new CFormModel(),
		);
		$this->assertTrue($urlRule->setModels($testModels), 'Unable to set models!');
		$this->assertEquals($urlRule->getModels(), $testModels, 'Unable to set models correctly!');

		$testCacheDuration = rand(20, 100);
		$this->assertTrue($urlRule->setModelCacheDuration($testCacheDuration), 'Unable to set model cache duration!');
		$this->assertEquals($urlRule->getModelCacheDuration(), $testCacheDuration, 'Unable to set model cache duration correctly!');

		$testModelUrlKeywordAttributeName = 'testModelUrlKeywordAttributeName';
		$this->assertTrue($urlRule->setModelUrlKeywordAttributeName($testModelUrlKeywordAttributeName), 'Unable to set model url keyword attribute name!');
		$this->assertEquals($urlRule->getModelUrlKeywordAttributeName(), $testModelUrlKeywordAttributeName, 'Unable to set model url keyword attribute name correctly!');

		$testModelGetParamName = 'testGetParamName';
		$this->assertTrue($urlRule->setModelGetParamName($testModelGetParamName), 'Unable to set model GET param name!');
		$this->assertEquals($urlRule->getModelGetParamName(), $testModelGetParamName, 'Unable to set model GET param name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testParseUrl() {
		$modelPageRoute = 'test_controller/test_action';
		$modelClassName = self::getTestActiveRecordClassName();
		$modelUrlKeywordAttributeName = 'name';
		$modelGetParamName = 'test_get_param_name';
		$urlRulePattern = '<'.$modelGetParamName.':\w+>';

		$urlRuleConfig = array(
			'modelClassName' => $modelClassName,
			'modelUrlKeywordAttributeName' => $modelUrlKeywordAttributeName,
			'pattern' => $urlRulePattern,
			'route' => $modelPageRoute,
			'modelGetParamName' => $modelGetParamName,
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$pageModels = CActiveRecord::model($modelClassName)->findAll();
		foreach ($pageModels as $pageModel) {
			$httpRequest = $this->createHttpRequestForUri($pageModel->$modelUrlKeywordAttributeName);
			$parsedUrl = $urlManager->parseUrl($httpRequest);
			$expectedParsedUrl = $modelPageRoute;
			$this->assertEquals($expectedParsedUrl, $parsedUrl, "Unable to parse model page '{$pageModel->$modelUrlKeywordAttributeName}' URL.");
			$this->assertEquals($pageModel->attributes, $_GET[$modelGetParamName]->attributes, 'Page model has not been append to the GET!');
		}
	}

	/**
	 * @depends testSetGet
	 */
	public function testCreateUrlByModel() {
		$modelPageRoute = 'test_controller/test_action';
		$modelClassName = self::getTestActiveRecordClassName();
		$modelUrlKeywordAttributeName = 'name';
		$modelGetParamName = 'test_get_param_name';
		$urlRulePattern = '<'.$modelGetParamName.':\w+>';

		$urlRuleConfig = array(
			'modelClassName' => $modelClassName,
			'modelUrlKeywordAttributeName' => $modelUrlKeywordAttributeName,
			'pattern' => $urlRulePattern,
			'route' => $modelPageRoute,
			'modelGetParamName' => $modelGetParamName,
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$pageModels = CActiveRecord::model($modelClassName)->findAll();
		foreach ($pageModels as $pageModel) {
			$urlParams = array(
				$modelGetParamName=>$pageModel
			);
			$createdUrl = $urlManager->createUrl($modelPageRoute, $urlParams);
			$expectedCreatedUrl = $urlManager->baseUrl.'/'.$pageModel->$modelUrlKeywordAttributeName;
			$this->assertEquals($expectedCreatedUrl, $createdUrl, "Unable to create model page '{$pageModel->$modelUrlKeywordAttributeName}' URL.");
		}
	}

	/**
	 * @depends testCreateUrlByModel
	 */
	public function testCreateUrlByGetKeyword() {
		$modelPageRoute = 'test_controller/test_action';
		$modelClassName = self::getTestActiveRecordClassName();
		$modelUrlKeywordAttributeName = 'name';
		$modelGetParamName = 'test_get_param_name';
		$urlRulePattern = '<'.$modelGetParamName.':\w+>';

		$urlRuleConfig = array(
			'modelClassName' => $modelClassName,
			'modelUrlKeywordAttributeName' => $modelUrlKeywordAttributeName,
			'pattern' => $urlRulePattern,
			'route' => $modelPageRoute,
			'modelGetParamName' => $modelGetParamName,
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$pageModels = CActiveRecord::model($modelClassName)->findAll();
		foreach ($pageModels as $pageModel) {
			$urlParams = array(
				$modelGetParamName => $pageModel->$modelUrlKeywordAttributeName
			);
			$createdUrl = $urlManager->createUrl($modelPageRoute, $urlParams);
			$expectedCreatedUrl = $urlManager->baseUrl.'/'.$pageModel->$modelUrlKeywordAttributeName;
			$this->assertEquals($expectedCreatedUrl, $createdUrl, "Unable to create model page '{$pageModel->$modelUrlKeywordAttributeName}' URL.");
		}
	}

	/**
	 * @depends testParseUrl
	 */
	public function testParseUrlWIthAdditionalParams() {
		$testAdditionalParamName = 'test_additional_param_name';
		$testAdditionalParamValue = 'test_additional_param_value';

		$modelPageRoute = 'test_controller/test_action';
		$modelClassName = self::getTestActiveRecordClassName();
		$modelUrlKeywordAttributeName = 'name';
		$modelGetParamName = 'test_get_param_name';
		$urlRulePattern = '<'.$testAdditionalParamName.':\w+>/<'.$modelGetParamName.':\w+>';

		$urlRuleConfig = array(
			'modelClassName' => $modelClassName,
			'modelUrlKeywordAttributeName' => $modelUrlKeywordAttributeName,
			'pattern' => $urlRulePattern,
			'route' => $modelPageRoute,
			'modelGetParamName' => $modelGetParamName,
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$pageModels = CActiveRecord::model($modelClassName)->findAll();
		foreach ($pageModels as $pageModel) {
			$httpRequest = $this->createHttpRequestForUri($testAdditionalParamValue.'/'.$pageModel->$modelUrlKeywordAttributeName);
			$parsedUrl = $urlManager->parseUrl($httpRequest);
			$expectedParsedUrl = $modelPageRoute;
			$this->assertEquals($expectedParsedUrl, $parsedUrl, "Unable to parse model page '{$pageModel->$modelUrlKeywordAttributeName}' URL.");
			$this->assertEquals($pageModel->attributes, $_GET[$modelGetParamName]->attributes, 'Page model has not been append to the GET!');
			$this->assertEquals($testAdditionalParamValue, $_GET[$testAdditionalParamName], 'Additional param has not been append to the GET!');
		}
	}

	/**
	 * @depends testCreateUrlByModel
	 */
	public function testCreateUrlWithAdditionalParams() {
		$testAdditionalParamName = 'test_additional_param_name';
		$testAdditionalParamValue = 'test_additional_param_value';

		$modelPageRoute = 'test_controller/test_action';
		$modelClassName = self::getTestActiveRecordClassName();
		$modelUrlKeywordAttributeName = 'name';
		$modelGetParamName = 'test_get_param_name';
		$urlRulePattern = '<'.$testAdditionalParamName.':\w+>/<'.$modelGetParamName.':\w+>';

		$urlRuleConfig = array(
			'modelClassName' => $modelClassName,
			'modelUrlKeywordAttributeName' => $modelUrlKeywordAttributeName,
			'pattern' => $urlRulePattern,
			'route' => $modelPageRoute,
			'modelGetParamName' => $modelGetParamName,
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$pageModels = CActiveRecord::model($modelClassName)->findAll();
		foreach ($pageModels as $pageModel) {
			$urlParams = array(
				$modelGetParamName => $pageModel,
				$testAdditionalParamName => $testAdditionalParamValue,
			);
			$createdUrl = $urlManager->createUrl($modelPageRoute, $urlParams);
			$expectedCreatedUrl = $urlManager->baseUrl.'/'.$testAdditionalParamValue.'/'.$pageModel->$modelUrlKeywordAttributeName;
			$this->assertEquals($expectedCreatedUrl, $createdUrl, "Unable to create model page '{$pageModel->$modelUrlKeywordAttributeName}' URL.");
		}
	}
}
