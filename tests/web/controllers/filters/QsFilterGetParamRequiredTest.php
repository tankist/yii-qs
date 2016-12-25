<?php
 
/**
 * Test case for the extension "qs.web.controllers.filters.QsFilterGetParamRequired".
 * @see QsFilterGetParamRequired
 */
class QsFilterGetParamRequiredTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.web.controllers.filters.QsFilterGetParamRequired');
	}

	/**
	 * Creates test filter chain instance.
	 * @return CFilterChain filter chain instance.
	 */
	protected function createTestFilterChain() {
		$controller = new QsTestController();
		$action = new CInlineAction($controller, 'test');
		$filterChain = $this->getMock('CFilterChain', array('run'), array($controller, $action));
		return $filterChain;
	}

	// Tests:

	public function testSetGet() {
		$filter = new QsFilterGetParamRequired();

		$testGetParamNames = array(
			'test_get_param_name_1',
			'test_get_param_name_2',
		);
		$this->assertTrue($filter->setGetParamNames($testGetParamNames), 'Unable to set get param names!');
		$this->assertEquals($testGetParamNames, $filter->getGetParamNames(), 'Unable to set get param names correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testFilterBlockRequest() {
		$filter = new QsFilterGetParamRequired();
		$testFilterChain = $this->createTestFilterChain();

		$testGetParamName = 'test_get_param_name';
		$filter->setGetParamNames(array($testGetParamName));

		$this->setExpectedException('CHttpException');

		$filter->filter($testFilterChain);
	}

	/**
	 * @depends testSetGet
	 */
	public function testFilterAllowRequest() {
		$filter = new QsFilterGetParamRequired();
		$testFilterChain = $this->createTestFilterChain();

		$testGetParamName = 'test_get_param_name';
		$filter->setGetParamNames(array($testGetParamName));

		$_GET[$testGetParamName] = 'test_get_param_name';

		$filter->filter($testFilterChain);

		$this->assertTrue(true, 'Filter has not been passed!');
	}
}
