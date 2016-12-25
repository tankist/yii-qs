<?php /* @var $this DefaultController */ ?>
<?php /* @var $content string */ ?>
<?php $this->beginContent($this->module->layout); ?>
	<?php if (!Yii::app()->getComponent('user')->isGuest) { ?>
	<?php $this->widget('zii.widgets.CBreadcrumbs', array(
		'homeLink' => CHtml::link('Main Application', $this->createUrl('/site/index')),
		'links' => $this->breadcrumbs,
	)); ?>
	<?php } ?>
	<div class="container">
		<h2><?php echo CHtml::encode($this->sectionTitle); ?></h2>
		<div class="access_actions">
			<?php
				$this->widget('zii.widgets.CMenu', array(
					'items' => $this->contextMenuItems,
					'htmlOptions' => array(
						'class' => 'operations'
					),
				));
			?>
		</div>
		<?php echo $content; ?>
	</div>
<?php $this->endContent(); ?>
