<?php
/**
 * QsTbButtonGroupSmartIcon class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('bootstrap.widgets.TbButtonGroup', true);

/**
 * QsTbButtonGroupSmartIcon creates group of buttons, which icons determined automatically by their URL.
 * This widget requires Twitter Bootstrap extension added to the project.
 *
 * @see TbButtonGroup
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets
 */
class QsTbButtonGroupSmartIcon extends TbButtonGroup {
	/**
	 * @var array controller action icon map in format: controllerActionName => iconName
	 */
	public $actionIconMap = array(
		// CRUD :
		'index' => 'arrow-left',
		'admin' => 'arrow-left',
		'create' => 'plus',
		'new' => 'plus',
		'add' => 'plus',
		'update' => 'pencil',
		'edit' => 'pencil',
		'delete' => 'trash',
		'remove' => 'trash',
		'view' => 'eye-open',
		'detail' => 'eye-open',
		'details' => 'eye-open',
		// Extra :
		'print' => 'print',
		'calendar' => 'calendar',
		'refresh' => 'refresh',
	);

	/**
	 * Runs the widget.
	 */
	public function run() {
		foreach ($this->buttons as $key => $button) {
			if (!array_key_exists('icon', $button)) {
				$url = $this->fetchButtonUrl($button);
				if (!empty($url)) {
					$this->buttons[$key]['icon'] = $this->determineIconByUrl($url);
				}
			}
		}
		parent::run();
	}

	/**
	 * Extracts the URL from button configuration.
	 * @param array $button button configuration.
	 * @return mixed|null extracted URL.
	 */
	protected function fetchButtonUrl(array $button) {
		if (!empty($button['linkOptions']['submit'])) {
			return $button['linkOptions']['submit'];
		} elseif (!empty($button['url'])) {
			return $button['url'];
		} else {
			return null;
		}
	}

	/**
	 * Determines the icon value based on given URL.
	 * @param mixed $url URL string or configuration.
	 * @return null|string icon name.
	 */
	protected function determineIconByUrl($url) {
		if (is_array($url)) {
			if (!empty($url[0])) {
				$route = $url[0];
				$routeParts = explode('/', $route);
				$action = array_pop($routeParts);
				if (isset($this->actionIconMap[$action])) {
					return $this->actionIconMap[$action];
				}
			}
		}
		return null;
	}
}