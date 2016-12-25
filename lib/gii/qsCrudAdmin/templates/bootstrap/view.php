<?php
/**
 * @var $this QsCrudAdminCode
 */
?>
<?php echo "<?php\n"; ?>
/* @var $this <?php echo $this->getControllerClass(); ?> */
/* @var $model <?php echo $this->getModelClass(); ?> */

<?php
$nameColumn = $this->guessNameColumn($this->tableSchema->columns);
$labelSingular = $this->class2name($this->modelClass);
$labelPlural = $this->pluralize($labelSingular);
?>
$this->sectionTitle = 'View <?php echo $labelSingular; ?> #' . $model->id;
$this->breadcrumbs[] = $model->id;

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'Create <?php echo $labelSingular; ?>', 'url'=>array('create')),
	array('label'=>'Update <?php echo $labelSingular; ?>', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete <?php echo $labelSingular; ?>', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete', 'id'=>$model->id), 'confirm'=>'Are you sure you want to delete this item?')),
);
?>
<?php echo "<?php"; ?> $this->widget('bootstrap.widgets.TbDetailView', array(
	'data' => $model,
	'attributes' => array(
<?php
foreach ($this->tableSchema->columns as $column)
	echo "\t\t'".$column->name."',\n";
?>
	),
)); ?>
