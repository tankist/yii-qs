<?php
/* @var $this DefaultController */
/* @var $log string */
/* @var $consoleCommandOutput string */
?>
<?php
$this->widget('phpunit.components.widgets.PhpUnitLogViewWidget', array(
	 'xml' => $log,
	 'consoleCommandOutput' => $consoleCommandOutput
 ));
?>