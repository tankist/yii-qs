<?php
/**
 * QsGridView class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('zii.widgets.grid.CGridView');

/**
 * QsGridView is an extension of the {@link CGridView} widget.
 * This widget adds the record group processing form.
 * In order to provide the group record selection you may use {@link CCheckBoxColumn} grid column.
 *
 * For example:
 * <code>
 * $this->widget('qs.web.widgets.grid.QsGridView', array(
 *     'id' => 'record-grid',
 *       'dataProvider' => $model->dataProviderAdmin(),
 *       'groupProcesses' => array(
 *         'delete' => 'Delete'
 *     ),
 *     'columns' => array(
 *         array(
 *             'class' => 'CCheckBoxColumn',
 *             'id' => 'row_keys',
 *             'selectableRows' => 2,
 *         ),
 *         array(
 *             'class' => 'CButtonColumn'
 *         ),
 *         'id',
 *         'name',
 *         'date',
 *     ),
 * ));
 * </code>
 * 
 * @see CCheckBoxColumn
 * @see QsActionAdminGroupProcess
 *
 * @property mixed $formAction public alias of {@link _formAction}.
 * @property array $groupProcesses public alias of {@link _groupProcesses}.
 * @property string $additionalCssFile public alias of {@link _additionalCssFile}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets.grid
 */
class QsGridView extends CGridView {
	/**
	 * @var string the template to be used to control the layout of various sections in the view.
	 * These tokens are recognized: {summary}, {items} and {pager}. They will be replaced with the
	 * summary text, the items, and the pager.
	 */
	public $template = "{formStart}\n{summary}\n{items}\n{formContent}\n{pager}\n{formEnd}";
	/**
	 * @var mixed group process form action. This value will be used to generate a valid URL.
	 * By default URL for the action 'groupprocess' of the current controller will be created.
	 * @see CHtml::normalizeUrl()
	 */
	protected $_formAction = '';
	/**
	 * @var string name of the form input, which determines particular group process name.
	 */
	public $groupProcessInputName = 'group_process';
	/**
	 * @var array set of group actions in format: actionName => actionTitle.
	 * For example:
	 * <code>
	 * array(
	 *     'delete' => 'Delete',
	 *     'export' => 'Export to CSV',
	 * );
	 * </code>
	 */
	protected $_groupProcesses = array();
	/**
	 * @var string the URL of the CSS file used by this group action form.
	 * If {@link cssFile} is set, no additional CSS will be used.
	 */
	protected $_additionalCssFile;

	// Set / Get :

	public function setFormAction($formAction) {
		$this->_formAction = $formAction;
		return true;
	}

	public function getFormAction() {
		if (empty($this->_formAction)) {
			$this->initFormAction();
		}
		return $this->_formAction;
	}

	public function setGroupProcesses(array $groupProcesses) {
		$this->_groupProcesses = $groupProcesses;
		return true;
	}

	public function getGroupProcesses() {
		return $this->_groupProcesses;
	}

	/**
	 * Initializes {@link formAction} value.
	 * @return boolean success.
	 */
	protected function initFormAction() {
		$formAction = is_array($_GET) ? $_GET : array();
		$route = Yii::app()->controller->id.'/groupprocess';
		array_unshift($formAction, $route);
		$this->_formAction = $formAction;
		return true;
	}

	/**
	 * Initializes the grid view.
	 * This method will initialize required property values and instantiate {@link columns} objects.
	 */
	public function init() {
		if (!empty($this->cssFile) || $this->cssFile===false) {
			$this->_additionalCssFile = false;
		}
		parent::init();

	}

	/**
	 * Renders the group actions form open tag.
	 */
	protected function renderFormStart() {
		if (!empty($this->_groupProcesses)) {
			echo CHtml::beginForm($this->getFormAction(), 'post', array('enctype'=>'multipart/form-data'));
		}
	}

	/**
	 * Renders the group actions form close tag.
	 */
	protected function renderFormEnd() {
		if (!empty($this->_groupProcesses)) {
			echo CHtml::endForm();
		}
	}

	/**
	 * Renders the group action form block.
	 */
	protected function renderFormContent() {
		if (!empty($this->_groupProcesses)) {
			if ($this->_additionalCssFile !== false) {
				$this->publishGroupActionAssets();
			}
			echo '<div class="group-process">';
			$this->renderGroupActionFormContent();
			echo('</div>');
		}
	}

	/**
	 * Publishes additional assets, which is used to for the group action form.
	 * @return boolean success.
	 */
	protected function publishGroupActionAssets() {
		$additionalAssetsPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'groupaction';
		$additionalAssetsUrl = Yii::app()->getAssetManager()->publish($additionalAssetsPath);
		$additionalCssFileUrl = $additionalAssetsUrl.'/group_action_styles.css';
		Yii::app()->getClientScript()->registerCssFile($additionalCssFileUrl);
		return true;
	}

	/**
	 * Renders the group action form main content.
	 */
	protected function renderGroupActionFormContent() {
		echo '<ul>';

		echo '<li class="select-all-arrow"></li>';

		echo '<li>';
		echo 'With selected:';
		echo '</li>';

		echo '<li>';
		$dropDownData = array(
			'' => '',
		);
		$dropDownData = array_merge($dropDownData, $this->_groupProcesses);
		echo CHtml::dropDownList($this->groupProcessInputName, null, $dropDownData);
		echo '</li>';

		echo '<li>';
		$submitHtmlOptions = array(
			'class' => 'button',
			'onclick' => "return confirm('Are you sure you wish to perform this action?');"
		);
		echo CHtml::submitButton('Perform', $submitHtmlOptions);
		echo '</li>';

		echo '</ul>';
	}
}
