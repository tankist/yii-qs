<?php
/**
 * QsApplicationBehaviorParamDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Behavior for the {@link CApplication}, which allows to append values from
 * database table to the {@link CApplication::params}.
 * Behavior access to the database through the {@link CActiveRecord} model. 
 * Attention: given {@link CActiveRecord} class should introduce public method "getValues", 
 * which should return aditional params array. If it has no such method, behavior will try to
 * create additional params on its own from table fields "name" and "value".
 * Note: you can use {@link QsActiveRecordBehaviorNameValue} behavior attached to the {@link CActiveRecord} model
 * in order to make it sutable for this behavior.
 * 
 * @see QsActiveRecordBehaviorNameValue
 *
 * @property string $paramModelClassName public alias of {@link _paramModelClassName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.application
 */
class QsApplicationBehaviorParamDb extends CBehavior {
	/**
	 * @var string name of the {@link CActiveRecord} model, which is used to
	 * access the source database table.
	 */
	protected $_paramModelClassName = 'ApplicationParams';

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onBeginRequest' => 'beginRequest'
		);
	}

	// Set / Get :
	public function setParamModelClassName($paramModelClassName) {
		if (!is_string($paramModelClassName)) {
			throw new CException('"' . get_class($this) . '::paramModelClassName" supposed to be string!');
		}
		$this->_paramModelClassName = $paramModelClassName;
		return true;
	}

	public function getParamModelClassName() {
		return $this->_paramModelClassName;
	}

	/**
	 * This event raises before {@link CApplication}.
	 * It update {@link CApplication::params} with database data.
	 * @param CEvent $event event object.
	 */
	public function beginRequest(CEvent $event) {
		$this->updateParams();
	}

	/**
	 * Returns finder for {@link CActiveRecord} model, which is set by {@link paramModelClassName}.
	 * @return CActiveRecord model finder.
	 */
	protected function getParamModelFinder() {
		$finder = CActiveRecord::model($this->getParamModelClassName());
		return $finder;
	}

	/**
	 * Update {@link CApplication::params} appending the values fetched from database.
	 * Method will try to call method "getValues" for the {@link paramModelClassName} model,
	 * while creating additional params. If it fails method will try to create additional params
	 * manually using pairs of fields "name", "value".
	 * @return boolean success.
	 */
	protected function updateParams() {
		try {
			$modelFinder = $this->getParamModelFinder();
			try {
				$additionalParams = $modelFinder->getValues();
			} catch (CException $exception) {
				$additionalParams = array();
				$models = $modelFinder->findAll();
				foreach ($models as $model) {
					$additionalParams[$model->name] = $model->value;
				}
			}
			Yii::app()->params = CMap::mergeArray(Yii::app()->params, $additionalParams);
		} catch (CException $exception) {
			Yii::log('"' . get_class($this) . '" is unable to update "' . get_class(Yii::app()) . '::params"', CLogger::LEVEL_WARNING);
		}
		return true;
	}
}