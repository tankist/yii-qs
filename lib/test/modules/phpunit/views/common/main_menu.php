<?php /* @var $this DefaultController */ ?>
		<div class="top-menus">
		<?php echo CHtml::link('webapp', Yii::app()->homeUrl); ?>
		<?php if (!Yii::app()->getComponent('user')->getIsGuest()): ?>
			| <?php echo CHtml::link('logout',array('default/logout')); ?>
		<?php endif; ?>
		</div>