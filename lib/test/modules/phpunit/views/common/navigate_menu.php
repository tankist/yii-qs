<?php /* @var $this DefaultController */ ?>
		<?php if (!Yii::app()->getComponent('user')->getIsGuest()): ?>
		<?php
			$moduleId = $this->getModule()->getId();
			$basePath = isset($_GET['basepath']) ? urldecode($_GET['basepath']) : '';
			$subPath = isset($_GET['subpath']) ? urldecode($_GET['subpath']) : '';
		?>
		<div id="navigate-menus">
		<?php
			$selectTestUrl = array("/{$moduleId}/default/index");
			$runTestUrl = array("/{$moduleId}/default/run");
			if (!empty($basePath)) {
				$selectTestUrl['basepath'] = $basePath;
				$runTestUrl['basepath'] = $basePath;
				if (!empty($subPath)) {
					$selectTestUrl['subpath'] = (strpos($subPath,'.php')!==false) ? urlencode(dirname($subPath)) : urlencode($subPath);
					$runTestUrl['subpath'] = urlencode($subPath);
				}
			}
			$this->widget('zii.widgets.CMenu', array(
				'items' => array(
					array('label'=>'Select Test', 'url'=>$selectTestUrl),
					array('label'=>'Run Test', 'url'=>$runTestUrl),
				),
			));
		?>
		</div>
		<?php endif; ?>