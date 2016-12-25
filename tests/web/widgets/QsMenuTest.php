<?php

/**
 * Test case for the extension widget "qs.web.widgets.QsMenu".
 * @see QsMenu
 */
class QsMenuTest extends CTestCase {

	public static function setUpBeforeClass() {
		Yii::import('qs.web.widgets.QsMenu');
	}

	/**
	 * Creates test items list.
	 * @param int $testItemsCount the test items count.
	 * @param string $itemUrlPrefix item URL prefix.
	 * @return array list of test items.
	 */
	protected function createTestItems($testItemsCount=2, $itemUrlPrefix='menu_item') {
		$testItems = array();
		for ($i=1; $i<=$testItemsCount; $i++) {
			$testItems[] = array('label'=>'MenuItem'.$i, 'url'=>array("{$itemUrlPrefix}_{$i}/"));
		}
		return $testItems;
	}

	/**
	 * Searches the active items in the given items list.
	 * @param array $items items list
	 * @return array found active items list.
	 */
	protected function findActiveItems(array $items) {
		$activeItems = array();
		foreach ($items as $item) {
			if ($item['active']) {
				$activeItems[] = $item;
			}
		}
		return $activeItems;
	}

	// Tests:

	public function testSetGet() {
		$widget = new QsMenu();

		$testAutoRender = 'test_auto_render';
		$this->assertTrue($widget->setAutoRender($testAutoRender), 'Unable to set auto render!');
		$this->assertEquals($widget->getAutoRender(), $testAutoRender, 'Unable to set view correctly!');

		$testItemActivityDirectMatching = 'testItemActivityDirectMatching';
		$this->assertTrue($widget->setItemActivityDirectMatching($testItemActivityDirectMatching), 'Unable to set item activity direct matching!');
		$this->assertEquals($widget->getItemActivityDirectMatching(), $testItemActivityDirectMatching, 'Unable to set item activity direct matching correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testRenderWithMenuView() {
		$testAutoRender = false;

		$controller = new CController('test');
		$this->assertTrue(is_object($controller), 'Unable to create controller as component!');

		$properties = array(
			'autoRender' => $testAutoRender
		);
		$widget = $controller->beginWidget('QsMenu', $properties);

		$this->assertTrue(is_object($widget), 'Unable to create tested widget with controller!');
	}

	/**
	 * @depends testRenderWithMenuView
	 */
	public function testItemActivityEmptyUrl() {
		$controller = new QsTestController();
		$testItemsCount = 2;
		$testItems = $this->createTestItems($testItemsCount);

		$menuWidget = $controller->beginWidget('QsMenu', array('items'=>$testItems));
		$resultItems = $menuWidget->items;
		$activeItems = $this->findActiveItems($resultItems);
		$controller->endWidget();
		$this->assertTrue(empty($activeItems), 'There are active items while request URL is empty!');
	}

	/**
	 * @depends testItemActivityEmptyUrl
	 */
	public function testItemActivityRootUrl() {
		$controller = new QsTestController();
		$testItemsCount = 2;
		$testItems = $this->createTestItems($testItemsCount);

		$testActiveItem = $testItems[rand(0, $testItemsCount-1)];
		$testRequestUri = Yii::app()->createUrl('/').'/'.rtrim($testActiveItem['url'][0],'/');
		$_SERVER['REQUEST_URI'] = $testRequestUri;

		$menuWidget = $controller->beginWidget('QsMenu', array('items' => $testItems));
		$resultItems = $menuWidget->items;
		$activeItems = $this->findActiveItems($resultItems);
		$controller->endWidget();

		$this->assertTrue(count($activeItems)==1, 'Wrong number of activate items!');
		list($activeItem) = $activeItems;
		$this->assertTrue(strpos($activeItem['url'].'/', $testActiveItem['url'][0]) !== false, 'Wrong item has been activated!');
	}

	/**
	 * @depends testItemActivityRootUrl
	 */
	public function testItemActivitySharedUrlPart() {
		$controller = new QsTestController();
		$sharedUrlPart = 'test_shared_url_part';
		$testItemsCount = 2;
		$testItems = $this->createTestItems($testItemsCount, $sharedUrlPart.'/'.'menu_item');

		$testHighLevelItem = array('label'=>'HighLevelItem', 'url'=>array("{$sharedUrlPart}/"));
		$testItems[] = $testHighLevelItem;

		$testActiveItem = $testItems[rand(0,$testItemsCount-1)];
		$testRequestUri = Yii::app()->createUrl('/').'/'.rtrim($testActiveItem['url'][0],'/');
		$_SERVER['REQUEST_URI'] = $testRequestUri;

		$menuWidgetOptions = array(
			'items' => $testItems,
		);
		$menuWidget = $controller->beginWidget('QsMenu', $menuWidgetOptions);
		$resultItems = $menuWidget->items;

		$activeItems = $this->findActiveItems($resultItems);
		$controller->endWidget();

		$this->assertTrue(count($activeItems)==1, 'Wrong number of activate items!');
	}
}
