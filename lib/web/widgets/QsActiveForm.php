<?php
/**
 * QsActiveForm class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Extension of the standard Yii widget {@link CActiveForm}, 
 * which set up enctype="multipart/form-data" and accept-charset="UTF-8" by default.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets
 */
class QsActiveForm extends CActiveForm {
	public function init() {
		$this->htmlOptions['enctype'] = 'multipart/form-data';
		$this->htmlOptions['accept-charset'] = 'UTF-8';
		parent::init();
	}
}