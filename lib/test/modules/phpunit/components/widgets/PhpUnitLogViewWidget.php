<?php
/**
 * PhpUnitLogViewWidget class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */
 
/**
 * PhpUnitLogViewWidget is the widget, which renders the PHPUnit test result log.
 *
 * @property SimpleXMLElement $xml public alias of {@link _xml}.
 * @property string $consoleCommandOutput public alias of {@link _consoleCommandOutput}.
 * @property boolean $isFailed public alias of {@link _isFailed}.
 * @property boolean $isIncomplete public alias of {@link _isIncomplete}.
 * @property array $colors public alias of {@link _colors}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.test.modules.phpunit
 */
class PhpUnitLogViewWidget extends CWidget {
	/**
	 * @var SimpleXMLElement xml representation object.
	 */
	protected $_xml = null;
	/**
	 * @var string PHPUnit console command output.
	 */
	protected $_consoleCommandOutput = '';
	/**
	 * @var boolean indicates if test is failed.
	 */
	protected $_isFailed = false;
	/**
	 * @var boolean indicates if test is incomplete.
	 */
	protected $_isIncomplete = false;
	/**
	 * @var array set of colors, which are used to create view.
	 * Should have 'failed', 'success' and 'incomplete'.
	 */
	protected $_colors = array(
		'failed' => '#FF0000',
		'success' => '#00CC00',
		'incomplete' => '#FFA500',
	);

	// Set / Get :

	public function setXml($xml) {
		$this->_xml = $xml;
		return true;
	}

	public function getXml() {
		if (!is_object($this->_xml)) {
			if (is_string($this->_xml)) {
				$this->_xml = simplexml_load_string($this->_xml);
			}
		}
		return $this->_xml;
	}

	public function setConsoleCommandOutput($consoleCommandOutput) {
		$this->_consoleCommandOutput = $consoleCommandOutput;
		return true;
	}

	public function getConsoleCommandOutput() {
		return $this->_consoleCommandOutput;
	}

	public function setIsFailed($isFailed) {
		$this->_isFailed = $isFailed;
		return true;
	}

	public function getIsFailed() {
		return $this->_isFailed;
	}

	public function setIsIncomplete($isIncomplete) {
		$this->_isIncomplete = $isIncomplete;
		return true;
	}

	public function getIsIncomplete() {
		return $this->_isIncomplete;
	}

	public function setColors(array $colors) {
		$this->_colors = $colors;
		return true;
	}

	public function getColors() {
		return $this->_colors;
	}

	/**
	 * Initializes the widget.
	 * This method is called by {@link CBaseController::createWidget}
	 * and {@link CBaseController::beginWidget} after the widget's
	 * properties have been initialized.
	 */
	public function init() {
		$this->setIsFailed(false);
		$this->setIsIncomplete(false);
	}

	/**
	 * Executes the widget.
	 * This method is called by {@link CBaseController::endWidget}.
	 */
	public function run() {
		try {
			$xml = $this->getXml();
			if (!is_object($xml)) {
				throw new CException('Unable to parse xml log!');
			}

			ob_start();
			$this->renderTestSuite($xml->xpath('testsuite'));
			$testSuitesHtml = ob_get_contents();
			ob_clean();

			$mainResultHtml = $this->renderMainResult();
			$html = $mainResultHtml.$testSuitesHtml;
		} catch (CException $exception) {
			$html = $this->renderError($exception->getMessage());
		}

		echo $html;
	}

	// Render :

	/**
	 * Renders green bar HTML.
	 * @return string green bar HTML code.
	 */
	protected function renderGreenBar() {
		return $this->render('green_bar', null, true);
	}

	/**
	 * Renders red bar HTML.
	 * @return string ref bar HTML code.
	 */
	protected function renderRedBar() {
		return $this->render('red_bar', null, true);
	}

	/**
	 * Renders red bar HTML.
	 * @return string ref bar HTML code.
	 */
	protected function renderYellowBar() {
		return $this->render('yellow_bar', null, true);
	}

	/**
	 * Renders the test main result: failed, success or incomplete.
	 * @return string main result HTML.
	 */
	protected function renderMainResult() {
		if ($this->getIsFailed()) {
			return $this->renderRedBar();
		} elseif ($this->getIsIncomplete()) {
			return $this->renderYellowBar();
		} else {
			return $this->renderGreenBar();
		}
	}

	/**
	 * Renders a HTML content as success node.
	 * @param string $content internal HTML content.
	 * @param integer $level - block level
	 * @return string result HTML.
	 */
	protected function renderAsSuccess($content, $level=0) {
		return $this->renderTreeNode($content, $level, false);
	}

	/**
	 * Renders a HTML content as failed node.
	 * @param string $content internal HTML content.
	 * @param integer $level - block level
	 * @return string result HTML.
	 */
	protected function renderAsFailed($content, $level=0) {
		return $this->renderTreeNode($content, $level, true);
	}

	/**
	 * Renders a HTML content as incomplete node.
	 * @param string $content internal HTML content.
	 * @param integer $level - block level
	 * @return string result HTML.
	 */
	protected function renderAsIncomplete($content, $level=0) {
		return $this->renderTreeNode($content, $level, false, true);
	}

	/**
	 * Renders a HTML content as styled node of the tree.
	 * Block can be rendered as success, failed or incomplete,
	 * @param string $content internal HTML content.
	 * @param integer $level - block tree level.
	 * @param boolean $failed - indicates if block should be rendered as failed.
	 * @param boolean $incomplete - indicates if block should be rendered as incomplete.
	 * @return boolean success.
	 */
	protected function renderTreeNode($content, $level=0, $failed=false, $incomplete=false) {
		$data = array(
			'content' => $content,
			'level' => $level,
			'failed' => $failed,
			'incomplete' => $incomplete,
		);
		return $this->render('tree_node', $data, false);
	}

	/**
	 * Renders the error appearance.
	 * @param string $errorMessage error message content.
	 * @return string error HTML.
	 */
	public function renderError($errorMessage) {
		$errorMessage .= '<br />Console output:<br />'.nl2br($this->getConsoleCommandOutput());
		$html = $this->renderRedBar();
		ob_start();
		$this->renderAsFailed($errorMessage);
		$html .= ob_get_contents();
		ob_clean();
		return $html;
	}

	// Render Results :

	/**
	 * Renders the test suite.
	 * @param array|SimpleXMLElement $testSuite test suite xml element(s).
	 * @param integer $level - test suite level on the tree.
	 * @return boolean success.
	 */
	protected function renderTestSuite($testSuite, $level=0) {
		if (is_array($testSuite)) {
			foreach ($testSuite as $subTestSuite) {
				$this->renderTestSuite($subTestSuite, $level);
			}
			return true;
		}
		if (!is_object($testSuite) || empty($testSuite)) {
			return false;
		}

		$this->renderTestSuiteInfo($testSuite, $level);

		$testCase = $testSuite->xpath('testcase');
		if (!empty($testCase)) {
			$this->renderTestCase($testCase, $level+1);
		}

		$nextTestSuite = $testSuite->xpath('testsuite');
		if (!empty($nextTestSuite)) {
			$this->renderTestSuite($nextTestSuite, $level+1);
		}

		return true;
	}

	/**
	 * Renders the test case.
	 * @param array|SimpleXMLElement $testCase test case xml element(s).
	 * @param integer $level - test suite level on the tree.
	 * @return boolean success.
	 */
	protected function renderTestCase($testCase, $level=0) {
		if (is_array($testCase)) {
			foreach ($testCase as $subTestCase) {
				$this->renderTestCase($subTestCase, $level);
			}
			return true;
		}
		if (!is_object($testCase) || empty($testCase)) {
			return false;
		}
		$this->renderTestCaseInfo($testCase, $level);
		return true;
	}

	/**
	 * Renders the particular test suite information.
	 * @param SimpleXMLElement $testSuite test suite xml element.
	 * @param integer $level - test suite level on the tree.
	 * @return boolean success.
	 */
	protected function renderTestSuiteInfo(SimpleXMLElement $testSuite, $level=0) {
		$testSuiteAttributes = $testSuite->attributes();

		$data = array(
			'testSuite' => $testSuite,
			'testSuiteAttributes' => $testSuiteAttributes,
		);
		$html = $this->render('test_suite_info', $data, true);

		if ($testSuiteAttributes['failures']>0 || $testSuiteAttributes['errors']>0) {
			$this->setIsFailed(true);
			return $this->renderAsFailed($html, $level);
		} elseif ($testSuiteAttributes['assertions']<=0) {
			return $this->renderAsIncomplete($html, $level);
		} else {
			return $this->renderAsSuccess($html, $level);
		}
	}

	/**
	 * Renders the particular test case information.
	 * @param SimpleXMLElement $testCase test case xml element.
	 * @param integer $level - test suite level on the tree.
	 * @return boolean success.
	 */
	protected function renderTestCaseInfo(SimpleXMLElement $testCase, $level=0) {
		$testCaseAttributes = $testCase->attributes();

		$data = array(
			'testCase' => $testCase,
			'testCaseAttributes' => $testCaseAttributes,
		);
		$html = $this->render('test_case_info', $data, true);

		$html = '<b>Test case: "'.$testCaseAttributes['class'].'::'.$testCaseAttributes['name'].'" (file: "'.$testCaseAttributes['file'].'" at line '.$testCaseAttributes['line'].'):</b><br />';
		$html .= "Assertions: {$testCaseAttributes['assertions']}<br />";
		$html .= "Time: {$testCaseAttributes['time']}<br />";

		if (isset($testCase->failure)) {
			$html .= "<b>Failure:</b><br /> ".nl2br( htmlspecialchars($testCase->failure) );
			$this->setIsFailed(true);
			return $this->renderAsFailed($html, $level);
		} elseif (isset($testCase->error)) {
			$errorAttributes = $testCase->error->attributes();
			if (isset($errorAttributes['type'])) {
				$errorType = (string)$errorAttributes['type'];
			} else {
				$errorType = 'unknown';
			}
			if (stripos($errorType, 'incomplete') || (stripos($errorType, 'skipped'))) {
				$this->setIsIncomplete(true);
				$html .= nl2br( htmlspecialchars($testCase->error) );
				return $this->renderAsIncomplete($html, $level);
			} else {
				$html .= "<b>Error:</b><pre>".htmlspecialchars($testCase->error)."</pre>";
				$this->setIsFailed(true);
				return $this->renderAsFailed($html, $level);
			}
		} elseif ($testCaseAttributes['assertions']<=0) {
			$this->setIsIncomplete(true);
			$html .= 'Incomplete Test: no assertions';
			return $this->renderAsIncomplete($html, $level);
		} else {
			return $this->renderAsSuccess($html, $level);
		}
	}
}
