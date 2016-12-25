<?php
 
/**
 * Test case for the extension widget "qs.web.widgets.QsDropDownBox".
 * @see QsDropDownBox
 */
class QsDropDownBoxTest extends CTestCase {
	/**
	 * @var CAssetManager asset manager application component backup.
	 */
	protected static $_assetManagerBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.web.widgets.QsDropDownBox');

		self::$_assetManagerBackup = clone Yii::app()->getComponent('assetManager');
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

	// Tests:

	public function testSetGet() {
		$widget = new QsDropDownBox();

		$testContainerId = 'test_container_id';
		$this->assertTrue($widget->setContainerId($testContainerId), 'Unable to set container id!');
		$this->assertEquals($testContainerId, $widget->getContainerId(), 'Unable to set container id correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultContainerId() {
		$widget = new QsDropDownBox();

		$testWidgetId = 'test_widget_id';
		$widget->setId($testWidgetId);

		$defaultContainerId = $widget->getContainerId();
		$this->assertContains($testWidgetId, $defaultContainerId, 'Default container id does not contain widget id!');
		$this->assertNotEquals($testWidgetId, $defaultContainerId, 'Default container id is the same as widget id!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testRender() {
		$controller = $this->createTestController();

		$testLabel = 'test_widget_label';
		$testContent = 'test_widget_content';

		$widgetOptions = array(
			'label' => $testLabel,
			'content' => $testContent,
		);

		$this->expectOutputRegex("/{$testLabel}(.*){$testContent}/i");
		$controller->widget('QsDropDownBox', $widgetOptions);
	}

	/**
	 * @depends testRender
	 */
	public function testRenderItems() {
		$controller = $this->createTestController();

		$testLabel = 'test_widget_label';
		$testItems = array();
		$expectOutputRegexParts = array();
		for ($i=1; $i<=3; $i++) {
			$testItem = array(
				'label' => 'item_label_'.$i,
				'url' => 'http://item.url/'.$i,
			);
			$testItems[] = $testItem;

			$expectOutputRegexParts[] = "<a href=\"{$testItem['url']}\">{$testItem['label']}</a>";
		}

		$widgetOptions = array(
			'label' => $testLabel,
			'items' => $testItems,
		);

		$this->expectOutputRegex('%'.implode('(.*)', $expectOutputRegexParts).'%is');
		$controller->widget('QsDropDownBox', $widgetOptions);
	}
}
