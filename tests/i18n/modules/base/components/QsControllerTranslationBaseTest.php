<?php
 
/**
 * Test for the class {@link QsControllerTranslationBase}, which is base controller
 * for the module "qs.i18n.modules.base.QsWebModuleTranslationBase".
 * @see QsWebModuleTranslationBase
 * @see QsControllerTranslationBase
 */
class QsControllerTranslationBaseTest extends CTestCase {
	/**
	 * @var array application modules list backup.
	 */
	protected $_modulesBackup = array();

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.base.QsWebModuleTranslationBase');
		Yii::import('qs.i18n.modules.base.components.*');
	}

	public function setUp() {
		$this->_modulesBackup = Yii::app()->getModules();
		Yii::app()->setModules($this->createTestModulesConfig());
	}

	public function tearDown() {
		Yii::app()->setModules($this->_modulesBackup);
	}

	/**
	 * Creates the configuration array for the application module
	 * @return array test modules config.
	 */
	protected function createTestModulesConfig() {
		$modulesConfig = array(
			'testmodule' => array(
				'class' => 'QsWebModuleTranslationBase',
				/*'components' => array(
					'languageManager' => array(
						'languageModelClassName' => 'TestLanguage'
					),
				)*/
			)
		);
		return $modulesConfig;
	}

	/**
	 * Returns the test application module instance.
	 * @return CModule application module instance.
	 */
	protected function getTestModule() {
		return Yii::app()->getModule('testmodule');
	}

	// Tests:

	public function testCreate() {
		$controller = new QsControllerTranslationBase('base', Yii::app());
		$this->assertTrue(is_object($controller), 'Unable to create controller!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetAccessRules() {
		$testModule = $this->getTestModule();
		$testAccessRules = array(
			array(
				'allow',
				'roles' => array('admin')
			),
			array(
				'deny',
				'users' => array('*'),
			)
		);
		$testModule->setAccessRules($testAccessRules);

		$controller = new QsControllerTranslationBase('base', $testModule);

		$controllerAccessRules = $controller->accessRules();
		$this->assertFalse(empty($controllerAccessRules), 'Unable to get access rules!');
		$this->assertEquals($testAccessRules, $controllerAccessRules, 'Controller access rules differ from the module ones!');
	}
}
