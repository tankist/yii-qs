<?php
/**
 * QsActiveRecordBehaviorCrypt class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsActiveRecordBehaviorCrypt allows automatic encryption/decryption of the model attributes.
 * Use this behavior to store data in the database in secure state.
 * This class relies on {@link CSecurityManager} application component, so be sure you have setup
 * this component in your application configuration.
 * Behavior config example:
 * <code>
 * array(
 *     'cryptBehavior' => array(
 *         'class'=>'qs.db.ar.QsActiveRecordBehaviorCrypt',
 *         'cryptAttributes'=>array(
 *             'secure_attribute_1',
 *             'secure_attribute_2' => 'custom_encryption_key',
 *         ),
 *     )
 * );
 * </code>
 *
 * Note: all encrypted fields in the database table should be in binary format (blob).
 *
 * @see CSecurityManager
 *
 * @property array $cryptAttributes public alias of {@link _cryptAttributes}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorCrypt extends CBehavior {
	/**
	 * @var array list of attributes which should be encrypted/decrypted.
	 * You can specify encryption key for each attribute, using format "attributeName => encryptionKey",
	 * otherwise the {@link CSecurityManager::encryptionKey} will be used.
	 * Example:
	 * <code>
	 * array(
	 *     'secure_attribute_1',
	 *     'secure_attribute_2' => 'custom_encryption_key',
	 * );
	 * </code>
	 */
	protected $_cryptAttributes = array();
	/**
	 * @var boolean indicates if the attributes, specified in {@link cryptAttributes}, should be
	 * automatically encrypted before the owner model is saved.
	 * You can disable this option in order to increase performance and reliability.
	 * In this case you should manually use method {@link encryptAttributes()}.
	 */
	public $autoEncryptAttributes = true;
	/**
	 * @var boolean indicates if the attributes, specified in {@link cryptAttributes}, should be
	 * automatically decrypted after model is found.
	 * You can disable this option in order to increase performance and reliability.
	 * In this case you should manually use method {@link decryptAttributes()}.
	 */
	public $autoDecryptAttributes = true;

	// Set / Get :

	public function setCryptAttributes($cryptAttributes) {
		$this->_cryptAttributes = $cryptAttributes;
		return true;
	}

	public function getCryptAttributes() {
		return $this->_cryptAttributes;
	}

	/**
	 * Returns the security manager application component.
	 * @return CSecurityManager security manager instance.
	 */
	protected function getSecurityManager() {
		return Yii::app()->getSecurityManager();
	}

	/**
	 * Encrypts the attributes specified by {@link cryptAttributes}.
	 * @return CActiveRecord owner reference.
	 */
	public function encryptAttributes() {
		$owner = $this->getOwner();
		$cryptAttributes = $this->getCryptAttributes();
		foreach ($cryptAttributes as $key => $value) {
			if (is_numeric($key)) {
				$encryptionKey = null;
				$attributeName = $value;
			} else {
				$encryptionKey = $value;
				$attributeName = $key;
			}
			$attributeValue = $owner->$attributeName;
			if (strlen($attributeValue)>0) {
				$owner->$attributeName = $this->getSecurityManager()->encrypt($attributeValue, $encryptionKey);
			}
		}
		return $owner;
	}

	/**
	 * Decrypts the attributes specified by {@link cryptAttributes}.
	 * @return CActiveRecord owner reference.
	 */
	public function decryptAttributes() {
		$owner = $this->getOwner();
		$cryptAttributes = $this->getCryptAttributes();
		foreach ($cryptAttributes as $key => $value) {
			if (is_numeric($key)) {
				$encryptionKey = null;
				$attributeName = $value;
			} else {
				$encryptionKey = $value;
				$attributeName = $key;
			}
			$attributeValue = $owner->$attributeName;
			if (strlen($attributeValue) > 0) {
				$owner->$attributeName = $this->getSecurityManager()->decrypt($attributeValue, $encryptionKey);
			}
		}
		return $owner;
	}

	// Events:

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onBeforeSave' => 'beforeSave',
			'onAfterFind' => 'afterFind',
		);
	}

	/**
	 * Responds to {@link CActiveRecord::onBeforeSave} event.
	 * @param CModelEvent $event event parameter
	 */
	public function beforeSave($event) {
		if ($this->autoEncryptAttributes) {
			$this->encryptAttributes();
		}
	}

	/**
	 * Responds to {@link CActiveRecord::onAfterFind} event.
	 * @param CEvent $event event parameter
	 */
	public function afterFind($event) {
		if ($this->autoDecryptAttributes) {
			$this->decryptAttributes();
		}
	}
}
