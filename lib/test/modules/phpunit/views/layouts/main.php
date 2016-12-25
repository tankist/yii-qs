<?php
/* @var $this DefaultController */
/* @var $clientScript CClientScript */
/* @var $module PhpUnitModule */
/* @var $content string */

$clientScript = Yii::app()->getComponent('clientScript');
$clientScript->coreScriptPosition = CClientScript::POS_HEAD;
$clientScript->scriptMap = array();
$module = $this->getModule();
$baseAssetsUrl = $module->getAssetsUrl();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="language" content="en" />

	<!-- blueprint CSS framework -->
	<link rel="stylesheet" type="text/css" href="<?php echo $baseAssetsUrl; ?>/css/screen.css" media="screen, projection" />
	<link rel="stylesheet" type="text/css" href="<?php echo $baseAssetsUrl; ?>/css/print.css" media="print" />
	<!--[if lt IE 8]>
	<link rel="stylesheet" type="text/css" href="<?php echo $baseAssetsUrl; ?>/css/ie.css" media="screen, projection" />
	<![endif]-->

	<link rel="stylesheet" type="text/css" href="<?php echo $baseAssetsUrl; ?>/css/main.css" />

	<link rel="icon" href="<?php echo $baseAssetsUrl; ?>/images/icons/favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="<?php echo $baseAssetsUrl; ?>/images/icons/favicon.ico" type="image/x-icon" />

	<title><?php echo CHtml::encode($this->pageTitle); ?></title>
</head>

<body>

<div class="container" id="page">
	<div id="header">
		<div id="logo">
			<?php
				$headerHtml = CHtml::image($baseAssetsUrl.'/images/logo.gif', 'PHPUnit', array('width'=>'47', 'height'=>'40'));
				echo CHtml::link($headerHtml,array('/'.$module->getId()));
			?>

		</div>
		<div id="logo-title">
				<b>PHPUnit Test Runner</b>
		</div>

		<?php echo $this->renderPartial('/common/main_menu'); ?>
	</div>
	<div>
	<?php echo $this->renderPartial('/common/navigate_menu'); ?>
	</div>

	<div class="container">
		<div id="content">
			<?php echo $content; ?>
		</div>
	</div>

</div>

<div id="footer">
	Powered by
	<a rel="external" href="https://github.com/sebastianbergmann/phpunit/">PHPUnit</a>
	<br/>A product of <a rel="external" href="http://www.quartsoft.com">QuartSoft</a>
</div>

</body>
</html>