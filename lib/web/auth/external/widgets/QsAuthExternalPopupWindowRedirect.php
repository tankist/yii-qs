<?php
/**
 * QsAuthExternalPopupWindowRedirect class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalPopupWindowRedirect renders HTML page, which performs browser redirect via JavaScript.
 * If the current page is a popup page, it will be closed and its parent page will be redirected.
 *
 * This widget is used inside {@link QsAuthExternalService::redirect()}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.widgets
 */
class QsAuthExternalPopupWindowRedirect extends CWidget {
	/**
	 * @var mixed redirect URL. Could be a string or array config to generate a valid URL.
	 * @see CHtml::normalizeUrl()
	 */
	public $url;
	/**
	 * @var boolean whether to redirect parent window after the popup one is closed.
	 * This parameter takes effect only if the current window is a popup window.
	 */
	public $enforceRedirect = true;
	/**
	 * @var boolean indicates if widget should terminate the script.
	 */
	public $terminate = true;
	/**
	 * @var string view name.
	 */
	public $view = 'popup_window_redirect';

	/**
	 * Initializes the widget.
	 */
	public function init() {
		$url = $this->url;
		if ($url === null) {
			/* @var $webUser CWebUser */
			$webUser = Yii::app()->getComponent('user');
			if (is_object($webUser)) {
				$url = $webUser->loginUrl;
			} else {
				$url = array();
			}
		}
		$this->url = CHtml::normalizeUrl($url);
	}

	/**
	 * Executes the widget.
	 */
	public function run() {
		$redirectJavaScript = <<<EOL
function popupWindowRedirect(url, enforceRedirect = true) {
	if (window.opener) {
		window.close();
		if (enforceRedirect) {
			window.opener.location = url;
		}
	} else {
		window.location = url;
	}
}
EOL;

		$redirectJavaScript .= 'popupWindowRedirect(' . CJavaScript::encode($this->url) . ', ' . CJavaScript::encode($this->enforceRedirect) . ');';

		$viewData = array(
			'url' => $this->url,
			'enforceRedirect' => $this->enforceRedirect,
			'redirectJavaScript' => $redirectJavaScript,
		);
		$this->render($this->view, $viewData);

		if ($this->terminate) {
			Yii::app()->end();
		}
	}

}
