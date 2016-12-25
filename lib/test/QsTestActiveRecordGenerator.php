<?php
/**
 * QsTestController class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsTestActiveRecordGenerator is a helper, which allows dynamic creation of the
 * {@link CActiveRecord} descendant classes.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test
 */
class QsTestActiveRecordGenerator extends CComponent {
	/**
	 * Generates new class, which extends {@link CActiveRecord}.
	 * @param array $config code configuration, it should contain at least 'tableName' parameter.
	 * @return boolean success.
	 */
	public function generate(array $config) {
		if (!array_key_exists('tableName', $config)) {
			throw new CException('Parameter "tableName" should be specified');
		}
		$tableName = $config['tableName'];
		$className = array_key_exists('className', $config) ? $config['className'] : $tableName;

		$rules = $this->generateArrayDataCodeFromConfig('rules', $config);
		$relations = $this->generateArrayDataCodeFromConfig('relations', $config);
		$attributeLabels = $this->generateArrayDataCodeFromConfig('attributeLabels', $config);
		$behaviors = $this->generateArrayDataCodeFromConfig('behaviors', $config);
		$scopes = $this->generateArrayDataCodeFromConfig('scopes', $config);
		$defaultScope = $this->generateArrayDataCodeFromConfig('defaultScope', $config);

		$additionalCode = array_key_exists('additionalCode', $config) ? $config['additionalCode'] : '';

		$classDefinitionCode = <<<EOD
class {$className} extends CActiveRecord {
	public static function model(\$className=__CLASS__) {
		return parent::model(\$className);
	}

	public function tableName() {
		return '{$tableName}';
	}

	public function rules() {
		return {$rules};
	}

	public function relations() {
		return {$relations};
	}

	public function attributeLabels() {
		return {$attributeLabels};
	}

	public function behaviors() {
		return {$behaviors};
	}

	public function scopes() {
		return {$scopes};
	}

	public function defaultScope() {
		return {$defaultScope};
	}

	{$additionalCode}
}
EOD;
		eval($classDefinitionCode);
		return true;
	}

	/**
	 * Generates a fragment of PHP code from the give data.
	 * @param array|string $sourceData source data.
	 * @return string PHP code fragment.
	 */
	protected function generateArrayDataCode($sourceData) {
		if (is_array($sourceData)) {
			return str_replace("\r", '', var_export($sourceData, true));
		} elseif (is_scalar($sourceData)) {
			return $sourceData;
		} else {
			throw new CException('Invalid data type for "'.get_class($this).'::'.__FUNCTION__.'"');
		}
	}

	/**
	 * Performs {@link generateArrayDataCode()} for the data from the given configuration array.
	 * @param string $dataKeyName config key name.
	 * @param array $config configuration array.
	 * @return string PHP code fragment.
	 */
	protected function generateArrayDataCodeFromConfig($dataKeyName, array $config) {
		$data = array_key_exists($dataKeyName,$config) ? $config[$dataKeyName] : array();
		$code = $this->generateArrayDataCode($data);
		return $code;
	}
}
