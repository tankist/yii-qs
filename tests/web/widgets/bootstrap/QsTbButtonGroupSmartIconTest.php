<?php

/**
 * Test case for the extension widget "qs.web.widgets.bootstrap.QsTbButtonGroupSmartIcon".
 * @see QsTbButtonGroupSmartIcon
 */
class QsTbButtonGroupSmartIconTest extends CTestCase {
	public static function setUpBeforeClass() {
		if (Yii::getPathOfAlias('bootstrap') === false) {
			self::markTestSkipped('Bootstrap extensions required.');
		}
		Yii::import('qs.web.widgets.bootstrap.QsTbButtonGroupSmartIcon');
	}

	/**
	 * Creates test controller instance.
	 * @return CController test controller instance.
	 */
	protected function createTestController() {
		$controller = new CController('test');

		$action = $this->getMock('CAction', array('run'), array($controller, 'test'));
		$controller->action = $action;

		Yii::app()->controller = $controller;
		return $controller;
	}

	/**
	 * Runs the test widget.
	 * @param array $widgetOptions widget options
	 * @param boolean $captureOutput
	 * @return string|null rendered HTML
	 */
	public function runWidget(array $widgetOptions = array(), $captureOutput = true) {
		$controller = $this->createTestController();
		return $controller->widget('QsTbButtonGroupSmartIcon', $widgetOptions, $captureOutput);
	}

	// Tests :

	/**
	 * Data provider for {@link testDetermineIcon}
	 * @return array test data
	 */
	public function dataProviderDetermineIcon() {
		return array(
			array(
				array(
					array(
						'url' => array('index')
					)
				),
				'arrow-left'
			),
			array(
				array(
					array(
						'url' => array('create')
					)
				),
				'plus'
			),
			array(
				array(
					array(
						'url' => array('update')
					)
				),
				'pencil'
			),
			array(
				array(
					array(
						'linkOptions' => array('submit' => array('delete'))
					)
				),
				'trash'
			),
		);
	}

	/**
	 * @dataProvider dataProviderDetermineIcon
	 *
	 * @param array $buttons
	 * @param $expectedIconName
	 */
	public function testDetermineIcon(array $buttons, $expectedIconName) {
		$html = $this->runWidget(array('buttons' => $buttons));
		$expectedIconHtml = '<i class="icon-' . $expectedIconName . '"></i>';
		$this->assertContains($expectedIconHtml, $html);
	}
}