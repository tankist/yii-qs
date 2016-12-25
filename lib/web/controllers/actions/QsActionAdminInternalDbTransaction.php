<?php
/**
 * QsActionAdminInternalDbTransaction class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.controllers.actions.QsActionAdminBase', true);

/**
 * QsActionAdminInternalDbTransaction introduces the transaction usage for the controller action.
 * This action uses {@link QsBehaviorInternalDbTransaction} as mixing.
 * You can disable transaction usage by setting {@link useInternalDbTransaction} to "false".
 * Note: child class should manually trigger begin, commit and rollback for transaction,
 * depending on the particular action logic.
 *
 * Example:
 * <code>
 * class MyAction extends QsActionAdminInternalDbTransaction {
 *     ...
 *     public function run() {
 *         ...
 *         try {
 *             $this->beginInternalDbTransaction();
 *             $model->save();
 *             $this->commitInternalDbTransaction();
 *         } catch (Exception $exception) {
 *             $this->rollbackInternalDbTransaction();
 *             throw $exception;
 *         }
 *         ...
 *     }
 * }
 * </code>
 *
 * @property QsBehaviorInternalDbTransaction internalDbTransactionBehavior
 * @method getInternalDbTransaction
 * @method commitInternalDbTransaction
 * @method rollbackInternalDbTransaction
 *
 * @see QsBehaviorInternalDbTransaction
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.controllers.actions
 */
abstract class QsActionAdminInternalDbTransaction extends QsActionAdminBase {
	/**
	 * @var boolean indicates if transaction should be used during action run.
	 * Note: actual influence of this option depends on child class functionality.
	 */
	public $useInternalDbTransaction = true;

	/**
	 * Constructor.
	 * @param CController $controller the controller who owns this action.
	 * @param string $id id of the action.
	 */
	public function __construct($controller, $id) {
		parent::__construct($controller, $id);
		$this->attachBehavior('internalDbTransactionBehavior', $this->createInternalDbTransactionBehaviorConfig());
	}

	/**
	 * Creates the configuration array for the database transaction behavior.
	 * @return array behavior config.
	 */
	protected function createInternalDbTransactionBehaviorConfig() {
		$behaviorConfig = array(
			'class' => 'qs.db.QsBehaviorInternalDbTransaction'
		);
		return $behaviorConfig;
	}

	/**
	 * Starts an internal transaction.
	 * If there is already opened transaction, no new one will be started.
	 * @return boolean indicates if new transaction has been actually started.
	 */
	public function beginInternalDbTransaction() {
		if ($this->useInternalDbTransaction) {
			return $this->internalDbTransactionBehavior->beginInternalDbTransaction();
		}
		return false;
	}
}
