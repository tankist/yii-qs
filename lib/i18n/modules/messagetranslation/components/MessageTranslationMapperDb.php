<?php
/**
 * MessageTranslationMapperDb class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * MessageTranslationMapperDb is a message translation model mapper, which uses the
 * database to store translations.
 * This mapper works with the {@link CDbMessageSource} message source.
 *
 * @see MessageTranslation
 * @see CDbMessageSource
 *
 * @property string $connectionId public alias of {@link _connectionId}.
 * @property string $sourceMessageTable public alias of {@link _sourceMessageTable}.
 * @property string $translatedMessageTable public alias of {@link _translatedMessageTable}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class MessageTranslationMapperDb extends MessageTranslationMapper {
	/**
	 * @var string the ID of the database connection application component. Defaults to 'db'.
	 */
	protected $_connectionId = '';
	/**
	 * @var string the name of the source message table. Defaults to 'SourceMessage'.
	 */
	protected $_sourceMessageTable = '';
	/**
	 * @var string the name of the translated message table. Defaults to 'Message'.
	 */
	protected $_translatedMessageTable = '';

	public function setConnectionId($connectionId) {
		if (!is_string($connectionId)) {
			throw new CException('"' . get_class($this) . '::connectionId" should be a string!');
		}
		$this->_connectionId = $connectionId;
		return true;
	}

	public function getConnectionId() {
		if (empty($this->_connectionId)) {
			$this->initConnectionId();
		}
		return $this->_connectionId;
	}

	public function setSourceMessageTable($sourceMessageTable) {
		if (!is_string($sourceMessageTable)) {
			throw new CException('"' . get_class($this) . '::sourceMessageTable" should be a string!');
		}
		$this->_sourceMessageTable = $sourceMessageTable;
		return true;
	}

	public function getSourceMessageTable() {
		if (empty($this->_sourceMessageTable)) {
			$this->initSourceMessageTable();
		}
		return $this->_sourceMessageTable;
	}

	public function setTranslatedMessageTable($translatedMessageTable) {
		if (!is_string($translatedMessageTable)) {
			throw new CException('"' . get_class($this) . '::translatedMessageTable" should be a string!');
		}
		$this->_translatedMessageTable = $translatedMessageTable;
		return true;
	}

	public function getTranslatedMessageTable() {
		if (empty($this->_translatedMessageTable)) {
			$this->initTranslatedMessageTable();
		}
		return $this->_translatedMessageTable;
	}

	/**
	 * Initializes {@link connectionId} value.
	 * @return boolean success.
	 */
	protected function initConnectionId() {
		$connectionId = 'db';
		$messageSource = Yii::app()->getMessages();
		if (is_a($messageSource, 'CDbMessageSource')) {
			$connectionId = $messageSource->connectionID;
		}
		$this->_connectionId = $connectionId;
		return true;
	}

	/**
	 * Initializes {@link sourceMessageTable} value.
	 * @return boolean success.
	 */
	protected function initSourceMessageTable() {
		$sourceMessageTable = 'SourceMessage';
		$messageSource = Yii::app()->getMessages();
		if (is_a($messageSource, 'CDbMessageSource')) {
			$sourceMessageTable = $messageSource->sourceMessageTable;
		}
		$this->_sourceMessageTable = $sourceMessageTable;
		return true;
	}

	/**
	 * Initializes {@link translatedMessageTable} value.
	 * @return boolean success.
	 */
	protected function initTranslatedMessageTable() {
		$translatedMessageTable = 'Message';
		$messageSource = Yii::app()->getMessages();
		if (is_a($messageSource, 'CDbMessageSource')) {
			$translatedMessageTable = $messageSource->translatedMessageTable;
		}
		$this->_translatedMessageTable = $translatedMessageTable;
		return true;
	}

	/**
	 * Returns the database connection application component,
	 * regarding to {@link connectionId} value.
	 * @return CDbConnection database connection instance.
	 */
	protected function getDbConnection() {
		return Yii::app()->getComponent($this->getConnectionId());
	}

	/**
	 * Creates new database query command.
	 * @return CDbCommand database query command.
	 */
	protected function createDbCommand() {
		return $this->getDbConnection()->createCommand();
	}

	/**
	 * Saves the particular message translation content on particular language.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @param string $language language locale code.
	 * @param string $content message content on specified language.
	 * @return boolean success.
	 */
	protected function saveTranslation($category, $name, $language, $content) {
		$sourceMessage = $this->fetchSourceMessage($category, $name);
		$translatedMessage = $this->findTranslatedMessage($sourceMessage['id'], $language);
		if (empty($translatedMessage)) {
			return $this->insertTranslatedMessage($sourceMessage['id'], $language, $content);
		} else {
			return $this->updateTranslatedMessage($sourceMessage['id'], $language, $content);
		}
	}

	/**
	 * Finds the source message record in the database, creates this record
	 * if it does not exist.
	 * @throws CException on failure.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @return array source message record.
	 */
	protected function fetchSourceMessage($category, $name) {
		$sourceMessage = $this->findSourceMessage($category, $name);
		if (empty($sourceMessage)) {
			$this->insertSourceMessage($category, $name);
			$sourceMessage = $this->findSourceMessage($category, $name);
			if (empty($sourceMessage)) {
				throw new CException("Unable to fetch source message category='{$category}' name='{$name}'");
			}
		}
		return $sourceMessage;
	}

	/**
	 * Finds the source message record in the database.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @return mixed array source message row or false if no row found.
	 */
	protected function findSourceMessage($category, $name) {
		$dbCommand = $this->createDbCommand();
		$dbCommand
			->select('*')
			->from($this->getSourceMessageTable())
			->where('category = :category AND message = :name');
		$params = array(
			'category' => $category,
			'name' => $name
		);
		$sourceMessage = $dbCommand->queryRow(true, $params);
		return $sourceMessage;
	}

	/**
	 * Insert source message record in the database.
	 * @param string $category message category name.
	 * @param string $name message self name.
	 * @return boolean success.
	 */
	protected function insertSourceMessage($category, $name) {
		$dbCommand = $this->createDbCommand();
		$columns = array(
			'category' => $category,
			'message' => $name,
		);
		$insertResult = $dbCommand->insert($this->getSourceMessageTable(), $columns);
		return ($insertResult>0);
	}

	/**
	 * Finds the translated message record in the database.
	 * @param integer $sourceMessageId source message id.
	 * @param string $language language locale code.
	 * @return mixed array translated message row or false if no row found.
	 */
	protected function findTranslatedMessage($sourceMessageId, $language) {
		$dbCommand = $this->createDbCommand();
		$dbCommand
			->select('*')
			->from($this->getTranslatedMessageTable())
			->where('id = :id AND language = :language');
		$params = array(
			'id' => $sourceMessageId,
			'language' => $language
		);
		$translatedMessage = $dbCommand->queryRow(true, $params);
		return $translatedMessage;
	}

	/**
	 * Insert translated message record in the database.
	 * @param integer $sourceMessageId source message id.
	 * @param string $language language locale code.
	 * @param string $content translated content.
	 * @return boolean success.
	 */
	protected function insertTranslatedMessage($sourceMessageId, $language, $content) {
		$dbCommand = $this->createDbCommand();
		$columns = array(
			'id' => $sourceMessageId,
			'language' => $language,
			'translation' => $content,
		);
		$insertResult = $dbCommand->insert($this->getTranslatedMessageTable(), $columns);
		return ($insertResult > 0);
	}

	/**
	 * Update existing translated message record in the database.
	 * @param integer $sourceMessageId source message id.
	 * @param string $language language locale code.
	 * @param string $content translated content.
	 * @return boolean success.
	 */
	protected function updateTranslatedMessage($sourceMessageId, $language, $content) {
		$dbCommand = $this->createDbCommand();
		$columns = array(
			'translation' => $content,
		);
		$params = array(
			'id' => $sourceMessageId,
			'language' => $language,
		);
		$updateResult = $dbCommand->update($this->getTranslatedMessageTable(), $columns, 'id=:id AND language=:language', $params);
		return ($updateResult > 0);
	}

	/**
	 * Finds existing message translations.
	 * While results filtering is performed separately,
	 * passed search filter can be used to filter translation list at this
	 * stage to save performance.
	 * @param MessageTranslationFilter $filter search filter.
	 * @return array list of translation data, each translation data is an array
	 * with following keys: 'name', 'category', 'language', 'content'.
	 */
	protected function findTranslations(MessageTranslationFilter $filter) {
		$dbCommand = $this->createDbCommand();

		$dbCommand
			->select('t.language AS language, t.translation AS content, s.message AS name, s.category AS category')
			->from($this->getTranslatedMessageTable() . ' AS t')
			->join($this->getSourceMessageTable() . ' AS s', 's.id = t.id');

		$params = array();
		// Apply filter to save performance:
		if (!$filter->isEmpty()) {
			$where = array(
				'AND'
			);
			if (!empty($filter->category_name)) {
				$where[] = 's.category LIKE :category_name';
				$params[':category_name'] = '%' . $filter->category_name . '%';
			}
			if (!empty($filter->name)) {
				$where[] = 's.message LIKE :name';
				$params[':name'] = '%' . $filter->name . '%';
			}
			if (count($where)>1) {
				$dbCommand->where($where);
			}
		}

		$rows = $dbCommand->queryAll(true,$params);
		return $rows;
	}
}
