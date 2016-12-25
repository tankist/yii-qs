<?php
 
/**
 * Test case for the extension widget "qs.web.widgets.QsListView".
 * @see QsListView
 */
class QsListViewTest extends CTestCase {
	/**
	 * @var CAssetManager asset manager application component backup.
	 */
	protected static $_assetManagerBackup = null;
	/**
	 * @var CHttpRequest request application component backup.
	 */
	protected static $_httpRequestBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.web.widgets.QsListView');

		self::$_assetManagerBackup = clone Yii::app()->getComponent('assetManager');

		self::$_httpRequestBackup = Yii::app()->getComponent('request');
		$testHttpRequest = Yii::createComponent(array('class'=>'QsTestHttpRequest'));
		Yii::app()->setComponent('request', $testHttpRequest);
	}

	public static function tearDownAfterClass() {
		if (is_object(self::$_assetManagerBackup)) {
			Yii::app()->setComponent('assetManager', self::$_assetManagerBackup);
		}
		Yii::app()->clientScript->reset();
	}

	public function setUp() {
		$assetsFilePath = self::getTestAssetFilePath();
		if (!file_exists($assetsFilePath)) {
			mkdir($assetsFilePath, 0777, true);
		}
		Yii::app()->getComponent('assetManager')->setBasePath($assetsFilePath);
	}

	public function tearDown() {
		$viewFilePath = self::getTestFilePath();
		if (file_exists($viewFilePath)) {
			exec("rm -rf {$viewFilePath}");
		}
	}

	/**
	 * Returns the test file path Yii alias.
	 * @return string test file path alias.
	 */
	public static function getTestFilePathAlias() {
		return 'application.runtime.'.__CLASS__.getmypid();
	}

	/**
	 * Returns the test files path.
	 * @return string test file path.
	 */
	public static function getTestFilePath() {
		$filePath = Yii::getPathOfAlias(self::getTestFilePathAlias());
		return $filePath;
	}

	/**
	 * Returns the test view file path.
	 * @return string test view file path.
	 */
	public static function getTestViewFilePath() {
		$filePath = self::getTestFilePath().DIRECTORY_SEPARATOR.'views';
		return $filePath;
	}

	/**
	 * Returns the test assets file path.
	 * @return string test assets file path.
	 */
	public static function getTestAssetFilePath() {
		$filePath = self::getTestFilePath().DIRECTORY_SEPARATOR.'assets';
		return $filePath;
	}

	/**
	 * Creates test controller instance.
	 * @return CController test controller instance.
	 */
	protected function createTestController() {
		$controller = new CController('test');

		$action = $this->getMock('CAction', array('run'), array($controller, 'test'));
		$controller->action = $action;

		Yii::app()->controller = $controller;
		return $controller;
	}

	/**
	 * Creates test data provider instance.
	 * @param integer $itemsCount count of test items.
	 * @return CArrayDataProvider test data provider.
	 */
	protected function createTestDataProvider($itemsCount=20) {
		$rawData = array();
		for ($i=1; $i<=$itemsCount; $i++) {
			$item = array(
				'name' => 'test_item_name_'.$i,
				'index' => $i,
			);
			$rawData[] = $item;
		}
		$dataProvider = new CArrayDataProvider($rawData, array('keyField' => 'index'));
		return $dataProvider;
	}

	/**
	 * Creates new view file.
	 * @param string $viewName view name
	 * @param string $fileContent view file content
	 * @return string view file alias.
	 */
	protected function createViewFile($viewName,$fileContent) {
		$filePath = self::getTestViewFilePath();
		if (!file_exists($filePath)) {
			mkdir($filePath, 0777, true);
		}
		$fileName = $filePath.DIRECTORY_SEPARATOR.$viewName.'.php';
		file_put_contents($fileName, $fileContent);

		$fileAlias = self::getTestFilePathAlias().'.views.'.$viewName;
		return $fileAlias;
	}

	// Tests:

	public function testRenderContentView() {
		$controller = $this->createTestController();

		$testContentViewName = 'test_content';
		$testWidgetContent = 'Test widget content';

		$contentViewName = $this->createViewFile($testContentViewName, $testWidgetContent);
		$itemViewName = $this->createViewFile('test_item', 'test item');

		$widgetOptions = array(
			'dataProvider' => $this->createTestDataProvider(),
			'contentView' => $contentViewName,
			'itemView' => $itemViewName,
		);

		$this->expectOutputRegex("/{$testWidgetContent}/i");
		$controller->widget('QsListView', $widgetOptions);
	}

	public function testRenderSummaryView() {
		$controller = $this->createTestController();

		$testSummaryViewName = 'test_sorter';
		$testWidgetSummaryContent = 'Test Widget Summary';

		$sorterViewName = $this->createViewFile($testSummaryViewName, $testWidgetSummaryContent);
		$itemViewName = $this->createViewFile('test_item', 'test item');

		$widgetOptions = array(
			'dataProvider' => $this->createTestDataProvider(),
			'summaryView' => $sorterViewName,
			'itemView' => $itemViewName,
		);

		$this->expectOutputRegex("/{$testWidgetSummaryContent}/i");
		$controller->widget('QsListView', $widgetOptions);
	}

	public function testRenderSorterView() {
		$controller = $this->createTestController();

		$testSorterViewName = 'test_sorter';
		$testWidgetSorterContent = 'Test Widget Sorter';

		$sorterViewName = $this->createViewFile($testSorterViewName, $testWidgetSorterContent);
		$itemViewName = $this->createViewFile('test_item', 'test item');

		$widgetOptions = array(
			'dataProvider' => $this->createTestDataProvider(),
			'enableSorting' => true,
			'sortableAttributes' => array('name'),
			'sorterView' => $sorterViewName,
			'itemView' => $itemViewName,
		);

		$this->expectOutputRegex("/{$testWidgetSorterContent}/i");
		$controller->widget('QsListView', $widgetOptions);
	}

	public function testRenderEmptyText() {
		$controller = $this->createTestController();

		$testEmptyTextViewName = 'test_empty_text';
		$testEmptyTextViewContent = 'Test Empty Text';

		$emptyTextViewName = $this->createViewFile($testEmptyTextViewName, $testEmptyTextViewContent);
		$itemViewName = $this->createViewFile('test_item', 'test item');

		$widgetOptions = array(
			'dataProvider' => $this->createTestDataProvider(0),
			'emptyTextView' => $emptyTextViewName,
			'itemView' => $itemViewName,
		);

		$this->expectOutputRegex("/{$testEmptyTextViewContent}/i");
		$controller->widget('QsListView', $widgetOptions);
	}
}
