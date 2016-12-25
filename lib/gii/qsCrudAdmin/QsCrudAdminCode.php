<?php
/**
 * QsCrudAdminCode class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('gii.generators.crud.CrudCode', true);

/**
 * QsCrudAdminCode extension of {@link CrudCode}, which appends attributes allowing
 * saving generated files in custom destination.
 *
 * @property string modelClass model class name.
 * @property string controllerClass controller class name.
 * @property string controllerFile controller file name.
 * @property CDbTableSchema tableSchema model table schema.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.gii
 */
class QsCrudAdminCode extends CrudCode {
	public $baseControllerClass = 'AdminListController';
	public $viewPath = 'application.views.admin';
	public $controllerPath = 'application.controllers.admin';

	/**
	 * @return array validation rules.
	 */
	public function rules() {
		return array_merge(parent::rules(), array(
			array('viewPath, controllerPath', 'required'),
			array('viewPath, controllerPath', 'match', 'pattern' => '/^\w+[\.\w+]*$/', 'message' => '{attribute} should only contain word characters and dots.'),
			array('viewPath, controllerPath', 'sticky'),
		));
	}

	/**
	 * Returns controller file name.
	 * @return string controller file name.
	 */
	public function getControllerFile() {
		$id=$this->getControllerID();
		if (($pos=strrpos($id,'/'))!==false) {
			$id[$pos+1]=strtoupper($id[$pos+1]);
		} else {
			$id[0]=strtoupper($id[0]);
		}
		return Yii::getPathOfAlias($this->controllerPath) . '/' . $id . 'Controller.php';
	}

	/**
	 * Prepares the code files.
	 */
	public function prepare() {
		$originalViewPath = $this->viewPath;
		$this->viewPath = Yii::getPathOfAlias($originalViewPath) . '/' . $this->controller;
		parent::prepare();
		$this->viewPath = $originalViewPath;
	}

	/**
	 * Generates bootstrap form active row.
	 * @param string $modelClass model class name
	 * @param CDbColumnSchema $column db column schema
	 * @return string generated code.
	 * @see TbActiveForm
	 */
	public function generateActiveRow($modelClass, $column) {
		if ($column->type === 'boolean') {
			return "\$form->checkBoxRow(\$model, '{$column->name}')";
		} elseif (stripos($column->dbType, 'text') !== false) {
			return "\$form->textAreaRow(\$model, '{$column->name}', array('rows'=>6, 'cols'=>50, 'class'=>'span8'))";
		} else {
			if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
				$inputField = 'passwordFieldRow';
			} else {
				$inputField = 'textFieldRow';
			}

			if ($column->type !== 'string' || $column->size === null) {
				return "\$form->{$inputField}(\$model, '{$column->name}', array('class'=>'span5'))";
			} else {
				return "\$form->{$inputField}(\$model, '{$column->name}', array('class'=>'span5', 'maxlength'=>$column->size))";
			}
		}
	}
}