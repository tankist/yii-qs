<?php
/* @var $this DefaultController */
/* @var $selector PhpUnitSelector */
?>
<?php $this->widget('zii.widgets.CBreadcrumbs', array(
		'homeLink' => false,
		'links' => $selector->getBreadcrumbs(),
	)); ?>

<?php $this->widget('zii.widgets.grid.CGridView', array(
	'id' => 'file-grid',
	'dataProvider' => $selector->createDataProvider(),
	'columns' => array(
		array(
			'name' => '',
			'htmlOptions' => array(
				'width' => '25px',
				'text-align' => 'center',
			),
			'type' => 'raw',
			'value' => '$data->getIsDir() ?
				CHtml::image(Yii::app()->getController()->getModule()->assetsUrl."/images/directory.gif", "directory") :
				CHtml::image(Yii::app()->getController()->getModule()->assetsUrl."/images/file.gif", "file");'
		),
		array(
			'name' => 'name',
			'type' => 'raw',
			'value' => (strlen($selector->getBasePath())<=0) ?
					'CHtml::link($data->name, Yii::app()->urlManager->createUrl( Yii::app()->getController()->getRoute(), array("basepath"=>$data->getName()) ) )'
					:
					'($data->getIsDir() ?
						CHtml::link($data->name, Yii::app()->urlManager->createUrl( Yii::app()->getController()->getRoute(), array("basepath"=>"'.$selector->getBasePath().'", "subpath"=>urlencode($data->getRelativePath("'.$selector->getCurrentBaseTestSuitePath().'")) ) ) )
					:
						CHtml::link($data->name, Yii::app()->urlManager->createUrl( Yii::app()->getController()->getModule()->getId()."/default/run", array("basepath"=>"'.$selector->getBasePath().'", "subpath"=>urlencode($data->getRelativePath("'.$selector->getCurrentBaseTestSuitePath().'")) ) ) )
					)'
		),
		array(
			'name' => 'type',
			'htmlOptions' => array(
				'width' => '200px',
				'text-align' => 'center',
			),
		)
	),
)); ?>