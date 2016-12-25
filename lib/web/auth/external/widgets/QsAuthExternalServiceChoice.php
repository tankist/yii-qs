<?php
/**
 * QsAuthExternalServiceChoice class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAuthExternalServiceChoice prints buttons for authentication via external auth services.
 * By default this widget relies on presence of {@link QsAuthExternalServiceHub} among application components
 * to get external services information.
 *
 * Example:
 * <code>
 * <?php $this->widget('qs.web.auth.external.widgets.QsAuthExternalServiceChoice'); ?>
 * </code>
 *
 * You can customize of the widget appearance by setting {@link autoRender} to "false",
 * using method {@link authLink()} or {@link composeExternalServiceUrl()}.
 * For example:
 * <code>
 * <?php $authChoice = $this->beginWidget('qs.web.auth.external.widgets.QsAuthExternalServiceChoice', array(
 *     'autoRender' => false,
 * )); ?>
 * <ul>
 * <?php foreach ($authChoice->getServices() as $service): ?>
 *     <li><?php $authChoice->authLink($service); ?></li>
 * <?php endforeach; ?>
 * </ul>
 * <?php $this->endWidget(); ?>
 * </code>
 *
 * @property QsAuthExternalService[] $externalServices public alias of {@link _services}.
 * @property array $baseAuthUrl public alias of {@link _baseAuthUrl}.
 * @property string $assetsUrl public alias of {@link _assetsUrl}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.external.widgets
 */
class QsAuthExternalServiceChoice extends CWidget {
	/**
	 * @var QsAuthExternalService[] external services list.
	 */
	protected $_services;
	/**
	 * @var string name of the external service collection application component.
	 * This component will be used to fetch {@link services} value if it is not set.
	 */
	public $serviceCollectionComponentName = 'externalAuth';
	/**
	 * @var array configuration for the external services base authentication URL.
	 */
	protected $_baseAuthUrl;
	/**
	 * @var string name of the GET param , which should be used to passed external service name to URL
	 * defined by {@link baseAuthUrl}.
	 */
	public $serviceIdGetParamName = 'service';
	/**
	 * @var string URL for the associated assets.
	 */
	protected $_assetsUrl = '';
	/**
	 * @var string the URL of the CSS file used by this widget. Defaults to null, meaning using the integrated CSS file.
	 * If this is set false, you are responsible to explicitly include the necessary CSS file in your page.
	 */
	public $cssFile;
	/**
	 * @var array the HTML attributes that should be rendered in the div HTML tag representing the container element.
	 */
	public $mainContainerHtmlOptions = array(
		'class' => 'services'
	);
	/**
	 * @var boolean indicates if popup window should be used instead of direct links.
	 */
	public $popupMode = true;
	/**
	 * @var boolean indicates if widget content, should be rendered automatically.
	 */
	public $autoRender = true;

	public function setServices(array $services) {
		$this->_services = $services;
		return $this;
	}

	public function getServices() {
		if (!is_array($this->_services)) {
			$this->_services = $this->defaultServices();
		}
		return $this->_services;
	}

	/**
	 * @param array $baseAuthUrl base auth URL configuration.
	 */
	public function setBaseAuthUrl(array $baseAuthUrl) {
		$this->_baseAuthUrl = $baseAuthUrl;
	}

	/**
	 * @return array base auth URL configuration.
	 */
	public function getBaseAuthUrl() {
		if (!is_array($this->_baseAuthUrl)) {
			$this->_baseAuthUrl = $this->defaultBaseAuthUrl();
		}
		return $this->_baseAuthUrl;
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
		$assetsPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'authchoice';
		$assetsUrl = Yii::app()->getAssetManager()->publish($assetsPath);
		return $assetsUrl;
	}

	/**
	 * Returns default external services list.
	 * @return array external services list.
	 */
	protected function defaultServices() {
		$serviceCollection = Yii::app()->getComponent($this->serviceCollectionComponentName);
		return $serviceCollection->getServices();
	}

	/**
	 * Composes default base auth URL configuration.
	 * @return array base auth URL configuration.
	 */
	protected function defaultBaseAuthUrl() {
		$baseAuthUrl = array(
			Yii::app()->getController()->route
		);
		$params = $_GET;
		unset($params[$this->serviceIdGetParamName]);
		$baseAuthUrl = array_merge($baseAuthUrl, $params);
		return $baseAuthUrl;
	}

	/**
	 * Registers necessary client script files.
	 */
	protected function registerClientScript() {
		/* @var $clientScript CClientScript */
		$clientScript = Yii::app()->getComponent('clientScript');
		$assetsUrl = $this->getAssetsUrl();
		if ($this->cssFile !== false) {
			if ($this->cssFile === null) {
				$this->cssFile = $assetsUrl . '/authchoice.css';
			}
			$clientScript->registerCssFile($this->cssFile);
		}
		if ($this->popupMode) {
			$clientScript->registerCoreScript('jquery');
			$clientScript->registerScriptFile($assetsUrl . '/authchoice.js');
			$javaScript = "\$('#" . $this->getId() . "').authchoice();";
			$clientScript->registerScript(__CLASS__ . '#' . $this->getId(), $javaScript);
		}
	}

	/**
	 * Outputs external service auth link.
	 * @param QsAuthExternalService $service external auth service instance.
	 * @param string $text link text, if not set - default value will be generated.
	 * @param array $htmlOptions link HTML options.
	 */
	public function authLink($service, $text = null, array $htmlOptions = array()) {
		if ($text === null) {
			$text = CHtml::tag('span', array('class' => 'auth-icon ' . $service->getName()), '');
			$text .= CHtml::tag('span', array('class' => 'auth-title'), $service->getTitle());
		}
		if (!array_key_exists('class', $htmlOptions)) {
			$htmlOptions['class'] = 'auth-link ' . $service->getName();
		}
		if ($this->popupMode) {
			if (isset($service->popupWidth)) {
				$htmlOptions['data-popup-width'] = $service->popupWidth;
			}
			if (isset($service->popupHeight)) {
				$htmlOptions['data-popup-height'] = $service->popupHeight;
			}
		}
		echo CHtml::link($text, $this->composeExternalServiceUrl($service), $htmlOptions);
	}

	/**
	 * Composes external service auth URL.
	 * @param QsAuthExternalService $externalService external auth service instance.
	 * @return string auth URL.
	 */
	public function composeExternalServiceUrl($externalService) {
		$url = $this->getBaseAuthUrl();
		$url[$this->serviceIdGetParamName] = $externalService->getId();
		return CHtml::normalizeUrl($url);
	}

	/**
	 * Renders the main content, which includes all external services links.
	 */
	protected function renderMainContent() {
		echo CHtml::openTag('ul', array('class'=>'auth-services clear'));
		foreach ($this->getServices() as $externalService) {
			echo CHtml::openTag('li', array('class' => 'auth-service'));
			$this->authLink($externalService);
			echo CHtml::closeTag('li');
		}
		echo CHtml::closeTag('ul');
	}

	/**
	 * Initializes the widget.
	 */
	public function init() {
		$this->registerClientScript();
		$this->mainContainerHtmlOptions['id'] = $this->getId();
		echo CHtml::openTag('div', $this->mainContainerHtmlOptions);
	}

	/**
	 * Executes the widget.
	 * This method is called by {@link CBaseController::endWidget}.
	 */
	public function run() {
		if ($this->autoRender) {
			$this->renderMainContent();
		}
		echo CHtml::closeTag('div');
	}
}
