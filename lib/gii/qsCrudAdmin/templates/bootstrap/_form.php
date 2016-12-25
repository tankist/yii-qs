<?php
/**
 * @var $this QsCrudAdminCode
 */
?>
<?php echo "<?php\n"; ?>
/* @var $this <?php echo $this->getControllerClass(); ?> */
/* @var $model <?php echo $this->getModelClass(); ?> */
/* @var $form TbActiveForm */
?>

<?php echo "<?php \$form = \$this->beginWidget('bootstrap.widgets.TbActiveForm', array(
	'id' => '" . $this->class2id($this->modelClass) . "-form',
	'enableAjaxValidation' => false,
)); ?>\n"; ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo "<?php echo \$form->errorSummary(\$model); ?>\n"; ?>

<?php
foreach ($this->tableSchema->columns as $column) {
	if ($column->autoIncrement) {
		continue;
	}
?>
	<?php echo "<?php echo " . $this->generateActiveRow($this->modelClass, $column) . "; ?>\n"; ?>

<?php
}
?>
	<div class="form-actions">
		<?php echo "<?php echo CHtml::htmlButton(\$model->isNewRecord ? 'Create' : 'Save', array('type'=>'submit', 'class'=>'btn btn-primary')); ?>\n"; ?>
	</div>

<?php echo "<?php \$this->endWidget(); ?>\n"; ?>