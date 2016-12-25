<?php
/**
 * QsOAuthHelper class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsOAuthHelper provides common functions for OAuth protocol support,
 * like using RFC 3986 standard for URL processing.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.web.auth.oauth
 */
class QsOAuthHelper {
	/**
	 * Performs URL encoding according to RFC 3986.
	 * @param string $string raw string.
	 * @return string encoded string.
	 */
	public static function urlEncode($string) {
		$result = rawurlencode($string);
		if (version_compare(phpversion(), '5.3', '<')) {
			$result = str_replace('%7E', '~', $result);
		}
		return $result;
	}

	/**
	 * Performs URL decoding according to RFC 3986.
	 * @param string $string raw string.
	 * @return string encoded string.
	 */
	public static function urlDecode($string) {
		return rawurldecode($string);
	}

	/**
	 * Parses string as if it were the query string passed via a URL and returns variables.
	 * @param string $queryString query string to be parsed.
	 * @return array parsed data.
	 */
	public static function parseQueryString($queryString) {
		/*parse_str($queryString, $result);
		return $result;*/

		$result = array();
		if (empty($queryString)) {
			return $result;
		}
		$pairs = explode('&', $queryString);
		foreach ($pairs as $pair) {
			list($name, $value) = explode('=', $pair);
			$name = self::urlDecode($name);
			$value = self::urlDecode($value);
			if (isset($result[$name])) {
				if (is_array($result[$name])) {
					$result[$name][] = $value;
				} else {
					$result[$name] = array(
						$result[$name],
						$value
					);
				}
			} else {
				$result[$name] = $value;
			}
		}
		return $result;
	}

	/**
	 * Generate URL-encoded query string.
	 * @param array $formData query parameters.
	 * @return string query string.
	 */
	public static function buildQueryString(array $formData) {
		if (defined('PHP_QUERY_RFC3986')) {
			return http_build_query($formData, null, null, PHP_QUERY_RFC3986);
		}

		// Parameters are sorted by name, using lexicographical byte value ordering. Ref: Spec: 9.1.1
		uksort($formData, 'strcmp');
		$pairs = array();
		foreach ($formData as $name => $value) {
			if (is_array($value)) {
				// If two or more parameters share the same name, they are sorted by their value Ref: Spec: 9.1.1
				sort($value, SORT_STRING);
				foreach ($value as $subValue) {
					$pairs[] = self::urlEncode($name) . '=' . self::urlEncode($subValue);
				}
			} else {
				$pairs[] = self::urlEncode($name) . '=' . self::urlEncode($value);
			}
		}
		return implode('&', $pairs);
	}
}
