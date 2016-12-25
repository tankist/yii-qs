<?php
/**
 * This is the template for generating the model class of a specified table.
 * @var $this ModelCode
 * @var $tableName string: the table name for this class (prefix is already removed if necessary)
 * @var $modelClass string: the model class name
 * @var $columns CDbColumnSchema[]: list of table columns (name=>CDbColumnSchema)
 * @var $labels array: list of attribute labels (name=>label)
 * @var $rules array: list of validation rules
 * @var $relations array: list of relations (name=>relation declaration)
 * @var $connectionId string: database connection id
 */
?>
<?php echo "<?php\n"; ?>

/**
 * This is the model class for table "<?php echo $tableName; ?>".
 *
 * The followings are the available columns in table '<?php echo $tableName; ?>':
<?php foreach ($columns as $column): ?>
 * @property <?php echo $column->type . ' $' . $column->name . "\n"; ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
 * The followings are the available model relations:
<?php foreach ($relations as $name=>$relation): ?>
 * @property <?php
	if (preg_match("~^array\(self::([^,]+), '([^']+)', '([^']+)'\)$~", $relation, $matches)) {
		$relationType = $matches[1];
		$relationModel = $matches[2];

		switch ($relationType) {
			case 'HAS_ONE':
				echo $relationModel . ' $' . $name . "\n";
			break;
			case 'BELONGS_TO':
				echo $relationModel . ' $' . $name . "\n";
			break;
			case 'HAS_MANY':
				echo $relationModel . '[] $' . $name . "\n";
			break;
			case 'MANY_MANY':
				echo $relationModel . '[] $' . $name . "\n";
			break;
			default:
				echo 'mixed $' . $name . "\n";
		}
	}
	?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?php echo $modelClass; ?> extends <?php echo $this->baseClass; ?> {
	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return '<?php echo $tableName; ?>';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules() {
		return array(
<?php foreach ($rules as $rule): ?>
			<?php echo $rule . ",\n"; ?>
<?php endforeach; ?>
			array('<?php echo implode(', ', array_keys($columns)); ?>', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations() {
		return array(
<?php foreach ($relations as $name => $relation): ?>
			<?php echo "'$name' => $relation,\n"; ?>
<?php endforeach; ?>
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels() {
		return array(
<?php foreach ($labels as $name => $label): ?>
			<?php echo "'$name' => '$label',\n"; ?>
<?php endforeach; ?>
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * This method should be called only in admin panel.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function dataProviderAdmin() {
		$criteria = new CDbCriteria;

<?php
foreach ($columns as $name => $column) {
	if ($column->type === 'string') {
		echo "\t\t\$criteria->compare('t.$name', \$this->$name,true);\n";
	} else {
		echo "\t\t\$criteria->compare('t.$name', \$this->$name);\n";
	}
}
?>

		return new CActiveDataProvider(get_class($this), array(
			'criteria' => $criteria,
		));
	}

	/**
	 * Creates the data provider, using current model criteria.
	 * @param array $config data provider config.
	 * @return CActiveDataProvider the data provider instance.
	 */
	public function dataProvider(array $config = array()) {
		$criteria = $this->getDbCriteria();
		if (array_key_exists('criteria', $config)) {
			$criteria->mergeWith($config['criteria']);
		}
		$config['criteria'] = $criteria;
		return new CActiveDataProvider(get_class($this), $config);
	}
	
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return <?php echo $modelClass; ?> the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
<?php if ($connectionId != 'db'):?>

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection() {
		return Yii::app()-><?php echo $connectionId ?>;
	}
<?php endif ?>
}