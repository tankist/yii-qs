<?php
/**
 * QsEmailPatternComposerBase class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsEmailPatternComposerBase is the base class for all email composers.
 * This class compose different parts of the message, filling its pattern with the real data.
 * This class should be extended in order to specify explicit method of composing.
 *
 * @property QsEmailPattern $emailPattern public alias of {@link _emailPattern}.
 * @property array $data public alias of {@link _data}.
 * @property array $defaultData public alias of {@link _defaultData}.
 * @property string $viewPath public alias of {@link _viewPath}.
 * @property string $layout public alias of {@link _layout}.
 * @property mixed $bodyTextAutoFillType public alias of {@link _bodyTextAutoFillType}.
 * @property string $bodyTextDefault public alias of {@link _bodyTextDefault}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.email.composers
 */
abstract class QsEmailPatternComposerBase extends CBaseController {
	const BodyTextAutoFillFromHtml = 'html';
	const BodyTextAutoFillFromDefault = 'default';
	const BodyTextAutoFillNone = false;

	/**
	 * @var QsEmailPattern email pattern instance for the internal usage.
	 */
	protected $_emailPattern = null;
	/**
	 * @var array data to be parsed into email pattern.
	 */
	protected $_data = array();
	/**
	 * @var array default data, which should be parsed into every email pattern.
	 * Default data will be filled with all values from <code>Yii::app()->params</code>
	 * and URL references: $homeUrl, $homeUrlHttp, $homeUrlHttps.
	 * @see initDefaultData
	 */
	protected $_defaultData = array();
	/**
	 * @var string path to the email view files.
	 * By default will be set to <code>Yii::app()->getBasePath().'/views/emails'</code>
	 */
	protected $_viewPath = '';
	/**
	 * @var string name of the email layout view.
	 */
	protected $_layout = 'layouts/layout';
	/**
	 * @var mixed determines source to create email body in text format (alternative of HTML).
	 * 'html' - use HTML body as base for the text one
	 * 'default' - use default text as text body
	 * false - not text body will be created at all
	 */
	protected $_bodyTextAutoFillType = self::BodyTextAutoFillFromHtml;
	/**
	 * @var string default text of the body in text format.
	 * @see bodyTextAutoFillType
	 */
	protected $_bodyTextDefault = 'You should use email client, which supports HTML to view this message.';

	/**
	 * Class constructor.
	 * Sets up default {@link viewPath}.
	 */
	public function __construct() {
		$this->setViewPath(Yii::app()->getBasePath() . '/views/emails');
	}

	// Property Access:
	
	public function setEmailPattern(QsEmailPattern $emailPattern) {
		$this->_emailPattern = $emailPattern;
		return true;
	}

	public function getEmailPattern() {
		return $this->_emailPattern;
	}

	public function setData(array $data) {
		$this->_data = $data;
		return true;
	}

	public function getData() {
		return $this->_data;
	}

	public function setDefaultData(array $defaultData) {
		$this->_defaultData = $defaultData;
		return true;
	}

	public function getDefaultData() {
		if (empty($this->_defaultData)) {
			$this->initDefaultData();
		}
		return $this->_defaultData;
	}

	public function setViewPath($viewPath) {
		if (!is_string($viewPath)) {
			return false;
		}
		$this->_viewPath = $viewPath;
		return true;
	}

	public function getViewPath() {
		return $this->_viewPath;
	}

	public function setLayout($layout) {
		if (!is_string($layout)) {
			return false;
		}
		$this->_layout = $layout;
		return true;
	}

	public function getLayout() {
		return $this->_layout;
	}

	public function setBodyTextAutoFillType($bodyTextAutoFillType) {
		$this->_bodyTextAutoFillType = $bodyTextAutoFillType;
		return true;
	}

	public function getBodyTextAutoFillType() {
		return $this->_bodyTextAutoFillType;
	}

	public function setBodyTextDefault($bodyTextDefault) {
		if (!is_string($bodyTextDefault)) {
			return false;
		}
		$this->_bodyTextDefault = $bodyTextDefault;
		return true;
	}

	public function getBodyTextDefault() {
		return $this->_bodyTextDefault;
	}

	/**
	 * Initializes {@link defaultData}.
	 * Default data will be filled with all values from <code>Yii::app()->params</code>
	 * and URL references: $homeUrl, $homeUrlHttp, $homeUrlHttps.
	 * @return boolean success.
	 */
	protected function initDefaultData() {
		$homeUrl = Yii::app()->createAbsoluteUrl('/', array(), 'http');
		$homeUrlTail = basename($homeUrl);
		if (strpos($homeUrlTail, '.php')!==false) {
			$homeUrl = dirname($homeUrl);
		}
		$homeUrlHttp = $homeUrl;
		$homeUrlHttps = str_replace('http://', 'https://', $homeUrl);
		$defaultData = array(
			'app' => Yii::app(),
			'homeUrl' => $homeUrl,
			'homeUrlHttp' => $homeUrlHttp,
			'homeUrlHttps' => $homeUrlHttps,
		);
		$defaultData = array_merge($defaultData, Yii::app()->params->toArray());
		$this->_defaultData = $defaultData;
		return true;
	}

	/**
	 * Translates view name into view file name.
	 * @param string $viewName - name of view.
	 * @return string full view file name.
	 */
	public function getViewFile($viewName) {
		$fileExtension = '.php';
		$fileName = $this->getViewPath() . DIRECTORY_SEPARATOR . $viewName . $fileExtension;
		return $fileName;
	}

	/**
	 * Evaluates string as view file content.
	 * @param string $_viewStr_ - code to be evaluated.
	 * @param array $_data_ - list of parameters to be parsed.
	 * @return string result of evaluation.
	 */
	protected function evalRender($_viewStr_, array $_data_=null) {
		$_evalStr_ = '?>' . $_viewStr_;
		if (is_array($_data_)) {
			extract($_data_, EXTR_PREFIX_SAME, 'data');
		}
		ob_start();
		ob_implicit_flush(false);
		eval($_evalStr_);
		return ob_get_clean();
	}

	/**
	 * Wraps content with layout code.
	 * @param string $content - content to be wrapped.
	 * @return string content wrapped by layout.
	 */
	protected function wrapLayout($content) {
		$layout = $this->getLayout();
		if (empty($layout)) {
			return $content;
		}
		$data = $this->getData();
		$data['content'] = $content;
		$wrappedContent = $this->renderInternal($this->getViewFile($layout), $data, true);
		return $wrappedContent;
	}

	/**
	 * Composes email pattern: transfers internal simple markup into valid PHP code.
	 * @param QsEmailPattern $emailPattern - instance of the {@link QsEmailPattern}.
	 * @param array $data - list of params, which should be parsed.
	 * @return QsEmailPattern - composed email pattern object.
	 */
	public function compose(QsEmailPattern $emailPattern, $data=null) {
		$this->setEmailPattern(clone $emailPattern);

		if (!is_array($data)) {
			$data = array();
		}
		$data = array_merge($this->getDefaultData(), $data);

		$this->setData($data);

		$this->beforeCompose();

		$this->composeFrom();
		$this->composeSubject();
		$this->composeBody();

		$this->afterCompose();

		return $this->getEmailPattern();
	}

	/**
	 * Composes 'from' part of the email pattern.
	 * @return boolean success.
	 */
	protected function composeFrom() {
		$fromName = $this->_emailPattern->getFromName();
		if (!empty($fromName)) {
			$this->composeFromName();
		}
		$this->composeFromEmail();
		return true;
	}

	/**
	 * Composes body of the email pattern.
	 * HTML and text format of the body will added as 'multipart-alternative'.
	 * @see bodyTextAutoFillType.
	 * @return boolean success.
	 */
	protected function composeBody() {
		// HTML:
		$this->composeBodyHtml();
		$bodyHtml = $this->wrapLayout($this->_emailPattern->getBodyHtml());
		$this->_emailPattern->setBodyHtml($bodyHtml);

		// Plain Text:
		$bodyText = $this->_emailPattern->getBodyText();
		if (empty($bodyText)) {
			switch ($this->_bodyTextAutoFillType) {
				case self::BodyTextAutoFillFromHtml: {
					$bodyText = strip_tags($this->_emailPattern->getBodyHtml());
					$this->_emailPattern->setBodyText($bodyText);
					break;
				}
				case self::BodyTextAutoFillFromDefault: {
					$this->_emailPattern->setBodyText($this->getBodyTextDefault());
					break;
				}
			}
		} else {
			$this->composeBodyText();
		}

		return true;
	}

	/**
	 * Composes bodyHtml part of the email pattern.
	 * This method is abstract and should be overridden depending the composing method.
	 * @return boolean success.
	 */
	abstract protected function composeBodyHtml();

	/**
	 * Composes bodyText part of the email pattern.
	 * This method is abstract and should be overridden depending the composing method.
	 * @return boolean success.
	 */
	abstract protected function composeBodyText();

	/**
	 * Composes subject part of the email pattern.
	 * This method is abstract and should be overridden depending the composing method.
	 * @return boolean success.
	 */
	abstract protected function composeSubject();

	/**
	 * Composes fromEmail part of the email pattern.
	 * This method is abstract and should be overridden depending the composing method.
	 * @return boolean success.
	 */
	abstract protected function composeFromEmail();

	/**
	 * Composes fromName part of the email pattern.
	 * This method is abstract and should be overridden depending the composing method.
	 * @return boolean success.
	 */
	abstract protected function composeFromName();

	/**
	 * This method is invoked before email pattern composing.
	 * The default implementation raises the {@link onBeforeCompose} event.
	 * You may override this method to do pre processing before email pattern composing.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function beforeCompose() {
		if ($this->hasEventHandler('onBeforeCompose')) {
			$event = new CEvent($this);
			$this->onBeforeCompose($event);
		}
	}

	/**
	 * This method is invoked after email pattern composing.
	 * The default implementation raises the {@link onAfterCompose} event.
	 * You may override this method to do post processing after email pattern composing.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	protected function afterCompose() {
		if ($this->hasEventHandler('onAfterCompose')) {
			$event = new CEvent($this);
			$this->onAfterCompose($event);
		}
	}

	/**
	 * This event is raised before email pattern composing.
	 * In order to access email pattern use
	 * <code>$event->sender->emailPattern</code>
	 * @param CEvent $event the event parameter
	 */
	public function onBeforeCompose($event) {
		$this->raiseEvent('onBeforeCompose', $event);
	}

	/**
	 * This event is raised before email pattern composing.
	 * In order to access email pattern use
	 * <code>$event->sender->emailPattern</code>
	 * @param CEvent $event the event parameter
	 */
	public function onAfterCompose($event) {
		$this->raiseEvent('onAfterCompose', $event);
	}
}