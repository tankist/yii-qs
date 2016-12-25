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
 * QsWebUserBehaviorAuthLogDb is a behavior for the {@link QsWebUser}, which allows
 * logging the web user login into the database table.
 * This behavior covers both login in from login form and by login cookie.
 *
 * In order to create table, which is suitable for logging, you may run the following DB migration:
 * <code>
 * $tableName = '_auth_log';
 * $columns = array(
 *     'id' => 'pk',
 *     'date' => 'datetime',
 *     'ip' => 'varchar(50)',
 *     'host' => 'string',
 *     'url' => 'string',
 *     'script_name' => 'string',
 *     'user_id' => 'integer',
 *     'username' => 'string',
 *     'error_code' => 'integer',
 *     'error_message' => 'string',
 * );
 * $this->createTable($tableName, $columns, 'engine=INNODB');
 * $this->createIndex("idx_{$tableName}_user_id", $tableName, 'user_id');
 * $this->createIndex("idx_{$tableName}_error_code", $tableName, 'error_code');
 * </code>
 * 
 * Use methods {@link writeAuthLog()}, {@link writeAuthLogError()}, {@link writeAuthLogFromUserIdentity()} to write the log data.
 *
 * @see QsWebUser
 *
 * @property string $authLogTableName public alias of {@link _authLogTableName}.
 * @method QsWebUser getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth
 */
class QsWebUserBehaviorAuthLogDb extends CBehavior {
	/**
	 * @var string name of auth log model class.
	 */
	protected $_authLogTableName = '_auth_log';
	/**
	 * @var boolean indicates if all web user states should be automatically logged, while
	 * saving auth log.
	 */
	public $allowAuthLogAllStates = false;
	/**
	 * @var string the ID of CDbConnection application component, which should be used while logging.
	 */
	public $dbConnectionId = 'db';

	// Set / Get :

	public function setAuthLogTableName($authLogTableName) {
		if (!is_string($authLogTableName)) {
			throw new CException('"'.get_class($this).'::authLogTableName" should be a string!');
		}
		$this->_authLogTableName = $authLogTableName;
		return true;
	}

	public function getAuthLogTableName() {
		return $this->_authLogTableName;
	}

	/**
	 * Return the database connection component, which should be used while logging.
	 * @return CDbConnection database connection.
	 */
	public function getDbConnection() {
		return Yii::app()->getComponent($this->dbConnectionId);
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
			'onAfterLogin' => 'afterLogin'
		);
	}

	/**
	 * Responds to {@link QsWebUser::onAfterLogin} event.
	 * @param CEvent $event event parameter.
	 */
	public function afterLogin(CEvent $event) {
		$this->writeAuthLog();
	}

	/**
	 * Returns log table schema.
	 * @return CDbTableSchema log table schema.
	 */
	protected function getAuthLogTableSchema() {
		$tableName = $this->getAuthLogTableName();
		$dbConnection = $this->getDbConnection();
		$tableSchema = $dbConnection->getSchema()->getTable($tableName);
		if (!is_object($tableSchema)) {
			throw new CException("Unable to get schema for the log table '{$tableName}'");
		}
		return $tableSchema;
	}

	/**
	 * Returns set of current web user states.
	 * @return array set of web user states.
	 */
	protected function getWebUserStates() {
		$states = array();
		$webUser = $this->getOwner();
		$stateNamePrefix = $webUser->getStateKeyPrefix();
		if (is_array($_SESSION)) {
			foreach ($_SESSION as $name => $value) {
				if (strpos($value, $stateNamePrefix)===0) {
					$states[$name] = $value;
				}
			}
		}
		return $states;
	}

	/**
	 * Returns default auth log data.
	 * @return array default log data.
	 */
	protected function getDefaultAuthLogData() {
		$webUser = $this->getOwner();
		$defaultLogData = array(
			'error_code' => 0,
			'error_message' => '',
			'user_id' => $webUser->getId(),
			'username' => $webUser->getName(),
			'date' => new CDbExpression('NOW()'),
		);
		if (isset($_SERVER['SCRIPT_NAME'])) {
			$defaultLogData['script_name'] = $_SERVER['SCRIPT_NAME'];
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$defaultLogData['ip'] = $_SERVER['REMOTE_ADDR'];
			$defaultLogData['host'] = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
		}
		if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
			$defaultLogData['url'] = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}

		if ($this->allowAuthLogAllStates) {
			$defaultLogData = array_merge($defaultLogData, $this->getWebUserStates());
		}
		return $defaultLogData;
	}

	/**
	 * Composes data to be logged, merging given data with the defaults.
	 * @param array $data given log data.
	 * @return array composed log data.
	 */
	protected function composeAuthLogData(array $data) {
		$defaultData = $this->getDefaultAuthLogData();
		$logData = array_merge($defaultData, $data);
		return $logData;
	}

	/**
	 * Writes data into the log.
	 * @param array $data data to be logged.
	 * @return boolean success.
	 */
	public function writeAuthLog(array $data=array()) {
		$data = $this->composeAuthLogData($data);
		$tableSchema = $this->getAuthLogTableSchema();
		$primaryKeyColumnName = $tableSchema->primaryKey;
		if (array_key_exists($primaryKeyColumnName, $data)) {
			unset($data[$primaryKeyColumnName]);
		}
		return $this->insertAuthLogData($data);
	}

	/**
	 * Creates new record in the table {@link logTableName}.
	 * @param array $data data to be inserted.
	 * @return boolean success.
	 */
	protected function insertAuthLogData(array $data) {
		$insertCommand = $this->getDbConnection()->getCommandBuilder()->createInsertCommand($this->getAuthLogTableName(), $data);
		$insertResult = $insertCommand->execute();
		return ($insertResult>0);
	}

	/**
	 * Writes error log.
	 * @param string $message error message.
	 * @param integer $code error code.
	 * @return boolean success.
	 */
	public function writeAuthLogError($message, $code) {
		$data = array(
			'error_message' => $message,
			'error_code' => $code,
		);
		return $this->writeAuthLog($data);
	}

	/**
	 * Write log by the user identity object.
	 * @param CBaseUserIdentity $userIdentity user identity instance
	 * @return boolean success.
	 */
	public function writeAuthLogFromUserIdentity(CBaseUserIdentity $userIdentity) {
		$data = array(
			'error_message' => $userIdentity->errorMessage,
			'error_code' => $userIdentity->errorCode,
			'user_id' => $userIdentity->getId(),
			'username' => $userIdentity->getName(),
		);
		return $this->writeAuthLog($data);
	}
}
