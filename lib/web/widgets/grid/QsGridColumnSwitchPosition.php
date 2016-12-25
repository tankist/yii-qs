<?php
/**
 * QsGridColumnSwitchPosition class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('zii.widgets.grid.CDataColumn');

/**
 * QsGridColumnSwitchPosition represents a grid view column that renders row position switches.
 * This column inherits sorting and filtering functionality of the {@link CDataColumn}.
 * Use this column for the attribute, which represents the current row position.
 * You can customize the display of the column using {@link template} property.
 * Example:
 * <code>
 * $this->widget('zii.widgets.grid.CGridView', array(
 *     ...
 *     'columns' => array(
 *         array(
 *             'class' => 'qs.web.widgets.grid.QsGridColumnSwitchPosition',
 *             'name' => 'position'
 *         ),
 *     ),
 * ));
 * </code>
 *
 * @see CGridView
 * @see CDataColumn
 * @see QsActionAdminMove
 *
 * @property string $assetsUrl public alias of {@link _assetsUrl}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets.grid
 */
class QsGridColumnSwitchPosition extends CDataColumn {
	/**
	 * @var string the template that is used to render the content in each cell.
	 * These default tokens are recognized: {first}, {prev}, {next}, {last} and {value}.
	 */
	public $template = '{first}&nbsp;{prev}&nbsp;{value}&nbsp;{next}&nbsp;{last}';
	/**
	 * @var string URL for the associated assets.
	 */
	protected $_assetsUrl = '';
	/**
	 * @var string a PHP expression that is evaluated for every "first" link and whose result is used
	 * as the URL for the "first" link. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object.
	 */
	public $firstUrl = 'Yii::app()->controller->createUrl("move", array_merge(array("to"=>"first", "id"=>$data->id), $_GET));';
	/**
	 * @var string a PHP expression that is evaluated for every "previous" link and whose result is used
	 * as the URL for the "previous" link. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object.
	 */
	public $prevUrl = 'Yii::app()->controller->createUrl("move", array_merge(array("to"=>"prev", "id"=>$data->id), $_GET));';
	/**
	 * @var string a PHP expression that is evaluated for every "next" link and whose result is used
	 * as the URL for the "next" link. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object.
	 */
	public $nextUrl = 'Yii::app()->controller->createUrl("move", array_merge(array("to"=>"next", "id"=>$data->id), $_GET));';
	/**
	 * @var string a PHP expression that is evaluated for every "last" link and whose result is used
	 * as the URL for the "last" link. In this expression, the variable
	 * <code>$row</code> the row number (zero-based); <code>$data</code> the data model for the row;
	 * and <code>$this</code> the column object.
	 */
	public $lastUrl = 'Yii::app()->controller->createUrl("move", array_merge(array("to"=>"last", "id"=>$data->id), $_GET));';

	/**
	 * @return string associated assets URL.
	 */
	public function getAssetsUrl() {
		if (empty($this->_assetsUrl)) {
			$this->_assetsUrl = $this->publishSwitchPositionAssets();
		}
		return $this->_assetsUrl;
	}

	/**
	 * Initializes the column.
	 */
	public function init() {
		parent::init();
		if (!array_key_exists('style', $this->htmlOptions) && !array_key_exists('class', $this->htmlOptions)) {
			$this->htmlOptions['style'] = 'text-align:center';
		}
	}

	/**
	 * Publishes additional assets, which is used to for the group action form.
	 * @return string assets URL.
	 */
	protected function publishSwitchPositionAssets() {
		$assetsPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'switchposition';
		$assetsUrl = Yii::app()->getAssetManager()->publish($assetsPath);
		return $assetsUrl;
	}

	/**
	 * Renders the switch position link.
	 * @param string $direction movement direction name.
	 * @param integer $row the row number (zero-based).
	 * @param mixed $data the data associated with the row.
	 */
	protected function renderSwitchPositionLink($direction, $row, $data) {
		$text = $this->composeSwitchPositionLinkText($direction, $row, $data);
		$directionUrlFieldName = $direction . 'Url';
		$rawUrl = $this->$directionUrlFieldName;
		$url = $rawUrl !== null ? $this->evaluateExpression($rawUrl, array('data'=>$data, 'row'=>$row)) : '#';
		$link = CHtml::link($text, $url, array('title'=>$direction));
		echo $link;
	}

	/**
	 * Composes switch position link text.
	 * @param string $direction movement direction name.
	 * @param integer $row the row number (zero-based).
	 * @param mixed $data the data associated with the row.
	 * @return string link text.
	 */
	protected function composeSwitchPositionLinkText($direction, $row, $data) {
		$assetsUrl = $this->getAssetsUrl();
		return CHtml::image($assetsUrl . "/{$direction}.png", $direction);
	}

	/**
	 * Renders the data cell value.
	 * This method evaluates {@link value} or {@link name} and renders the result.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderValue($row, $data) {
		parent::renderDataCellContent($row, $data);
	}

	/**
	 * Renders the data cell content.
	 * This method evaluates {@link value} or {@link name} and renders the result.
	 * @param integer $row the row number (zero-based)
	 * @param mixed $data the data associated with the row
	 */
	protected function renderDataCellContent($row, $data) {
		$tr = array();
		ob_start();

		$this->renderValue($row, $data);
		$tr['{value}'] = ob_get_contents();
		ob_clean();

		$directions = array(
			'first',
			'prev',
			'next',
			'last',
		);
		foreach ($directions as $direction) {
			$this->renderSwitchPositionLink($direction, $row, $data);
			$tr['{' . $direction . '}'] = ob_get_contents();
			ob_clean();
		}
		ob_end_clean();
		echo strtr($this->template, $tr);
	}
}
