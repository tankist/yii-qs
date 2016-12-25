<?php
/**
 * QsConfigStorage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsConfigStorage represents the storage for configuration items in format: id => value.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.config
 */
abstract class QsConfigStorage extends CApplicationComponent {
	/**
	 * Saves given values.
	 * @param array $values in format: 'id' => 'value'
	 * @return boolean success.
	 */
	abstract public function save(array $values);

	/**
	 * Returns previously saved values.
	 * @return array values in format: 'id' => 'value'
	 */
	abstract public function get();

	/**
	 * Clears all saved values.
	 * @return boolean success.
	 */
	abstract public function clear();
}