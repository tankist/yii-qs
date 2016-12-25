<?php
/* @var $this DefaultController */
/* @var $messageTranslationMapper MessageTranslationMapper */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model MessageTranslation */
/* @var $filter MessageTranslationFilter */

$assetsUrl = $this->getModule()->getAssetsUrl();
Yii::app()->getClientScript()->registerCssFile($assetsUrl.'/css/translation.css');

$this->sectionTitle = 'Manage Message Translations';
?>

<p>
	This section manages message translations. Use the column filters to find a particular element.
	In order to update message translation, simply click on it.
</p>

<?php

$messageTranslationMapper = $this->getModule()->getComponent('messageTranslationMapper');
$categoryNames = $messageTranslationMapper->getMessageCategoryNames();
$categoryFilter = array();
foreach ($categoryNames as $categoryName) {
	$categoryFilter[$categoryName] = $categoryName;
}

$listColumns = array(
	array(
		'class' => 'CButtonColumn',
		'template' => '{view} {update}',
		'viewButtonUrl' => 'Yii::app()->controller->createUrl("view",array("id"=>$data->id))',
		'updateButtonUrl' => 'Yii::app()->controller->createUrl("update",array("id"=>$data->id))',
	),
	array(
		'name' => 'name',
		'type' => 'text',
		'header' => $filter->getAttributeLabel('name'),
	),
	array(
		'name' => 'category_name',
		'header' => $filter->getAttributeLabel('category_name'),
		'filter' => $categoryFilter
	),
	array(
		'name' => 'default_content',
		'type' => 'text',
		'header' => $filter->getAttributeLabel('default_content'),
	),
);

$languageManager = $this->getModule()->getComponent('languageManager');
foreach ($languageManager->getLanguages() as $language) {
	$languageKey = $language->locale_code;
	$languageMessageColumn = array(
		'header' => $language->name.' Message',
		'type' => 'raw',
		'name' => 'content_'.$languageKey,
		'value' => '
			CHtml::link(
				( $translation = $data->getTranslation("'.$languageKey.'") )
					? CHtml::encode($translation)
					: \'Not set\'
				, array("update", "id"=>$data->id, "language"=>'.$languageKey.')
				, array("class"=>empty($translation) ? "translation-empty" : "translation")
			)
		',
	);
	$listColumns[] = $languageMessageColumn;
}

$this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'record-grid',
	'dataProvider' => $model->dataProvider($filter),
	'filter' => $filter,
	'columns' => $listColumns,
)); ?>
