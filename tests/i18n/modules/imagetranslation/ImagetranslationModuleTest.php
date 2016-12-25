<?php
 
/**
 * Test case for the extension module "qs.i18n.modules.imagetranslation.ImagetranslationModule".
 * @see ImagetranslationModule
 */
class ImagetranslationModuleTest extends CTestCase {
	/**
	 * @var QsImageTranslationSource image translation source component backup.
	 */
	protected $_imageTranslationSourceBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.imagetranslation.ImagetranslationModule');
		Yii::import('qs.i18n.images.*');
	}

	public function setUp() {
		if (Yii::app()->hasComponent('imageTranslationSource')) {
			$this->_imageTranslationSourceBackup = Yii::app()->getComponent('imageTranslationSource');
		}
		$imageTranslationSource = $this->createImageTranslationSource();
		Yii::app()->setComponent('imageTranslationSource', $imageTranslationSource);
	}

	public function tearDown() {
		if (is_object($this->_imageTranslationSourceBackup)) {
			Yii::app()->setComponent('imageTranslationSource', $this->_imageTranslationSourceBackup);
		}
	}

	/**
	 * Creates test image translation module instance.
	 * @return ImagetranslationModule image translation module instance.
	 */
	protected function createImageTranslationModule() {
		$module = new ImagetranslationModule('imagetranslation', Yii::app());
		return $module;
	}

	/**
	 * Creates test image translation source component.
	 * @return QsImageTranslationSource image translation source instance.
	 */
	protected function createImageTranslationSource() {
		$methodsList = array(
			'loadImageTranslation',
			'imageTranslationExists',
			'saveImageTranslation',
		);
		$imageTranslationSource = $this->getMock('QsImageTranslationSource', $methodsList);
		return $imageTranslationSource;
	}

	// Tests:

	public function testSetGet() {
		$module = $this->createImageTranslationModule();

		$testAccessRules = array(
			array(
				'allow',
				'roles' => array('admin')
			),
		);
		$this->assertTrue($module->setAccessRules($testAccessRules), 'Unable to set access rules!');
		$this->assertEquals($module->getAccessRules(), $testAccessRules, 'Unable to set access rules correctly!');

		$testImageTranslationComponentName = 'testImageTranslationComponentName';
		$this->assertTrue($module->setImageTranslationSourceComponentName($testImageTranslationComponentName), 'Unable to set image translation source component name!');
		$this->assertEquals($module->getImageTranslationSourceComponentName(), $testImageTranslationComponentName, 'Unable to set image translation source component name correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetImageTranslationSource() {
		$module = $this->createImageTranslationModule();

		$imageTranslationSource = $module->getImageTranslationSource();
		$this->assertTrue(is_object($imageTranslationSource), 'Unable to get translation source!');
	}
}
