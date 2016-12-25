<?php
 
/**
 * Test case for the {@link MessageTranslationDynamicContentWidget} widget of the module "qs.i18n.modules.messagetranslation.MessagetranslationModule".
 * @see ImagetranslationModule
 * @see MessageTranslationDynamicContentWidget
 */
class MessageTranslationDynamicContentWidgetTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.i18n.modules.messagetranslation.MessagetranslationModule');
		Yii::import('qs.i18n.modules.messagetranslation.components.widgets.*');
		Yii::import('qs.i18n.modules.messagetranslation.models.*');
	}

	public function testSetGet() {
		$widget = new MessageTranslationDynamicContentWidget();

		$testModel = new MessageTranslation();
		$this->assertTrue($widget->setModel($testModel), 'Unable to set model!');
		$this->assertEquals($widget->getModel(), $testModel, 'Unable to set model correctly!');

		$testLanguageInputId = 'test_language_input_id';
		$this->assertTrue($widget->setLanguageInputId($testLanguageInputId), 'Unable to set language input id!');
		$this->assertEquals($widget->getLanguageInputId(), $testLanguageInputId, 'Unable to set language input id correctly!');

		$testContentInputId = 'test_content_input_id';
		$this->assertTrue($widget->setContentInputId($testContentInputId), 'Unable to set content input id!');
		$this->assertEquals($widget->getContentInputId(), $testContentInputId, 'Unable to set content input id correctly!');

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
		$widget = new MessageTranslationDynamicContentWidget();

		$testModel = new MessageTranslation();
		$widget->setModel($testModel);

		$defaultLanguageInputId = $widget->getLanguageInputId();
		$this->assertFalse(empty($defaultLanguageInputId), 'Unable to get default language input id!');

		$modelAttribute = 'language';
		$expectedLanguageInputId = CHtml::getIdByName(CHtml::resolveName($testModel, $modelAttribute));
		$this->assertEquals($expectedLanguageInputId, $defaultLanguageInputId, 'Default language input id has wrong value!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultContentInputId() {
		$widget = new MessageTranslationDynamicContentWidget();

		$testModel = new MessageTranslation();
		$widget->setModel($testModel);

		$defaultContentInputId = $widget->getContentInputId();
		$this->assertFalse(empty($defaultContentInputId), 'Unable to get default language input id!');

		$modelAttribute = 'content';
		$expectedLanguageInputId = CHtml::getIdByName(CHtml::resolveName($testModel, $modelAttribute));
		$this->assertEquals($expectedLanguageInputId, $defaultContentInputId, 'Default content input id has wrong value!');
	}
}
