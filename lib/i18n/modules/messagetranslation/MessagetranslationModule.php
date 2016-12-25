<?php
/**
 * MessagetranslationModule class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

require_once(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'base' . DIRECTORY_SEPARATOR . 'QsWebModuleTranslationBase.php');

/**
 * MessagetranslationModule is a web module, which provides the ability to manage
 * translation messages.
 * This module should be a part of the web application administration panel.
 *
 * This module requires the blank translation PHP source files for the default application language,
 * which will serve as a map for the future translations. These message files should be created separately
 * before module usage. This could be done using "yiic message" command.
 *
 * Example application configuration:
 * <code>
 * array(
 *     ...
 *     'modules' => array(
 *         'messagetranslation' => array(
 *             'class' => 'qs.i18n.modules.messagetranslation.MessagetranslationModule',
 *             'layout' => '//layouts/main',
 *             'accessRules' => array(
 *                 array(
 *                     'allow',
 *                     'roles' => array('admin')
 *                 ),
 *                 array(
 *                     'deny',
 *                     'users' => array('*'),
 *                 ),
 *             ),
 *         )
 *     ),
 *     ...
 * );
 * </code>
 *
 * @see QsWebModuleTranslationBase
 * @see CMessageSource
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.messagetranslation
 */
class MessagetranslationModule extends QsWebModuleTranslationBase {
	/**
	 * Imports the core classes.
	 */
	protected function importCoreClasses() {
		$moduleName = $this->getName();
		Yii::import("{$moduleName}.components.*");
		Yii::import("{$moduleName}.models.*");
	}

	/**
	 * Registers the core application components.
	 */
	protected function registerCoreComponents() {
		parent::registerCoreComponents();
		$components = array(
			'messageTranslationMapper' => array(
				'class' => $this->chooseMessageTranslationMapperClassName(),
			),
		);
		$this->setComponents($components);
	}

	/**
	 * Chooses the class name for the message translation mapper component,
	 * based on class name of application message source component,
	 * @return string message translation mapper class name.
	 */
	protected function chooseMessageTranslationMapperClassName() {
		$messageTranslationMapperClassName = 'MessageTranslationMapperPhp';
		$messageSource = Yii::app()->getMessages();
		if (is_a($messageSource, 'CDbMessageSource')) {
			$messageTranslationMapperClassName = 'MessageTranslationMapperDb';
		}
		return $messageTranslationMapperClassName;
	}
}
