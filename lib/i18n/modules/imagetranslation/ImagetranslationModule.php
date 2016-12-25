<?php
/**
 * ImagetranslationModule class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

require_once(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'base'.DIRECTORY_SEPARATOR.'QsWebModuleTranslationBase.php');

/**
 * ImagetranslationModule is a module, which provides the ability to manage
 * image file translations.
 * This module should be a part of the web application administration panel.
 *
 * Attention: this module expects the {@link QsImageTranslationSource} application component present!
 *
 * Example application configuration:
 * <code>
 * array(
 *     'components' => array(
 *         ...
 *         'imageTranslationSource' => array(
 *             'class' => 'QsImageTranslationSourceFileStorage',
 *             ...
 *         ),
 *         ...
 *     ),
 *     ...
 *     'modules' => array(
 *         'imagetranslation' => array(
 *             'class' => 'qs.i18n.modules.imagetranslation.ImagetranslationModule',
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
 * @see QsImageTranslationSource
 *
 * @property string $imageTranslationSourceComponentName public alias of {@link _imageTranslationSourceComponentName}.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.i18n.modules.imagetranslation
 */
class ImagetranslationModule extends QsWebModuleTranslationBase {
	/**
	 * @var string name of the image translation source application component.
	 * @see QsImageTranslationSource
	 */
	protected $_imageTranslationSourceComponentName = 'imageTranslationSource';

	public function setImageTranslationSourceComponentName($imageTranslationSourceComponentName) {
		if (!is_string($imageTranslationSourceComponentName)) {
			throw new CException('"' . get_class($this) . '::imageTranslationSourceComponentName" should be a string!');
		}
		$this->_imageTranslationSourceComponentName = $imageTranslationSourceComponentName;
		return true;
	}

	public function getImageTranslationSourceComponentName() {
		return $this->_imageTranslationSourceComponentName;
	}

	/**
	 * Returns the image translation source application component.
	 * @throws CException if component can not be found.
	 * @return QsImageTranslationSource image translation source application component.
	 */
	public function getImageTranslationSource() {
		$imageTranslationSource = Yii::app()->getComponent($this->getImageTranslationSourceComponentName());
		if (!is_object($imageTranslationSource)) {
			throw new CException('Unable to find image translation source component');
		}
		return $imageTranslationSource;
	}

	/**
	 * Imports the core classes.
	 */
	protected function importCoreClasses() {
		$moduleName = $this->getName();
		Yii::import("{$moduleName}.models.*");
	}
}
