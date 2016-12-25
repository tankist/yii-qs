<?php
/**
 * QsButtonLink class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsButtonLink is the widget, which creates HTML button, that will behave itself like a link.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets
 */
class QsButtonLink extends CWidget {
	public $label = 'Back';
	public $url = '#';
	public $linkOptions = null;

	public function init() {
		echo $this->renderButton();
	}

	public function run() {
		return null;
	}

	protected function renderButton() {
		$url = CHtml::normalizeUrl($this->url);
		$htmlOptions = array(
			'onclick' => "window.location='{$url}'; return false;"
		);
		if (is_array($this->linkOptions)) {
			$htmlOptions = array_merge($this->linkOptions, $htmlOptions);
		}
		return CHtml::button($this->label, $htmlOptions);
	}
}