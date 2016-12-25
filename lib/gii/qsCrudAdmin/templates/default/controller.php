<?php
/**
 * This is the template for generating a controller class file for CRUD feature.
 * @var $this QsCrudAdminCode
 */
?>
<?php echo "<?php\n"; ?>

class <?php echo $this->controllerClass; ?> extends <?php echo $this->baseControllerClass; ?> {
	public function init() {
		$this->setModelClassName('<?php echo $this->modelClass; ?>');

		$this->breadcrumbs = array(
			'<?php echo $this->pluralize($this->class2name($this->modelClass)); ?>' => array($this->getId() . '/'),
		);
	}
}
