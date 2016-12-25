<?php
/* @var $this DefaultController */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model ImageTranslation */
/* @var $form CActiveForm */

$this->sectionTitle = 'Update Image Translation "'.$model->name.'"';
$this->breadcrumbs[$model->name] = array('view', 'name'=>$model->name);
$this->breadcrumbs[] = 'Update';

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'View Image Translation', 'url'=>array('view', 'name'=>urlencode($model->name))),
);
?>

<div class="form">

<?php
$languageManager = $this->getModule()->getComponent('languageManager');

$form = $this->beginWidget('CActiveForm', array(
	'id' => 'model-form',
	'enableAjaxValidation' => false,
	'htmlOptions' => array(
		'enctype' => 'multipart/form-data',
	)
)); ?>

	<p class="note">Fields with <span class="required">*</span> are required.</p>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo CHtml::label('Default image', null) ?>
		<?php echo CHtml::image($model->getDefaultUrl()); ?>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model, 'language'); ?>
		<?php echo $form->dropDownList($model, 'language', CHtml::listData($languageManager->getLanguages(),'locale_code','name') ); ?>
		<?php echo $form->error($model, 'language'); ?>
	</div>
	<div class="row">
		<?php echo CHtml::label('Current image', null) ?>
		<?php $this->widget($this->getModule()->getName().'.components.widgets.ImageTranslationDynamicImageWidget',array('model'=>$model)); ?>
	</div>
	<div class="row">
		<?php echo $form->labelEx($model, 'file'); ?>
		<?php echo $form->fileField($model, 'file'); ?>
		<?php echo $form->error($model, 'file'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton('Save'); ?>
	</div>

<?php $this->endWidget(); ?>

</div>