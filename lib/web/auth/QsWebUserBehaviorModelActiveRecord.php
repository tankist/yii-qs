<?php
/**
 * QsWebUserBehaviorAuthLogDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsWebUserBehaviorModelActiveRecord is a behavior for the {@link QsWebUser}, which allows
 * to bound web user with the {@link CActiveRecord} model, which represents him.
 * This behavior also allows to synchronize web user data with the database on each web request.
 *
 * @see QsWebUser
 *
 * @property string $modelClassName public alias of {@link _modelClassName}.
 * @property mixed $modelFindCondition public alias of {@link _modelFindCondition}.
 * @property CActiveRecord $model public alias of {@link _model}.
 * @method QsWebUser getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth
 */
class QsWebUserBehaviorModelActiveRecord extends CBehavior {
	/**
	 * @var string name of the {@link CActiveRecord} model, which stores users.
	 */
	protected $_modelClassName = 'User';
	/**
	 * @var mixed query condition or criteria.
	 */
	protected $_modelFindCondition = null;
	/**
	 * @var CActiveRecord user model instance.
	 */
	protected $_model = null;
	/**
	 * @var boolean indicates if {@link ensureModel} will be called
	 * after user restore automatically.
	 */
	public $autoEnsureModel = true;

	// Set / Get :

	public function setModelClassName($userModelClassName) {
		$this->_modelClassName = $userModelClassName;
		return true;
	}

	public function getModelClassName() {
		return $this->_modelClassName;
	}

	public function setModelFindCondition($modelSearchCondition) {
		$this->_modelFindCondition = $modelSearchCondition;
		return true;
	}

	public function getModelFindCondition() {
		return $this->_modelFindCondition;
	}

	public function setModel(CActiveRecord $model) {
		$this->_model = $model;
		return true;
	}

	public function getModel() {
		if (!is_object($this->_model)) {
			$this->initModel();
		}
		return $this->_model;
	}

	/**
	 * Initializes {@link model} field with user model instance.
	 * Model will initialized only if user is not guest.
	 * @return boolean model is initialized.
	 */
	protected function initModel() {
		$webUser = $this->getOwner();
		if ($webUser->getIsGuest()) {
			return false;
		}
		$this->_model = $this->findModel();
		return true;
	}

	/**
	 * Finds user model in the database.
	 * @return CActiveRecord user model instance.
	 */
	protected function findModel() {
		$webUser = $this->getOwner();
		$modelFinder = CActiveRecord::model($this->getModelClassName());
		$model = $modelFinder->findByPk($webUser->getId(), $this->getModelFindCondition());
		return $model;
	}

	/**
	 * Updates web user states using value from {@link model}.
	 * @return boolean success.
	 */
	public function refreshStatesFromModel() {
		$model = $this->getModel();
		if (!is_object($model)) {
			return false;
		}
		$webUser = $this->getOwner();
		foreach ($model->attributes as $attributeName => $attributeValue) {
			$webUser->setState($attributeName, $attributeValue);
		}
		return true;
	}

	/**
	 * Ensures user model exists and updates user states with the model data.
	 * If model can not be found and user is NOT guest,
	 * user will be logged out.
	 * @return boolean success.
	 */
	public function ensureModel() {
		if ($this->refreshStatesFromModel()) {
			return true;
		} else {
			$webUser = $this->getOwner();
			if (!$webUser->getIsGuest()) {
				$webUser->logout(false);
			}
			return false;
		}
	}

	/**
	 * Declares events and the corresponding event handler methods.
	 * The events are defined by the {@link owner} component, while the handler
	 * methods by the behavior class. The handlers will be attached to the corresponding
	 * events when the behavior is attached to the {@link owner} component; and they
	 * will be detached from the events when the behavior is detached from the component.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onAfterRestore' => 'afterRestore'
		);
	}

	/**
	 * Responds to {@link QsWebUser::onAfterRestore} event.
	 * @param CEvent $event event parameter.
	 */
	public function afterRestore(CEvent $event) {
		if ($this->autoEnsureModel) {
			$this->ensureModel();
		}
	}
}
