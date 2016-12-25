<?php
/* @var $this DefaultController */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model MessageTranslation */
/* @var $form CActiveForm */

$this->sectionTitle = 'Update Message Translation';
$this->breadcrumbs['Message'] = array('view', 'id'=>$model->id);
$this->breadcrumbs[] = 'Update';

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'View Message Translation', 'url'=>array('view', 'id'=>$model->id)),
);
?>

<div class="form">

<?php
$languageManager = $this->getModule()->getComponent('languageManager');

$form = $this->beginWidget('CActiveForm', array(
	'id' => 'model-form',
	'enableAjaxValidation' => false,
	'htmlOptions' => array(
		'accept-charset' => 'UTF-8',
	)
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model, 'name'); ?>
		<div><?php echo CHtml::encode($model->name); ?></div>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model, 'category_name'); ?>
		<?php echo $model->category_name; ?>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model, 'default_content'); ?>
		<div><?php echo CHtml::encode($model->default_content); ?></div>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model, 'language'); ?>
		<?php echo $form->dropDownList($model, 'language', CHtml::listData($languageManager->getLanguages(),'locale_code','name') ); ?>
		<?php echo $form->error($model, 'language'); ?>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model, 'content'); ?>
		<?php echo $form->textArea($model, 'content', array('rows'=>6, 'cols'=>70)); ?>
		<?php echo $form->error($model, 'content'); ?>
	</div>

	<?php $this->widget($this->getModule()->getName().'.components.widgets.MessageTranslationDynamicContentWidget',array('model'=>$model)); ?>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div>