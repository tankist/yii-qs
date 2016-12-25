<?php
/**
 * QsConfigItem class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsConfigItem represents a single application configuration item.
 * It allows extraction and composition of the config value for the particular
 * config array keys sequence setup by {@link path}.
 *
 * @see QsConfigManager
 *
 * @property string $value public alias of {@link _value}.
 * @property array $rules public alias of {@link _rules}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.config
 */
class QsConfigItem extends CModel {
	/**
	 * @var mixed config parameter unique identifier.
	 */
	public $id;
	/**
	 * @var string label for the {@link value} attribute.
	 */
	public $label = 'Value';
	/**
	 * @var mixed config parameter value.
	 */
	protected $_value;
	/**
	 * @var array validation rules.
	 * Unlike the configuration for the common model, each rule should not contain attribute name
	 * as it already determined as {@link value}.
	 */
	protected $_rules = array();
	/**
	 * @var string|array application config path. Path is sequence of the config array keys.
	 * It could be either a string, where keys are separated by '.', or an array of keys.
	 * For example:
	 * 'params.myparam';
	 * array('params', 'myparam');
	 * 'components.securityManager.validationKey';
	 * array('components', 'securityManager', 'validationKey');
	 * If path is not set it will point to {@link CApplication::params} with the key equals ot {@link id}.
	 */
	public $path;
	/**
	 * @var string brief description for the config item.
	 */
	public $description;

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->_value = $value;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {
		if ($this->_value === null) {
			$this->_value = $this->extractCurrentValue();
		}
		return $this->_value;
	}

	/**
	 * @param array $rules
	 */
	public function setRules(array $rules) {
		$this->_rules = $rules;
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return $this->_rules;
	}

	/**
	 * Returns the config path parts.
	 * @return array config path parts.
	 */
	public function getPathParts() {
		if (empty($this->path)) {
			$this->path = $this->composeDefaultPath();
		}
		if (is_array($this->path)) {
			$pathParts = $this->path;
		} else {
			$pathParts = explode('.', $this->path);
		}
		return $pathParts;
	}

	/**
	 * Returns the list of attribute names of the model.
	 * @return array list of attribute names.
	 */
	public function attributeNames() {
		return array(
			'value'
		);
	}

	/**
	 * Returns the attribute labels.
	 * @return array attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
			'value' => $this->label,
		);
	}

	/**
	 * Creates validator objects based on the specification in {@link rules}.
	 * This method is mainly used internally.
	 * @throws CException on invalid configuration.
	 * @return CList validators built based on {@link rules()}.
	 */
	public function createValidators() {
		$validatorList = parent::createValidators();
		$rules = $this->getRules();
		array_unshift($rules, array('safe'));
		foreach ($rules as $rule) {
			if (isset($rule[0])) { // validator name
				$validatorList->add(CValidator::createValidator($rule[0], $this, 'value', array_slice($rule, 2)));
			} else {
				throw new CException('Invalid validation rule for "' . $this->getAttributeLabel('value') . '". The rule must specify the validator name.');
			}
		}
		return $validatorList;
	}

	/**
	 * Composes default config path, which points to {@link CApplication::params} array
	 * with key equal to {@link id}.
	 * @return array config path.
	 */
	protected function composeDefaultPath() {
		return array('params', $this->id);
	}

	/**
	 * Extracts current config item value from the current application instance.
	 * @return mixed current value.
	 */
	public function extractCurrentValue() {
		$pathParts = $this->getPathParts();
		return $this->findConfigPathValue(Yii::app(), $pathParts);
	}

	/**
	 * Finds the given config path inside given source.
	 * @param array|object $source config source
	 * @param array $pathParts config path parts.
	 * @return mixed config param value.
	 * @throws CException on failure.
	 */
	protected function findConfigPathValue($source, array $pathParts) {
		if (empty($pathParts)) {
			throw new CException('Empty extraction path.');
		}
		$name = array_shift($pathParts);
		if (is_array($source)) {
			if (array_key_exists($name, $source)) {
				$result = $source[$name];
			} else {
				throw new CException('Key "' . $name . '" not present!');
			}
		} elseif (is_object($source)) {
			if (is_a($source, 'CModule') && $name == 'components') {
				$result = $source->getComponents(false);
			} else {
				if (isset($source->$name)) {
					$result = $source->$name;
				} else {
					if (is_a($source, 'ArrayAccess')) {
						$result = $source[$name];
					} else {
						throw new CException('Property "' . get_class($source) . '::' . $name . '" not present!');
					}
				}
			}
		} else {
			throw new CException('Unable to extract path "' . implode('.', $pathParts) . '" from "' . gettype($source) . '"');
		}
		if (empty($pathParts)) {
			return $result;
		} else {
			return $this->findConfigPathValue($result, $pathParts);
		}
	}

	/**
	 * Composes application configuration array, which can setup this config item.
	 * @return array application configuration array.
	 */
	public function composeConfig() {
		$pathParts = $this->getPathParts();
		return $this->composeConfigPathValue($pathParts);
	}

	/**
	 * Composes the configuration array by given path parts.
	 * @param array $pathParts config path parts.
	 * @return array configuration array segment.
	 * @throws CException on failure.
	 */
	protected function composeConfigPathValue(array $pathParts) {
		if (empty($pathParts)) {
			throw new CException('Empty extraction path.');
		}
		$basis = array();
		$name = array_shift($pathParts);
		if (empty($pathParts)) {
			$basis[$name] = $this->value;
		} else {
			$basis[$name] = $this->composeConfigPathValue($pathParts);
		}
		return $basis;
	}
}