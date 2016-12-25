<?php
/**
 * QsBaseTranslationController class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsBaseTranslationController is the base controller for the translation modules.
 * @see QsBaseTranslationModule
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.base
 */
class QsControllerTranslationBase extends CController {
	/**
	 * @var string layout view name.
	 */
	public $layout = '/layouts/internal';
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs = array();
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $contextMenuItems = array();
	/**
	 * @var string contains the title of the current section.
	 * It should change depending on the particular action.
	 */
	public $sectionTitle = 'Translations';

	/**
	 * @return array action filters
	 */
	public function filters() {
		return array(
			'accessControl' => 'accessControl', // perform access control
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules() {
		return $this->getModule()->getAccessRules();
	}

	/**
	 * Returns page title depending on current route.
	 * @return string page title
	 */
	public function getPageTitle() {
		switch ($this->action->id) {
			case 'index': {
				$actionTitle = Yii::app()->name . ' - ' . $this->getModule()->getName();
				break;
			}
			default: {
				$actionTitle = ucfirst($this->action->id);
			}
		}
		return Yii::app()->name . ' - ' . $this->getModule()->getName() . ' - ' . $actionTitle;
	}
}
