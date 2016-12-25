<?php
/**
 * @var $this QsCrudAdminCode
 */
?>
<?php echo "<?php\n"; ?>
/* @var $this <?php echo $this->getControllerClass(); ?> */
/* @var $model <?php echo $this->getModelClass(); ?> */
<?php
$labelSingular = $this->class2name($this->modelClass);
$labelPlural = $this->pluralize($labelSingular);
?>
$this->sectionTitle = 'Manage <?php echo $labelPlural; ?>';

$this->contextMenuItems = array(
	array('label'=>'Create <?php echo $labelSingular; ?>', 'url'=>array('create')),
);
?>

<?php echo "<?php \$this->renderPartial('//common/advanced_search', array('model'=>\$model)); ?>"; ?>

<?php echo "<?php"; ?> $this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'record-grid',
	'ajaxUrl' => array($this->getRoute()),
	'dataProvider' => $model->dataProviderAdmin(),
	'filter' => $model,
	'columns' => array(
		array(
			'class' => 'CButtonColumn',
		),
<?php
$count=0;
foreach ($this->tableSchema->columns as $column) {
	if (++$count == 7) {
		echo "\t\t/*\n";
	}
	echo "\t\t'" . $column->name . "',\n";
}
if ($count >= 7)
	echo "\t\t*/\n";
?>
	),
)); ?>
