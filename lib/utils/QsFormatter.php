<?php
/**
 * QsFormatter class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * Extension of the standard Yii class {@link CFormatter},
 * which extends format methods list with the following features:
 * - evaluate given PHP code
 * - evaluate given PHP view code
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.utils
 */
class QsFormatter extends CFormatter {
	/**
	 * Formats the value evaluating it as PHP expression.
	 * @param mixed $value the value to be formatted: a PHP expression or PHP callback to be evaluated.
	 * @param array $data additional parameters to be passed to the above expression/callback.
	 * @return string the formatted result
	 */
	public function formatEval($value, $data = array()) {
		return $this->evaluateExpression($value, $data);
	}

	/**
	 * Formats the value evaluating it as view file content.
	 * @param mixed $value the value to be formatted: view code (HTNL with embeded PHP).
	 * @param array $data additional parameters to be passed to the above expression/callback.
	 * @return string the formatted result
	 */
	public function formatEvalView($value, $data = null) {
		return $this->evalRender($value, $data);
	}

	/**
	 * Evaluates string as view file content.
	 * @param string $_viewStr_ - code to be evaluated.
	 * @param array $_data_ - list of parameters to be parsed.
	 * @return string result of evaluation.
	 */
	protected function evalRender($_viewStr_, array $_data_ = null) {
		$_evalStr_ = '?>' . $_viewStr_;
		if (is_array($_data_)) {
			extract($_data_, EXTR_PREFIX_SAME, 'data');
		}
		ob_start();
		ob_implicit_flush(false);
		eval($_evalStr_);
		return ob_get_clean();
	}
}