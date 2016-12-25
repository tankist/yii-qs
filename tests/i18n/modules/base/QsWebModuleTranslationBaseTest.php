<?php
 
/**
 * Test case for the extension module "qs.i18n.modules.QsWebModuleTranslationBase".
 * @see QsWebModuleTranslationBase
 */
class QsWebModuleTranslationBaseTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.base.QsWebModuleTranslationBase');
	}

	/**
	 * Creaets test image translation module instance.
	 * @return ImagetranslationModule image translation module instance.
	 */
	protected function createTranslationModule() {
		$module = new QsWebModuleTranslationBase('imagetranslation', Yii::app());
		return $module;
	}

	// Tests:

	public function testSetGet() {
		$module = $this->createTranslationModule();

		$testAccessRules = array(
			array(
				'allow',
				'roles' => array('admin')
			),
		);
		$this->assertTrue($module->setAccessRules($testAccessRules), 'Unable to set access rules!');
		$this->assertEquals($module->getAccessRules(), $testAccessRules, 'Unable to set access rules correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetLanguageManager() {
		$module = $this->createTranslationModule();

		$languageManager = $module->getComponent('languageManager');
		$this->assertTrue(is_object($languageManager), 'Unable to get language manager component!');
	}
}
