<?php
 
/**
 * Test case for the extension "qs.web.url.QsUrlRuleModuleDefault".
 * @see QsUrlRuleModuleDefault
 */
class QsUrlRuleModuleDefaultTest extends CTestCase {
	/**
	 * @var array backup for the values of the $_SERVER super global array.
	 */
	protected $_serverBackup = null;
	/**
	 * @var CComponent backup for the {@link CHttpRequest} component.
	 */
	protected $_requestBackup = null;
	/**
	 * @var array backup for the Application modules list.
	 */
	protected $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::app()->urlManager; // make sure "system.web.CUrlManager.php" has been included.
		Yii::import('qs.web.url.QsUrlRuleModuleDefault');
	}

	public function setUp() {
		$this->_serverBackup = $_SERVER;

		$this->_requestBackup = Yii::app()->getRequest();

		$this->_modulesBackup = Yii::app()->getModules();

		Yii::app()->setModules($this->getTestModulesConfig());

		$mockRequestConfig = array(
			'class' => 'QsTestHttpRequest'
		);
		$mockRequest = Yii::createComponent($mockRequestConfig);
		Yii::app()->setComponent('request', $mockRequest);
	}

	public function tearDown() {
		$_SERVER = $this->_serverBackup;
		Yii::app()->setComponent('request', $this->_requestBackup);
		Yii::app()->setModules($this->_modulesBackup);
		//Yii::app()->cache->flush();
	}

	/**
	 * Returns the configuration for URL rule,
	 * which is under test.
	 * @param array $urlRuleConfig raw URL rule config.
	 * @return array URL rule configuration.
	 */
	protected function adjustUrlRuleConfig(array $urlRuleConfig) {
		if (!array_key_exists('class', $urlRuleConfig)) {
			$urlRuleConfig['class'] = 'QsUrlRuleModuleDefault';
		}
		return $urlRuleConfig;
	}

	/**
	 * Creates the test URL manager insatance, which uses
	 * the URL rule, which is under the test.
	 * @param array $urlRuleConfig configuration array for the test URL rule.
	 * @return CUrlManager test URL manager instance.
	 */
	protected function createTestUrlManager(array $urlRuleConfig=array()) {
		$urlRuleConfig = $this->adjustUrlRuleConfig($urlRuleConfig);
		$urlManagerConfig = array(
			'class' => 'CUrlManager',
			'urlFormat' => 'path',
			'showScriptName' => false,
			'rules' => array(
				$urlRuleConfig,
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
	 * Returns the test module name.
	 * @return string test module name.
	 */
	protected function getTestModuleName() {
		return 'test_module';
	}

	/**
	 * Returns the configuration array for the test module.
	 * @return array module configuration array.
	 */
	protected function getTestModuleConfig() {
		$testModuleConfig = array(
			'class' => 'system.gii.GiiModule',
			'ipFilters' => array(
				'127.0.0.1',
			),
			'password' => 'test_module_password',
		);
		return $testModuleConfig;
	}

	/**
	 * Returns the test configuration array for the application modules.
	 * @return array application modules configuration array.
	 */
	protected function getTestModulesConfig() {
		$testModuleName = $this->getTestModuleName();
		$testModuleConfig = $this->getTestModuleConfig();
		$testModulesConfig = array(
			$testModuleName => $testModuleConfig
		);
		return $testModulesConfig;
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
		$urlRule = new QsUrlRuleModuleDefault();
		$this->assertTrue(is_object($urlRule), 'Unable to create "QsUrlRuleModuleDefault" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testParseUrlSingleModuleName() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();

		$originalServerRequestUri = Yii::app()->getRequest()->getRequestUri();
		$requestUriAddon = $testModuleName;
		$_SERVER['REQUEST_URI'] = $originalServerRequestUri.$requestUriAddon;

		$httpRequest = Yii::createComponent(array('class'=>'QsTestHttpRequest'));
		$parsedUrl = $urlManager->parseUrl($httpRequest);

		$expectedParsedUrl = $testModuleName;
		$this->assertEquals($expectedParsedUrl, $parsedUrl, 'Unable to parse URL with the single module name.');
	}

	/**
	 * @depends testParseUrlSingleModuleName
	 */
	public function testParseUrlModuleController() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';

		$httpRequest = $this->createHttpRequestForUri($testModuleName.'/'.$testControllerName);

		$parsedUrl = $urlManager->parseUrl($httpRequest);

		$expectedParsedUrl = $testModuleName.'/'.$testControllerName;
		$this->assertEquals($expectedParsedUrl, $parsedUrl, 'Unable to parse URL with module and controller are mentioned.');
	}

	/**
	 * @depends testParseUrlModuleController
	 */
	public function testParseUrlModuleControllerAction() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';

		$httpRequest = $this->createHttpRequestForUri($testModuleName.'/'.$testControllerName.'/'.$testActionName);
		$parsedUrl = $urlManager->parseUrl($httpRequest);

		$expectedParsedUrl = $testModuleName.'/'.$testControllerName.'/'.$testActionName;
		$this->assertEquals($expectedParsedUrl, $parsedUrl, 'Unable to parse URL with module, controller and action are mentioned.');
	}

	/**
	 * @depends testParseUrlModuleControllerAction
	 */
	public function testParseUrlWithAdditionalParams() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';
		$testParamName = 'test_param_name';
		$testParamValue = 'test_param_value';

		$httpRequest = $this->createHttpRequestForUri($testModuleName.'/'.$testControllerName.'/'.$testActionName.'/'.$testParamName.'/'.$testParamValue);
		$parsedUrl = $urlManager->parseUrl($httpRequest);

		$expectedParsedUrl = $testModuleName.'/'.$testControllerName.'/'.$testActionName;
		$this->assertEquals($expectedParsedUrl, $parsedUrl, 'Unable to parse URL with additional parameters.');
		$this->assertTrue(array_key_exists($testParamName, $_GET), 'Unable to parse additional parameter!');
		$this->assertEquals($testParamValue, $_GET[$testParamName], 'Unable to parse additional parameter correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testCreateUrlSingleModuleName() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();

		$url = $urlManager->createUrl($testModuleName);
		$expectedUrl = $urlManager->baseUrl.'/'.$testModuleName;

		$this->assertEquals($expectedUrl, $url, 'Unable to create URL with single module name is mentioned.');
	}

	/**
	 * @depends testCreateUrlSingleModuleName
	 */
	public function testCreateUrlModuleController() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';

		$url = $urlManager->createUrl($testModuleName.'/'.$testControllerName);
		$expectedUrl = $urlManager->baseUrl.'/'.$testModuleName.'/'.$testControllerName;

		$this->assertEquals($expectedUrl, $url, 'Unable to create URL with module and controller are mentioned.');
	}

	/**
	 * @depends testCreateUrlModuleController
	 */
	public function testCreateUrlModuleControllerAction() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';

		$url = $urlManager->createUrl($testModuleName.'/'.$testControllerName.'/'.$testActionName);
		$expectedUrl = $urlManager->baseUrl.'/'.$testModuleName.'/'.$testControllerName.'/'.$testActionName;

		$this->assertEquals($expectedUrl, $url, 'Unable to create URL with module, controller and action are mentioned.');
	}

	/**
	 * @depends testCreateUrlModuleController
	 */
	public function testCreateUrlWithAdditionalParams() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';
		$testParams = array(
			'test_param_name_1' => 'test_param_value_1',
			'test_param_name_2' => 'test_param_value_2',
		);

		$url = $urlManager->createUrl($testModuleName.'/'.$testControllerName.'/'.$testActionName, $testParams);
		$expectedUrl = $urlManager->baseUrl.'/'.$testModuleName.'/'.$testControllerName.'/'.$testActionName;
		foreach ($testParams as $paramName => $paramValue) {
			$expectedUrl .= "/{$paramName}/{$paramValue}";
		}

		$this->assertEquals($expectedUrl, $url, 'Unable to create URL with additional parameters.');
	}

	/**
	 * @depends testCreateUrlWithAdditionalParams
	 */
	public function testCreateUrlWithAdditionalParamsDefaultController() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testParams = array(
			'test_param_name_1' => 'test_param_value_1',
			'test_param_name_2' => 'test_param_value_2',
		);

		$url = $urlManager->createUrl($testModuleName, $testParams);

		$defaultControllerName = Yii::app()->getModule($testModuleName)->defaultController;
		$defaultActionName = 'index';

		$expectedUrl = $urlManager->baseUrl.'/'.$testModuleName.'/'.$defaultControllerName.'/'.$defaultActionName;
		foreach ($testParams as $paramName => $paramValue) {
			$expectedUrl .= "/{$paramName}/{$paramValue}";
		}

		$this->assertEquals($expectedUrl, $url, 'Unable to create URL with additional parameters for default controller.');
	}

	/**
	 * @depends testParseUrlWithAdditionalParams
	 */
	public function testManageUrlWithArrayValueParams() {
		$urlManager = $this->createTestUrlManager();

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';
		$testParams = array(
			'test_param_name_1' => array(
				'test_param_name_1_1' => 'test_param_value_1_1',
				'test_param_name_1_2' => 'test_param_value_1_2',
			),
			'test_param_name_2' => array(
				'test_param_name_2_1' => 'test_param_value_2_1',
				'test_param_name_2_2' => 'test_param_value_2_2',
			),
		);

		$url = $urlManager->createUrl("{$testModuleName}/{$testControllerName}/{$testActionName}", $testParams);

		$_SERVER['REQUEST_URI'] = $url;
		$httpRequest = Yii::createComponent(array('class'=>'QsTestHttpRequest'));

		$urlManager->parseUrl($httpRequest);

		$this->assertEquals($testParams, $_GET, 'Unable to manage URL with params contained array values!');
	}

	/**
	 * @depends testParseUrlModuleControllerAction
	 */
	public function testParseUrlUsingPatternWithAdditionalParams() {
		$testAdditionalParamName = 'test_additional_param_name';
		$testAdditionalParamValue = 'test_additional_param_value';

		$urlRuleConfig = array(
			'pattern' => '<'.$testAdditionalParamName.':\w+>/<moduleRoute:(\w+(\/\w+(\/\w+)?)?)>*'
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';

		$httpRequest = $this->createHttpRequestForUri($testAdditionalParamValue.'/'.$testModuleName.'/'.$testControllerName.'/'.$testActionName);
		$parsedUrl = $urlManager->parseUrl($httpRequest);

		$expectedParsedUrl = $testModuleName.'/'.$testControllerName.'/'.$testActionName;
		$this->assertEquals($expectedParsedUrl, $parsedUrl, "Unable to parse URL, using pattern with additional params.");
		$this->assertEquals($testAdditionalParamValue, $_GET[$testAdditionalParamName], 'Additional param has not been append to the GET!');
	}

	/**
	 * @depends testCreateUrlModuleControllerAction
	 */
	public function testCreateUrlUsingPatterWithAdditionalParams() {
		$testAdditionalParamName = 'test_additional_param_name';
		$testAdditionalParamValue = 'test_additional_param_value';

		$urlRuleConfig = array(
			'pattern' => '<'.$testAdditionalParamName.':\w+>/<moduleRoute:(\w+(\/\w+(\/\w+)?)?)>*'
		);
		$urlManager = $this->createTestUrlManager($urlRuleConfig);

		$testModuleName = $this->getTestModuleName();
		$testControllerName = 'test_controller';
		$testActionName = 'test_action';

		$testParams = array(
			$testAdditionalParamName => $testAdditionalParamValue
		);
		$url = $urlManager->createUrl("{$testModuleName}/{$testControllerName}/{$testActionName}", $testParams);

		$expectedUrl = $urlManager->baseUrl.'/'.$testAdditionalParamValue.'/'.$testModuleName.'/'.$testControllerName.'/'.$testActionName;
		$this->assertEquals($expectedUrl, $url, 'Unable to create URL, using pattern with additional params.');
	}
}
