<?php

Yii::import('qs.web.auth.oauth.QsOAuthHelper');

/**
 * Test case for the extension "qs.web.auth.oauth.QsOAuthHelper".
 * @see QsOAuthHelper
 */
class QsOAuthHelperTest extends CTestCase {
	/**
	 * Data provider for {@link testUrlEncode()}
	 * @return array test data.
	 */
	public function urlEncodeDecodeDataProvider() {
		return array(
			array(
				'test space',
				'test%20space',
			),
			array(
				'test~tilde',
				'test~tilde',
			),
			array(
				'test&amp',
				'test%26amp',
			),
		);
	}

	/**
	 * @dataProvider urlEncodeDecodeDataProvider
	 *
	 * @param string $sourceString source string.
	 * @param string $expectedEncodedString expected URL encoded string.
	 */
	public function testUrlEncode($sourceString, $expectedEncodedString) {
		$this->assertEquals($expectedEncodedString, QsOAuthHelper::urlEncode($sourceString));
	}

	/**
	 * @dataProvider urlEncodeDecodeDataProvider
	 *
	 * @param string $expectedDecodedString expected URL encoded string.
	 * @param string $sourceString source string.
	 */
	public function testUrlDecode($expectedDecodedString, $sourceString) {
		$this->assertEquals($expectedDecodedString, QsOAuthHelper::urlDecode($sourceString));
	}

	/**
	 * Data provider for {@link testParseQueryString()}
	 * @return array test data.
	 */
	public function queryStringDataProvider() {
		return array(
			array(
				'test=name',
				array(
					'test' => 'name'
				)
			),
			array(
				'test1=name1&test2=name2',
				array(
					'test1' => 'name1',
					'test2' => 'name2',
				)
			),
			array(
				'name=test%20space',
				array(
					'name' => 'test space',
				)
			),
			array(
				'name=test~tilde',
				array(
					'name' => 'test~tilde',
				)
			),
		);
	}

	/**
	 * @dataProvider queryStringDataProvider
	 *
	 * @param $queryString
	 * @param array $expectedParseResult
	 */
	public function testParseQueryString($queryString, array $expectedParseResult) {
		$this->assertEquals($expectedParseResult, QsOAuthHelper::parseQueryString($queryString));
	}

	/**
	 * @dataProvider queryStringDataProvider
	 *
	 * @param string $expectedQueryString expected built query string.
	 * @param array $formData form data.
	 */
	public function testBuildQueryString($expectedQueryString, array $formData) {
		$this->assertEquals($expectedQueryString, QsOAuthHelper::buildQueryString($formData));
	}
}
