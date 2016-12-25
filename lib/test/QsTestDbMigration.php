<?php
/**
 * QsTestDbMigration class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * This class extends {@link CDbMigration} and was created as helper 
 * for the unit test creation.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test
 */
class QsTestDbMigration extends CDbMigration {
	/**
	 * Executes a SQL statement.
	 * This method executes the specified SQL statement using {@link dbConnection}.
	 * @param string $sql the SQL statement to be executed
	 * @param array $params input parameters (name=>value) for the SQL execution. See {@link CDbCommand::execute} for more details.
	 */
	public function execute($sql, $params=array()) {
		$this->getDbConnection()->createCommand($sql)->execute($params);
	}

	/**
	 * Creates and executes an INSERT SQL statement.
	 * The method will properly escape the column names, and bind the values to be inserted.
	 * @param string $table the table that new rows will be inserted into.
	 * @param array $columns the column data (name=>value) to be inserted into the table.
	 */
	public function insert($table, $columns) {
		$this->getDbConnection()->createCommand()->insert($table, $columns);
	}

	/**
	 * Builds and executes a SQL statement for creating a new DB table.
	 *
	 * The columns in the new  table should be specified as name-definition pairs (e.g. 'name'=>'string'),
	 * where name stands for a column name which will be properly quoted by the method, and definition
	 * stands for the column type which can contain an abstract DB type.
	 * The {@link getColumnType} method will be invoked to convert any abstract type into a physical one.
	 *
	 * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly
	 * inserted into the generated SQL.
	 *
	 * @param string $table the name of the table to be created. The name will be properly quoted by the method.
	 * @param array $columns the columns (name=>definition) in the new table.
	 * @param string $options additional SQL fragment that will be appended to the generated SQL.
	 */
	public function createTable($table, $columns, $options=null) {
		$this->getDbConnection()->createCommand()->createTable($table, $columns, $options);
	}

	/**
	 * Builds and executes a SQL statement for dropping a DB table.
	 * @param string $table the table to be dropped. The name will be properly quoted by the method.
	 */
	public function dropTable($table) {
		$this->getDbConnection()->createCommand()->dropTable($table);
	}

	/**
	 * Builds and executes a SQL statement for truncating a DB table.
	 * @param string $table the table to be truncated. The name will be properly quoted by the method.
	 */
	public function truncateTable($table) {
		$this->getDbConnection()->createCommand()->truncateTable($table);
	}
}