<?php
/* @var $this QsAuthExternalPopupWindowRedirect */
/* @var $redirectJavaScript string */
/* @var $url string */
?>
<!DOCTYPE html>
<html>
	<head>
		<?php echo CHtml::script($redirectJavaScript); ?>
	</head>
	<body>
		<h2 id="title" style="display:none;">Redirecting back to the &quot;<?php echo Yii::app()->name; ?>&quot;...</h2>
		<h3 id="link"><a href="<?php echo $url; ?>">Click here to return to the &quot;<?php echo Yii::app()->name; ?>&quot;.</a></h3>
		<script type="text/javascript">
			document.getElementById('title').style.display = '';
			document.getElementById('link').style.display = 'none';
		</script>
	</body>
</html>