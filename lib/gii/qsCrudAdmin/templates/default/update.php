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
$this->sectionTitle = 'Update <?php echo $labelSingular; ?> #' . $model->id;
$this->breadcrumbs[$model->id] = array('view', 'id'=>$model->id);
$this->breadcrumbs[] = 'Update';

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'Create <?php echo $labelSingular; ?>', 'url'=>array('create')),
	array('label'=>'View <?php echo $labelSingular; ?>', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Delete <?php echo $labelSingular; ?>', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete', 'id'=>$model->id), 'confirm'=>'Are you sure you want to delete this item?')),
);
?>
<?php echo "<?php echo \$this->renderPartial('_form', array('model'=>\$model)); ?>"; ?>