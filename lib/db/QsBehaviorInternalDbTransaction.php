<?php
/**
 * QsBehaviorInternalDbTransaction class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsBehaviorInternalDbTransaction - behavior, which allows the isolated component to
 * perform the internal database transaction.
 * This behavior handles the problem of nested transactions: if database transaction already opened,
 * the new one will not be created.
 * This behavior only appends the methods to its owner, but does not handles any events.
 * So the owner should manually trigger begin, commit and rollback for transaction,
 * depending on the particular component logic.
 * Example:
 * <code>
 * class MyComponent extends CApplicationComponent {
 *     public $behaviors = array(
 *         'internalDbTransactionBehavior' => array(
 *             'class'=>'qs.db.QsBehaviorInternalDbTransaction'
 *         ),
 *     );
 *     ...
 *     public function someMethod() {
 *         ...
 *         try {
 *             $this->beginInternalDbTransaction();
 *             $this->someDbAffectMethod();
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
 * @property CDbTransaction $internalDbTransaction public alias of {@link _internalDbTransaction}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db
 */
class QsBehaviorInternalDbTransaction extends CBehavior {
	/**
	 * @var CDbTransaction internal transaction.
	 */
	protected $_internalDbTransaction = null;

	// Set / Get :

	public function setInternalDbTransaction($internalDbTransaction) {
		$this->_internalDbTransaction = $internalDbTransaction;
		return true;
	}

	public function getInternalDbTransaction() {
		return $this->_internalDbTransaction;
	}

	/**
	 * Starts an internal database transaction.
	 * If there is already opened transaction, no new one will be started.
	 * @return boolean indicates if new transaction has been actually started.
	 */
	public function beginInternalDbTransaction() {
		$dbConnection = Yii::app()->getDb();
		$currentDbTransaction = $dbConnection->getCurrentTransaction();
		if (!is_object($currentDbTransaction)) {
			$dbTransaction = $dbConnection->beginTransaction();
			$this->setInternalDbTransaction($dbTransaction);
			return true;
		}
		return false;
	}

	/**
	 * Commits an internal database transaction.
	 * @return boolean success.
	 */
	public function commitInternalDbTransaction() {
		$internalDbTransaction = $this->getInternalDbTransaction();
		if (is_object($internalDbTransaction)) {
			if ($internalDbTransaction->getActive()) {
				$internalDbTransaction->commit();
			}
			$this->setInternalDbTransaction(null);
		}
		return true;
	}

	/**
	 * Rolls back an internal database transaction.
	 * @return boolean success.
	 */
	public function rollbackInternalDbTransaction() {
		$internalDbTransaction = $this->getInternalDbTransaction();
		if (is_object($internalDbTransaction)) {
			if ($internalDbTransaction->getActive()) {
				$internalDbTransaction->rollback();
			}
			$this->setInternalDbTransaction(null);
		}
		return true;
	}
}
