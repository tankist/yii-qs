<?php

Yii::import('qs.web.auth.external.widgets.QsAuthExternalPopupWindowRedirect');

/**
 * Test case for the extension "qs.web.auth.external.widgets.QsAuthExternalPopupWindowRedirect".
 * @see QsAuthExternalPopupWindowRedirect
 */
class QsAuthExternalPopupWindowRedirectTest extends CTestCase {
	public static function setUpBeforeClass() {
		$webUser = Yii::createComponent(array('class' => 'CWebUser'));
		Yii::app()->setComponent('user', $webUser);
	}

	/**
	 * Runs the {@link QsAuthExternalPopupWindowRedirect} widget.
	 * @param array $widgetConfig widget configuration.
	 * @return string widget output.
	 */
	protected function runPopupWindowRedirectWidget(array $widgetConfig = array()) {
		$widgetConfig['class'] = 'QsAuthExternalPopupWindowRedirect';
		$widgetConfig['terminate'] = false;
		$widget = Yii::createComponent($widgetConfig);

		ob_start();
		ob_implicit_flush(false);
		$widget->init();
		$widget->run();
		return ob_get_clean();
	}

	// Tests :

	public function testRun() {
		$url = 'http://test.url';
		$output = $this->runPopupWindowRedirectWidget(array('url' => $url));

		$this->assertContains("function popupWindowRedirect(", $output, 'No redirect java script definition!');
		$this->assertContains("popupWindowRedirect('{$url}', true);", $output, 'No redirect java script invoked!');
	}

	public function testDefaultUrl() {
		$widget = new QsAuthExternalPopupWindowRedirect();

		$userLoginUrl = 'http://test.return.url';
		Yii::app()->getComponent('user')->loginUrl = $userLoginUrl;
		$widget->init();

		$this->assertNotEmpty($widget->url, 'Unable to get default URL!');
	}
}
