<?php

/**
 * Test case for the extension "qs.email.includes.composers.QsEmailPatternComposerBase".
 * @see QsEmailPatternComposerBase
 */
class QsEmailPatternComposerBaseTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
		Yii::import('qs.email.includes.composers.*');
	}

	public function testCreate() {
		$emailComposer = new QsEmailPatternComposerEval();
		$this->assertTrue(is_object($emailComposer));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$emailComposer = new QsEmailPatternComposerEval();

		$testViewPath = 'test_view_folder';
		$this->assertTrue($emailComposer->setViewPath($testViewPath), 'Can not set viewPath!');
		$this->assertEquals($emailComposer->getViewPath(), $testViewPath, 'Can not set viewPath correctly!');

		$testLayout = 'test_layout';
		$this->assertTrue($emailComposer->setLayout($testLayout), 'Can not set layout!');
		$this->assertEquals($emailComposer->getLayout(), $testLayout, 'Can not set layout correctly!');

		$testBodyTextAutoFillType = 'test_body_text_auto_fill_type';
		$this->assertTrue($emailComposer->setBodyTextAutoFillType($testBodyTextAutoFillType), 'Can not set bodyTextAutoFillType!');
		$this->assertEquals($emailComposer->getBodyTextAutoFillType(), $testBodyTextAutoFillType, 'Can not set bodyTextAutoFillType correctly!');

		$testBodyTextDefault = 'Test body text default.';
		$this->assertTrue($emailComposer->setBodyTextDefault($testBodyTextDefault), 'Can not set bodyTextDefault!');
		$this->assertEquals($emailComposer->getBodyTextDefault(), $testBodyTextDefault, 'Can not set bodyTextDefault correctly!');

		$testEmailPattern = new QsEmailPattern();
		$this->assertTrue($emailComposer->setEmailPattern($testEmailPattern), 'Can not set emailPattern!');
		$this->assertEquals($emailComposer->getEmailPattern(), $testEmailPattern, 'Can not set emailPattern correctly!');

		$testData = array(
			'testDataKey' => 'TestDataValue'
		);
		$this->assertTrue($emailComposer->setData($testData), 'Can not set data!');
		$this->assertEquals($emailComposer->getData(), $testData, 'Can not set data correctly!');

		$testDefaultData = array(
			'testDefaultDataKey' => 'TestDefaultDataValue'
		);
		$this->assertTrue($emailComposer->setDefaultData($testDefaultData), 'Can not set default data!');
		$this->assertEquals($emailComposer->getDefaultData(), $testDefaultData, 'Can not set default data correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testPropertyAccess() {
		$emailComposer = new QsEmailPatternComposerEval();

		$testViewPath = 'test_view_folder_by_property';
		$emailComposer->viewPath = $testViewPath;
		$this->assertEquals($emailComposer->getViewPath(), $testViewPath, 'Can not set viewPath by property correctly!');
		$this->assertEquals($emailComposer->viewPath, $testViewPath, 'Can not get viewPath by property!');

		$testLayout = 'test_layout_by_property';
		$emailComposer->layout = $testLayout;
		$this->assertEquals($emailComposer->getLayout(), $testLayout, 'Can not set layout by property correctly!');

		$testBodyTextAutoFillType = 'test_body_text_auto_fill_type_by_property';
		$emailComposer->bodyTextAutoFillType = $testBodyTextAutoFillType;
		$this->assertEquals($emailComposer->bodyTextAutoFillType, $testBodyTextAutoFillType, 'Can not set bodyTextAutoFillType by property correctly!');

		$testBodyTextDefault = 'Test body text default by property.';
		$emailComposer->bodyTextDefault = $testBodyTextDefault;
		$this->assertEquals($emailComposer->bodyTextDefault, $testBodyTextDefault, 'Can not set bodyTextDefault by property correctly!');
	}
}
