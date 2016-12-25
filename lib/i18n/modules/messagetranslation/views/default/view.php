<?php
/* @var $this DefaultController */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model MessageTranslation */

$assetsUrl = $this->getModule()->getAssetsUrl();
Yii::app()->getClientScript()->registerCssFile($assetsUrl.'/css/translation.css');

$this->sectionTitle = 'View Message Translation';
$this->breadcrumbs[] = 'Message';

$this->contextMenuItems = array(
	array('label'=>'Back To List', 'url'=>array('index')),
	array('label'=>'Update Message Translation', 'url'=>array('update', 'id'=>$model->id)),
);
?>

<?php
$detailAttributes = array(
	'name',
	'category_name',
	'default_content',
);

$languageManager = $this->getModule()->getComponent('languageManager');
foreach ($languageManager->getLanguages() as $language) {
	$languageKey = $language->locale_code;
	$languageMessageAttribute = array(
		'label' => $language->name.' Message',
		'type' => 'raw',
		'value' => CHtml::link(
			($translation = $model->getTranslation($languageKey)) ? CHtml::encode($translation) : 'Not set',
			array("update", "id"=>$model->id, "language"=>$languageKey),
			array("class"=>empty($translation) ? "translation-empty" : "translation")
		),
	);
	$detailAttributes[] = $languageMessageAttribute;
}

$this->widget('zii.widgets.CDetailView', array(
	'data' => $model,
	'attributes' => $detailAttributes,
)); ?>