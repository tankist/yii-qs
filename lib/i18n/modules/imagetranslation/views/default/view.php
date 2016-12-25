<?php
/* @var $this DefaultController */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model ImageTranslation */

$this->sectionTitle = 'View Image Translation "'.$model->name.'"';
$this->breadcrumbs[] = $model->name;

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'Update Image Translation', 'url'=>array('update', 'name'=>urlencode($model->name))),
);
?>

<?php
$detailAttributes = array(
	'name',
	'width',
	'height',
	array(
		'label' => 'Default Image',
		'type' => 'raw',
		'value' => CHtml::image($model->getDefaultUrl()),
	),
);

$languageManager = $this->getModule()->getComponent('languageManager');
foreach ($languageManager->getLanguages() as $language) {
	$languageKey = $language->locale_code;
	$languageImageAttribute = array(
		'label' => $language->name.' Image',
		'type' => 'raw',
		'value' => CHtml::link( CHtml::image( $model->fetchUrl($languageKey), $model->name, array('width'=>$model->width, 'height'=>$model->height, 'title'=>'Click to update') ) , array('update', 'name'=>urlencode($model->name), 'language'=>$language->locale_code)),
	);
	$detailAttributes[] = $languageImageAttribute;
}

$this->widget('zii.widgets.CDetailView', array(
	'data' => $model,
	'attributes' => $detailAttributes,
)); ?>