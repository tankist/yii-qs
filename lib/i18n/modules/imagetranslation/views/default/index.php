<?php
/* @var $this DefaultController */
/* @var $languageManager QsTranslationLanguageManager */
/* @var $model ImageTranslation */
/* @var $filter ImageTranslationFilter */

$this->sectionTitle = 'Manage Image Translations';
?>

<p>
	This section manages image translations. Use the column filters to find a particular element.
	To upload new image translation, simply click the missing image in the list.
</p>

<?php

$listColumns = array(
	array(
		'class'=>'CButtonColumn',
		'template'=>'{view} {update}',
		'viewButtonUrl'=>'Yii::app()->controller->createUrl("view",array("name"=>urlencode($data->name)))',
		'updateButtonUrl'=>'Yii::app()->controller->createUrl("update",array("name"=>urlencode($data->name)))',
	),
	'name',
	'width',
	'height',
	array(
		'header'=>'Default Image',
		'type'=>'raw',
		'value'=>'CHtml::image( $data->getDefaultUrl() )',
	),
);

$languageManager = $this->getModule()->getComponent('languageManager');
foreach ($languageManager->getLanguages() as $language) {
	$languageKey = $language->locale_code;
	$languageImageColumn = array(
		'header' => $language->name.' Image',
		'type' => 'raw',
		'name' => 'exist_'.$languageKey,
		'filter' => array(
			'missing' => 'Missing Only',
			'present' => 'Present Only',
		),
		'value' => 'CHtml::link( CHtml::image( $data->fetchUrl("'.$languageKey.'"), $data->name, array("width"=>$data->width, "height"=>$data->height, "title"=>"Click to update") ) , array("update", "name"=>urlencode($data->name), "language"=>'.$languageKey.'))',
	);
	$listColumns[] = $languageImageColumn;
}

$this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'record-grid',
	'dataProvider' => $model->dataProvider($filter),
	'filter' => $filter,
	'columns' => $listColumns,
)); ?>
