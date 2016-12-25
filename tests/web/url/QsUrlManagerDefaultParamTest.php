<?php

/**
 * Test case for the extension "ext.url.QsUrlManagerDefaultParam".
 * @see QsUrlManagerDefaultParam
 */
class QsUrlManagerDefaultParamTest extends CTestCase {
	/**
	 * @var CUrlManager URL manager application component backup.
	 */
	protected $_urlManagerBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.web.url.QsUrlManagerDefaultParam');
	}

	public function setUp() {
		$this->_urlManagerBackup = clone Yii::app()->urlManager;
	}

	public function tearDown() {
		Yii::app()->setComponent('urlManager', $this->_urlManagerBackup);
		$_GET = array();
	}

	// Tests:
	
	public function testCreate() {
		$urlManager = new QsUrlManagerDefaultParam();
		$this->assertTrue(is_object($urlManager), 'Unable to create "QsUrlManagerDefaultParam" instance!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$urlManager = new QsUrlManagerDefaultParam();

		$testDefaultParamName = 'test_default_param_name';
		$this->assertTrue($urlManager->setDefaultParamName($testDefaultParamName), 'Unable to set default param name!');
		$this->assertEquals($urlManager->getDefaultParamName(), $testDefaultParamName, 'Unable to set default param name correctly!');

		$testIsDefaultParamPrependRoute = 'testIsDefaultParamPrependRoute';
		$this->assertTrue($urlManager->setIsDefaultParamPrependRoute($testIsDefaultParamPrependRoute), 'Unable to set "isDefaultParamPrependRoute"!');
		$this->assertEquals($urlManager->getIsDefaultParamPrependRoute(), $testIsDefaultParamPrependRoute, 'Unable to set "isDefaultParamPrependRoute" correctly!');

		$testIsDefaultParamNameDisplay = 'testIsDefaultParamNameDisplay';
		$this->assertTrue($urlManager->setIsDefaultParamNameDisplay($testIsDefaultParamNameDisplay), 'Unable to set "isDefaultParamNameDisplay"!');
		$this->assertEquals($urlManager->getIsDefaultParamNameDisplay(), $testIsDefaultParamNameDisplay, 'Unable to set "isDefaultParamNameDisplay" correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testCreateUrl() {
		$testDefaultParamName = 'test_default_param_name';

		$urlManagerConfig = array(
			'class' => 'qs.web.url.QsUrlManagerDefaultParam',
			'defaultParamName' => $testDefaultParamName,
			'isDefaultParamPrependRoute' => true,
			'isDefaultParamNameDisplay' => false,
			'urlFormat' => 'path',
			'showScriptName' => false,
			'rules' => array(),
		);
		$urlManager = Yii::createComponent($urlManagerConfig);

		$testDefaultParamValue = 'test_default_param_value';
		$_GET[$testDefaultParamName] = $testDefaultParamValue;

		$testRoute = 'test_controller/test_action';
		$createdUrl = $urlManager->createUrl($testRoute);

		$expectedCreatedUrl = Yii::app()->baseUrl.'/'.$testDefaultParamValue.'/'.$testRoute;
		$this->assertEquals($expectedCreatedUrl, $createdUrl, 'Unable create URL with default parameter as single word!');
	}

	/**
	 * @depends testCreateUrl
	 */
	public function testCreateUrlWithDefaultParamName() {
		$testDefaultParamName = 'test_default_param_name';

		$urlManagerConfig = array(
			'class'=>'qs.web.url.QsUrlManagerDefaultParam',
			'defaultParamName'=>$testDefaultParamName,
			'isDefaultParamPrependRoute'=>true,
			'isDefaultParamNameDisplay'=>true,
			'urlFormat'=>'path',
			'showScriptName'=>false,
			'rules'=>array(),
		);
		$urlManager = Yii::createComponent($urlManagerConfig);

		$testDefaultParamValue = 'test_default_param_value';
		$_GET[$testDefaultParamName] = $testDefaultParamValue;

		$testRoute = 'test_controller/test_action';
		$createdUrl = $urlManager->createUrl($testRoute);

		$expectedCreatedUrl = Yii::app()->baseUrl.'/'.$testDefaultParamName.'/'.$testDefaultParamValue.'/'.$testRoute;
		$this->assertEquals($expectedCreatedUrl, $createdUrl, 'Unable create URL with default parameter as pair "name/value"!');
	}

	/**
	 * @depends testCreateUrl
	 */
	public function testCreateUrlWithDefaultParamPassedAsArgument() {
		$testDefaultParamName = 'test_default_param_name';

		$urlManagerConfig = array(
			'class' => 'qs.web.url.QsUrlManagerDefaultParam',
			'defaultParamName' => $testDefaultParamName,
			'isDefaultParamPrependRoute' => true,
			'isDefaultParamNameDisplay' => false,
			'urlFormat' => 'path',
			'showScriptName' => false,
			'rules' => array(),
		);
		$urlManager = Yii::createComponent($urlManagerConfig);

		$testDefaultParamValue = 'test_default_param_value';

		$testRoute = 'test_controller/test_action';
		$createdUrl = $urlManager->createUrl($testRoute, array($testDefaultParamName=>$testDefaultParamValue) );

		$expectedCreatedUrl = Yii::app()->baseUrl.'/'.$testDefaultParamValue.'/'.$testRoute;
		$this->assertEquals($expectedCreatedUrl, $createdUrl, 'Unable create URL with default parameter passed as argument!');
	}
}
