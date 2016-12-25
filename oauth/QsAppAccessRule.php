<?php
/**
 * QsAppAccessRule class file.
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2012 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsAppAccessRule represents an access rule that is managed by {@link AppAccessControlFilter}.
 *
 * @author Alexander Khromychenko <sanekfl@quartsoft.com>
 */
class QsAppAccessRule extends CAccessRule {

	/**
	 * Checks whether the application is allowed to perform the specified command.
	 * @param @link CActiveRecord $application the application model object
	 * @param string $command the command currently being executed
	 * @return integer 1 if the application is allowed, -1 if the application is denied, 0 if the rule does not apply to the user
	 */
	public function isAppAllowed($application, $command) {
		if ($this->isCommandMatched($command)
			&& $this->isAppMatched($application)
			&& $this->isRoleMatched($application)
		) {
			return $this->allow ? 1 : -1;
		} else {
			return 0;
		}
	}

	/**
	 * @param string $command the command
	 * @return boolean whether the rule applies to the action
	 */
	protected function isCommandMatched($command) {
		return empty($this->actions) || in_array(strtolower($command), $this->actions);
	}

	/**
	 * @param @link CActiveRecord $application the application
	 * @return boolean whether the rule applies to the application
	 */
	protected function isAppMatched($application) {
		if (empty($this->users)) {
			return true;
		}
		foreach ($this->users as $u) {
			if ($u === '*') {
				return true;
			} else {
				if (!strcasecmp($u, $application->id)) {
					return true;
				}
			}
		}

		return false;
	}

}
