<?php
/**
 * QsAppAccessControlFilter class file.
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2012 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

//@todo Yii::import();

/**
 * QsAppAccessControlFilter performs authorization checks for the specified commands.
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 */
class QsAppAccessControlFilter extends CAccessControlFilter {

	public $appAccessRule = array(
		'class' => 'ext.qs.oauth.QsAppAccessRule',
	);

	/**
	 *
	 * @var array
	 */
	protected $_rules = array();

	/**
	 * @return array list of access rules.
	 */
	public function getRules() {
		return $this->_rules;
	}

	/**
	 * @param array $rules list of access rules.
	 */
	public function setRules($rules) {
		foreach ($rules as $rule) {
			if (is_array($rule) && isset($rule[0])) {
				$r = Yii::createComponent($this->appAccessRule);
				$r->allow = $rule[0] === 'allow';
				foreach (array_slice($rule, 1) as $name => $value) {
					if ($name === 'expression' || $name === 'roles' || $name === 'message' || $name === 'deniedCallback') {
						$r->$name = $value;
					} else {
						$r->$name = array_map('strtolower', $value);
					}
				}
				$this->_rules[] = $r;
			}
		}
	}

	/**
	 * Performs pre-acion filtering
	 *
	 * @param {@link CActiveRecord} $application
	 * @param string $command
	 * @return boolean true if success
	 */
	public function checkFiler($application, $command) {
		foreach ($this->getRules() as $rule) {
			if (($allow = $rule->isAppAllowed($application, $command)) > 0) // allowed
			{
				break;
			} else {
				if ($allow < 0) // denied
				{
					if (isset($rule->deniedCallback)) {
						call_user_func($rule->deniedCallback, $rule);
					} else {
						$this->accessDenied($this->resolveErrorMessage($rule));
					}

					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Denies the access of the application.
	 * This method is invoked when access check fails.
	 *
	 * @param string $message the error message to be displayed
	 */
	protected function accessDenied($message) {
		throw new CHttpException(403, $message);
	}

}
