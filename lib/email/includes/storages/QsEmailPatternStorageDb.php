<?php
/**
 * QsEmailPatternStorageDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternStorageDb is an explicit implementation of {@link QsEmailPatternStorageBase}, 
 * which use database as the storage of the email patterns.
 * This class uses {@link CActiveRecord} model as the access point to the database.
 * You can specify the class name of the model using {@link modelClassName}.
 * In order to create such model you may run the following DB migration:
 * <code>
 * $tableName = 'email_pattern';
 * $columns = array(
 *     'id' => 'pk',
 *     'timestamp' => 'integer',
 *     'name' => 'string',
 *     'from_email' => 'string',
 *     'from_name' => 'string',
 *     'subject' => 'string',
 *     'body' => 'text',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * $this->createIndex("idx_{$tableName}_name", $tableName, 'name', true);
 * $this->createIndex("idx_{$tableName}_timestamp", $tableName, 'timestamp'); 
 * </code>
 *
 * @property string $modelClassName public alias of {@link _modelClassName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.storages
 */
class QsEmailPatternStorageDb extends QsEmailPatternStorageBase {
	/**
	 * @var string name of the {@link CActiveRecord} model,
	 * which will be used to find patterns.
	 */
	protected $_modelClassName = 'EmailPattern';

	// Set / Get :

	public function setModelClassName($modelClassName) {
		if (!is_string($modelClassName)) {
			return false;
		}
		$this->_modelClassName = $modelClassName;
		return true;
	}

	public function getModelClassName() {
		return $this->_modelClassName;
	}

	/**
	 * Initializes email pattern instance, filling up its attributes with values
	 * found in storage.
	 * @throws Exception on fail.
	 * @param QsEmailPattern $patternInstance email pattern.
	 * @return boolean success.
	 */
	protected function initEmailPatternInstance(QsEmailPattern $patternInstance) {
		$emailPatternModel = $this->findEmailPatternModel($patternInstance->getId());
		if (empty($emailPatternModel)) {
			throw new CException('Unable to find email pattern "' . $patternInstance->getId() . '" in the database.');
		}

		if (isset($emailPatternModel->from)) {
			$patternInstance->setFrom($emailPatternModel->from);
		} else {
			if (isset($emailPatternModel->from_email)) {
				$patternInstance->setFromEmail($emailPatternModel->from_email);
			}
			if (isset($emailPatternModel->from_name)) {
				$patternInstance->setFromName($emailPatternModel->from_name);
			}
		}

		$patternInstance->setSubject($emailPatternModel->subject);
		$patternInstance->setBodyHtml($emailPatternModel->body);
		$patternInstance->setTimestamp($emailPatternModel->timestamp);

		return true;
	}

	/**
	 * Finds the record in the database matching the requested patternId.
	 * @param mixed $patternId pattern id.
	 * @return CModel email pattern source model.
	 */
	protected function findEmailPatternModel($patternId) {
		$modelFinder = CActiveRecord::model($this->getModelClassName());
		$attributes = array(
			'name' => $patternId
		);
		$model = $modelFinder->findByAttributes($attributes);
		return $model;
	}
}