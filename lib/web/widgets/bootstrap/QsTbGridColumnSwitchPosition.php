<?php
/**
 * QsTbGridColumnSwitchPosition class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.web.widgets.grid.QsGridColumnSwitchPosition', true);

/**
 * QsTbGridColumnSwitchPosition represents a grid view column that renders row position switches
 * in Twitter Bootstrap style.
 * This widget requires Twitter Bootstrap extension added to the project.
 *
 * @see QsGridColumnSwitchPosition
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets.grid
 */
class QsTbGridColumnSwitchPosition extends QsGridColumnSwitchPosition {
	/**
	 * Composes switch position link text.
	 * @param string $direction movement direction name.
	 * @param integer $row the row number (zero-based).
	 * @param mixed $data the data associated with the row.
	 * @return string link text.
	 */
	protected function composeSwitchPositionLinkText($direction, $row, $data) {
		$directionIconMap = array(
			'first' => 'chevron-up',
			'prev' => 'arrow-up',
			'next' => 'arrow-down',
			'last' => 'chevron-down',
		);
		$iconName = $directionIconMap[$direction];
		return CHtml::tag('i', array('class' => 'icon-' . $iconName), '');
	}

	/**
	 * Renders the filter cell.
	 */
	public function renderFilterCell() {
		echo '<td><div class="filter-container">';
		$this->renderFilterCellContent();
		echo '</div></td>';
	}
}