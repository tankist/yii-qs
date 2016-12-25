<?php
/**
 * QsApplicationBehaviorApplyConfigManager class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/**
 * QsApplicationBehaviorApplyConfigManager is a behavior for the {@link CApplication},
 * which allows to apply configuration stored in {@link QsConfigManager}.
 *
 * @see QsConfigManager
 *
 * @method CApplication getOwner()
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.application
 */
class QsApplicationBehaviorApplyConfigManager extends CBehavior {
	/**
	 * @var string id of the config manager application component.
	 */
	public $configManagerComponentId = 'configManager';

	/**
	 * @throws CException on failure
	 * @return QsConfigManager config manager instance.
	 */
	public function getConfigManager() {
		$configManager = Yii::app()->getComponent($this->configManagerComponentId);
		if (!is_object($configManager)) {
			throw new CException('Component "' . $this->configManagerComponentId . '" is missing.');
		}
		return $configManager;
	}

	/**
	 * Declares events and the corresponding event handler methods.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events() {
		return array(
			'onBeginRequest' => 'beginRequest'
		);
	}

	/**
	 * This event raises before {@link CApplication}.
	 * It update {@link CApplication::params} with database data.
	 * @param CEvent $event event object.
	 */
	public function beginRequest(CEvent $event) {
		$this->updateConfig();
	}

	/**
	 * Updates owner configuration from config manager.
	 */
	protected function updateConfig() {
		try {
			$this->getOwner()->configure($this->getConfigManager()->fetchConfig());
		} catch (CException $exception) {
			Yii::log('"' . get_class($this) . '" is unable to update application configuration from config manager:' . $exception->getMessage(), CLogger::LEVEL_WARNING);
		}
	}
}