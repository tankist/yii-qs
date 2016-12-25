<?php
/**
 * QsListView class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('zii.widgets.CListView');

/**
 * QsListView widget is an extension on the standard Yii widget {@link CListView},
 * which allows to render sorter, summary and entire content based on the view files.
 * Widget usage example:
 * <code>
 * $this->widget('qs.web.widgets.QsListView', array(
 *     'dataProvider' => $model->search(),
 *     'contentView' => '_list',
 *     'itemView' => '_row',
 *     'summaryView' => '_summary',
 *     'sorterView' => '_sorter',
 *     'sortableAttributes' => array(
 *         'name',
 *         'create_date' => 'Post Date',
 *     )
 * ));
 * </code>
 * 
 * List view file example:
 * <code>
 * <?php echo $summary; ?>
 * <?php echo $sorter; ?>
 * <?php echo $items; ?>
 * <?php echo $pager; ?>
 * </code>
 * 
 * Sorter view file example:
 * <code>
 * <div class="<?php echo $cssClass; ?>">
 * <?php echo $header; ?>
 * <?php foreach ($links as $link) { ?>
 *     <li><?php echo $link; ?></li>
 * <?php } ?>
 * <?php echo $footer; ?>
 * </div>
 * </code>
 * 
 * Summary view file example:
 * <code>
 * <div class="<?php echo $cssClass; ?>">
 *     <?php if ($count <= 0) { ?>
 *     No result(s) to display.
 *     <?php } else { ?>
 *     Displaying <?php if ($start>0) { ?><?php echo $start; ?>-<?php echo $end; ?> of <?php } ?><?php echo $count; ?> result(s).
 *     <?php } ?>
 * </div>
 * </code>
 * 
 * Set specific view to null in order to use default {@link CListView} functionality for this part.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.widgets
 */
class QsListView extends CListView {
	/**
	 * @var array the configuration for the pager.
	 * By defaults uses {@link QsLinkPager}.
	 * @see enablePagination
	 */
	public $pager = array(
		'class' => 'qs.web.widgets.QsLinkPager'
	);
	/**
	 * @string name of the view, which should be used to render the main list content.
	 */
	public $contentView = null;
	/**
	 * @var string name of the view, which should be used to render list summary section.
	 */
	public $summaryView = 'application.views.index.pagers.summary';
	/**
	 * @var string name of the view, which should be used to render sorter section.
	 */
	public $sorterView = null;
	/**
	 * @var string name of the view, which should be used to render empty message, when there is no data.
	 */
	public $emptyTextView = null;

	/**
	 * Renders the main content of the view.
	 * @see CBaseListView::renderContent()
	 */
	public function renderContent() {
		if (empty($this->contentView)) {
			parent::renderContent();
		} else {
			$this->renderContentView();
		}
	}

	/**
	 * Saves render result of the specified section into the string variable.
	 * @param string $sectionName name of the section.
	 * @return string section render output.
	 */
	public function renderSectionFetch($sectionName) {
		$matchesMockup = array($sectionName, $sectionName);
		ob_start();
		$html = $this->renderSection($matchesMockup);
		ob_end_flush();
		return $html;
	}

	/**
	 * Renders the main content of the list, using {@link contentView}.
	 * @return boolean success.
	 */
	protected function renderContentView() {
		$data = array(
			'widget' => $this,
			'summary' => $this->renderSectionFetch('summary'),
			'items' => $this->renderSectionFetch('items'),
			'pager' => $this->renderSectionFetch('pager'),
			'sorter' => $this->renderSectionFetch('sorter')
		);
		return $this->renderByOwner($this->contentView, $data);
	}

	/**
	 * Renders the given view, using the widget owner as renderer,
	 * so view name will be resolved by widget owner.
	 * @param string $view view name.
	 * @param array $data view data.
	 * @return boolean success.
	 */
	protected function renderByOwner($view, $data) {
		$owner = $this->getOwner();
		$renderMethod = $owner instanceof CController ? 'renderPartial' : 'render';
		return $owner->$renderMethod($view, $data);
	}

	/**
	 * Renders the summary text.
	 */
	public function renderSummary() {
		if (empty($this->summaryView)) {
			parent::renderSummary();
			return;
		}

		if (($count=$this->dataProvider->getItemCount())<=0) {
			return;
		}

		$data = array(
			'widget' => $this,
			'cssClass' => $this->summaryCssClass,
		);

		if ($this->enablePagination) {
			$pagination = $this->dataProvider->getPagination();
			$total = $this->dataProvider->getTotalItemCount();
			$start = $pagination->currentPage*$pagination->pageSize+1;
			$end = $start + $count - 1;

			if ($end>$total) {
				$end = $total;
				$start = $end-$count+1;
			}

			$additionalData = array(
				'start' => $start,
				'end' => $end,
				'count' => $total,
				'page' => $pagination->currentPage+1,
				'pages' => $pagination->pageCount,
			);
			$data = array_merge($data, $additionalData);
		} else {
			$data['count'] = $count;
		}
		$this->renderByOwner($this->summaryView, $data);
	}

	/**
	 * Renders the sorter.
	 */
	public function renderSorter() {
		if (empty($this->sorterView)) {
			parent::renderSorter();
			return;
		}

		if ($this->dataProvider->getItemCount()<=0 || !$this->enableSorting || empty($this->sortableAttributes)) {
			return;
		}

		$sort = $this->dataProvider->getSort();

		$links = array();
		foreach ($this->sortableAttributes as $name => $label) {
			if (is_integer($name)) {
				$links[] = $sort->link($label);
			} else {
				$links[] = $sort->link($name, $label);
			}
		}

		$data = array(
			'widget' => $this,
			'sort' => $sort,
			'cssClass' => $this->sorterCssClass,
			'header' => $this->sorterHeader===null ? Yii::t('zii','Sort by: ') : $this->sorterHeader,
			'footer' => $this->sorterFooter,
			'links' => $links,
		);

		$this->renderByOwner($this->sorterView, $data);
	}

	/**
	 * Renders the empty message when there is no data.
	 */
	public function renderEmptyText() {
		if (empty($this->emptyTextView)) {
			parent::renderEmptyText();
		} else {
			$emptyText = $this->emptyText===null ? Yii::t('zii','No results found.') : $this->emptyText;
			$data = array(
				'emptyText' => $emptyText
			);
			$this->renderByOwner($this->emptyTextView, $data);
		}
	}
}