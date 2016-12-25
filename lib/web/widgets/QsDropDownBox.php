<?php
/**
 * QsDropDownBox class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsDropDownBox renders the drop down box triggered by link clicking.
 *
 * You should specify the label and content for the drop down box.
 * For example:
 * <code>
 * $this->widget('qs.web.widgets.QsDropDownBox', array(
 *     'label' => 'Click Me',
 *     'content' => 'Drop down content',
 * ));
 * </code>
 * You may also set the {@link items} property, in this case the internal {@link CMenu}
 * widget will be used to create a drop down box content.
 * For example:
 * <code>
 * $this->widget('qs.web.widgets.QsDropDownBox', array(
 *     'label' => 'Click Me',
 *     'items' => array(
 *         array(
 *             'label' => 'Index page',
 *             'url' => array('index'),
 *         ),
 *         array(
 *             'label' => 'Edit data',
 *             'url' => array('edit'),
 *         ),
 *     ),
 * ));
 * </code>
 *
 * @property string $assetsUrl public alias of {@link _assetsUrl}.
 * @property string $containerId public alias of {@link _containerId}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets
 */
class QsDropDownBox extends CWidget {
	/**
	 * @var string URL for the associated assets.
	 */
	protected $_assetsUrl = '';
	/**
	 * @var string the URL of the CSS file used by this widget. Defaults to null, meaning using the integrated
	 * CSS file. If this is set false, you are responsible to explicitly include the necessary CSS file in your page.
	 */
	public $cssFile;
	/**
	 * @var string id of the container HTML element.
	 */
	protected $_containerId = '';
	/**
	 * @var array the initial component JavaScript options.
	 */
	public $options = array();
	/**
	 * @var array the HTML attributes that should be rendered in the HTML tag representing the trigger element.
	 */
	public $triggerHtmlOptions = array();
	/**
	 * @var array the HTML attributes that should be rendered in the HTML tag representing the container element.
	 */
	public $containerHtmlOptions = array();
	/**
	 * @var string the HTML fragment for the link, which triggers the drop down box.
	 */
	public $label = '';
	/**
	 * @var string the HTML content, which should be rendered inside the drop down container.
	 */
	public $content = '';
	/**
	 * @var array|null items, which should be used to generated content menu.
	 * If this field is set the internal {@link CMenu} widget will be created to fill up
	 * container content.
	 * @see CMenu::items
	 */
	public $items = null;

	// Set / Get :

	public function setContainerId($containerId) {
		$this->_containerId = $containerId;
		return true;
	}

	public function getContainerId() {
		if (empty($this->_containerId)) {
			$this->_containerId = $this->getId().'_container';
		}
		return $this->_containerId;
	}

	/**
	 * @return string associated assets URL.
	 */
	public function getAssetsUrl() {
		if (empty($this->_assetsUrl)) {
			$this->_assetsUrl = $this->publishAssets();
		}
		return $this->_assetsUrl;
	}

	/**
	 * Publishes related assets.
	 * @return string assets URL.
	 */
	protected function publishAssets() {
		$assetsPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'dropdownbox';
		$assetsUrl = Yii::app()->getAssetManager()->publish($assetsPath);
		return $assetsUrl;
	}

	/**
	 * Registers the required client scripts.
	 */
	protected function registerClientScript() {
		$clientScript = Yii::app()->getClientScript();
		$clientScript->registerCoreScript('jquery');

		$assetsUrl = $this->getAssetsUrl();
		Yii::app()->getClientScript()->registerScriptFile($assetsUrl.'/dropdownbox.js');

		$triggerId = $this->getId();
		$this->options['containerId'] = $this->getContainerId();
		$options = CJavaScript::encode($this->options);
		$javaScript = "jQuery('#{$triggerId}').dropdownbox({$options});";
		$clientScript->registerScript(__CLASS__.'#'.$this->getId(), $javaScript);
	}

	/**
	 * Registers the CSS files.
	 */
	protected function registerClientCss() {
		if ($this->cssFile!==false) {
			if (empty($this->cssFile)) {
				$assetsUrl = $this->getAssetsUrl();
				$this->cssFile = $assetsUrl.'/dropdownbox.css';
			}
			Yii::app()->getClientScript()->registerCssFile($this->cssFile);
		}
	}

	/**
	 * Renders the trigger HTML element.
	 */
	protected function renderTrigger() {
		$htmlOptions = $this->triggerHtmlOptions;
		$htmlOptions['id'] = $this->getId();
		if (!array_key_exists('class', $htmlOptions)) {
			$htmlOptions['class'] = 'drop-down-box-trigger';
		}
		echo CHtml::link($this->label, '#', $htmlOptions);
	}

	/**
	 * Renders the container HTML element.
	 */
	protected function renderContainer() {
		$htmlOptions = $this->containerHtmlOptions;
		$htmlOptions['id'] = $this->getContainerId();
		if (!array_key_exists('class', $htmlOptions)) {
			$htmlOptions['class'] = 'drop-down-box-container';
		}
		echo CHtml::openTag('div',$htmlOptions);
		$this->renderContainerContent();
		echo CHtml::closeTag('div');
	}

	/**
	 * Renders the drop down container content.
	 */
	protected function renderContainerContent() {
		if (is_array($this->items)) {
			$menuOptions = array(
				'id' => $this->getId().'_menu',
				'items' => $this->items
			);
			$this->widget('zii.widgets.CMenu', $menuOptions);
		} else {
			echo $this->content;
		}
	}

	/**
	 * Initializes the widget.
	 * This method is called by {@link CBaseController::createWidget}
	 * and {@link CBaseController::beginWidget} after the widget's
	 * properties have been initialized.
	 */
	public function init() {
		$this->registerClientScript();
		$this->registerClientCss();
	}

	/**
	 * Executes the widget.
	 * This method is called by {@link CBaseController::endWidget}.
	 */
	public function run() {
		$this->renderTrigger();
		$this->renderContainer();
	}
}
