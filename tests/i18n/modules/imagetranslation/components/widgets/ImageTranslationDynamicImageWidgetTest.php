<?php
 
/**
 * Test case for the {@link ImageTranslationDynamicImageWidget} widget of the module "qs.i18n.modules.imagetranslation.ImagetranslationModule".
 * @see ImagetranslationModule
 * @see ImageTranslationDynamicImageWidget
 */
class ImageTranslationDynamicImageWidgetTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.imagetranslation.ImagetranslationModule');
		Yii::import('qs.i18n.modules.imagetranslation.components.widgets.*');
		Yii::import('qs.i18n.modules.imagetranslation.models.*');
	}

	public function testSetGet() {
		$widget = new ImageTranslationDynamicImageWidget();

		$testModel = new ImageTranslation();
		$this->assertTrue($widget->setModel($testModel), 'Unable to set model!');
		$this->assertEquals($widget->getModel(), $testModel, 'Unable to set model correctly!');

		$testLanguageInputId = 'test_language_input_id';
		$this->assertTrue($widget->setLanguageInputId($testLanguageInputId), 'Unable to set language input id!');
		$this->assertEquals($widget->getLanguageInputId(), $testLanguageInputId, 'Unable to set language input id correctly!');

		$testLanguages = array(
			'test_lang_1',
			'test_lang_2'
		);
		$this->assertTrue($widget->setLanguages($testLanguages), 'Unable to set languages!');
		$this->assertEquals($widget->getLanguages(), $testLanguages, 'Unable to set languages correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultLanguageInputId() {
		$widget = new ImageTranslationDynamicImageWidget();

		$testModel = new ImageTranslation();
		$widget->setModel($testModel);

		$defaultLanguageInputId = $widget->getLanguageInputId();
		$this->assertFalse(empty($defaultLanguageInputId), 'Unable to get default language input id!');

		$modelAttribute = 'language';
		$expectedLanguageInputId = CHtml::getIdByName(CHtml::resolveName($testModel, $modelAttribute));
		$this->assertEquals($expectedLanguageInputId, $defaultLanguageInputId, 'Default language input id has wrong value!');
	}
}
