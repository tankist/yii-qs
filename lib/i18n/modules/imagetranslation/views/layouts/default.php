<?php
/* @var $this DefaultController */
/* @var $cs CClientScript */
/* @var $content string */
$cs = Yii::app()->clientScript;
$cs->coreScriptPosition = CClientScript::POS_HEAD;
$cs->scriptMap = array();
$baseUrl = $this->module->assetsUrl;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />

	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo $this->module->assetsUrl; ?>/css/screen.css" media="screen, projection" />
	<link rel="stylesheet" type="text/css" href="<?php echo $this->module->assetsUrl; ?>/css/print.css" media="print" />
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo $this->module->assetsUrl; ?>/css/ie.css" media="screen, projection" />
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo $this->module->assetsUrl; ?>/css/main.css" />

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>

<body>

<div class="container" id="page">
	<div id="header">
		<div id="logo">
			<h1><?php echo Yii::app()->name; ?> Image Translation Manager</h1>
		</div>

		<?php echo $this->renderPartial('/common/main_menu'); ?>
	</div><!-- header -->

	<div class="container">
		<div id="content">
			<?php echo $content; ?>
		</div><!-- content -->
	</div>

</div><!-- page -->

<div id="footer">
	Powered by <a href="http://www.quartsoft.com">QuartSoft Ltd</a>.
</div><!-- footer -->

</body>
</html>