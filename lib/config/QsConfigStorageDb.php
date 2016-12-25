<?php
/**
 * QsConfigStorageDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

if (!class_exists('QsConfigStorage', false)) {
	require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'QsConfigStorage.php');
}

/**
 * QsConfigStorageDb represents the configuration storage based on database table.
 * Example migration for such table:
 * <code>
 * $tableName = '_app_config';
 * $columns = array(
 *     'id' => 'string',
 *     'value' => 'text',
 *     'PRIMARY KEY(id)',
 * );
 * $this->createTable($tableName, $columns);
 * </code>
 *
 * @property CDbConnection $dbConnection database connection instance.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.config
 */
class QsConfigStorageDb extends QsConfigStorage {
	/**
	 * @var string id of the database connection application component.
	 */
	public $db = 'db';
	/**
	 * @var string name of the table, which should store values.
	 */
	public $table = '_app_config';

	/**
	 * @return CDbConnection database connection application component.
	 */
	public function getDbConnection() {
		return Yii::app()->getComponent($this->db);
	}

	/**
	 * Saves given values.
	 * @param array $values in format: 'id' => 'value'
	 * @return boolean success.
	 */
	public function save(array $values) {
		$this->clear();
		$data = array();
		foreach ($values as $id => $value) {
			$data[] = array(
				'id' => $id,
				'value' => $value,
			);
		}
		$command = $this->getDbConnection()->getCommandBuilder()->createMultipleInsertCommand($this->table, $data);
		$insertedRowsCount = $command->execute();
		return (count($values) == $insertedRowsCount);
	}

	/**
	 * Returns previously saved values.
	 * @return array values in format: 'id' => 'value'
	 */
	public function get() {
		$command = $this->getDbConnection()->createCommand();
		$command->setFrom($this->table);
		$rows = $command->queryAll();
		$values = array();
		foreach ($rows as $row) {
			$values[$row['id']] = $row['value'];
		}
		return $values;
	}

	/**
	 * Clears all saved values.
	 * @return boolean success.
	 */
	public function clear() {
		$this->getDbConnection()->createCommand()->delete($this->table);
		return true;
	}
}