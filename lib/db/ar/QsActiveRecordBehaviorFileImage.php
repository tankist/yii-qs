<?php
/**
 * QsActiveRecordBehaviorFileImage class file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

Yii::import('qs.db.ar.QsActiveRecordBehaviorFileTransform');

/**
 * Extension of the {@link QsActiveRecordBehaviorFile} - behavior for the {@link CActiveRecord}.
 * QsActiveRecordBehaviorFileImage is developed for the managing image files.
 * Behavior allows to set up several different transformations for image, so actually several files will be related to the one record in the database table. 
 * You can set up the {@link transformCallback} in order to specify transformation method(s).
 * By default behavior attempts to call convert method of the {@link imageFileConvertorComponentName} application component.
 * Such component should satisfy the {@link IQsFileConvertor} interface.
 * If this component is not available, the behavior performs resize with {@link ImageMagic} tool.
 *
 * In order to specify image resizing, you should set {@link fileTransforms} field.
 * For example:
 * <code>
 * array(
 *     'full' => array(800, 600),
 *     'thumbnail' => array(200, 150)
 * );
 * </code>
 * In order save original file without any transformations, set string value with native key.
 * For example:
 * <code>
 * array(
 *     'origin',
 *     'full' => array(800, 600),
 *     'thumbnail' => array(200, 150)
 * );
 * </code>
 *
 * Note: you can always use {@link saveFile} method to attach any file (not just uploaded one) to the model.
 *
 * Attention: this extension requires the extension "qs.files.storages" to be attached to the application!
 * Files will be saved using file storage component.
 *
 * @see QsActiveRecordBehaviorFileTransform
 * @see IQsFileStorage
 * @see IQsFileStorageBucket
 * @see IQsFileConvertor
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.db.ar
 */
class QsActiveRecordBehaviorFileImage extends QsActiveRecordBehaviorFileTransform {
	/**
	 * @var string name of the application component, which should be used to convert image files.
	 * Note: specified component should satisfy the {@link IQsFileConvertor} interface.
	 */
	public $imageFileConvertorComponentName = 'imageFileConvertor';

	/**
	 * Overridden.
	 * @return callback transform callback
	 */
	public function getTransformCallback() {
		if ($this->_transformCallback === null) {
			$this->initTransformCallback();
		}
		return $this->_transformCallback;
	}

	/**
	 * Initializes the {@link transformCallback} with the default value.
	 * @return boolean success.
	 */
	protected function initTransformCallback() {
		$this->_transformCallback = array($this, 'transformImageFileResize');
		return true;
	}

	/**
	 * Transforms source file to destination file according to the transformation settings,
	 * using ImageMagic tool.
	 * @param string $sourceFileName is the full source file system name.
	 * @param string $destinationFileName is the full destination file system name.
	 * @param array $transformSettings is the transform settings data, it should be the pair: imageWidth & imageHeight,
	 * For example:
	 * <code>array(800, 600);</code>
	 * @throws CException on invalid transform settings.
	 * @return boolean success.
	 */
	public function transformImageFileResize($sourceFileName, $destinationFileName, $transformSettings) {
		if (!is_array($transformSettings)) {
			throw new CException('Wrong transform settings are passed to "' . get_class($this) . '::' . __FUNCTION__ . '"');
		}

		$imageFileConvertorComponentName = $this->imageFileConvertorComponentName;
		if (Yii::app()->hasComponent($imageFileConvertorComponentName)) {
			$imageFileConvertor = Yii::app()->getComponent($imageFileConvertorComponentName);
			$imageConvertOptions = array(
				'-strip',
				'colorspace' => 'rgb',
				'resize' => $transformSettings,
			);
			return $imageFileConvertor->convert($sourceFileName, $destinationFileName, $imageConvertOptions);
		} else {
			list($width, $height) = array_values($transformSettings);
			$command = 'convert ' . escapeshellarg($sourceFileName) . ' -strip -colorspace rgb -resize ' . escapeshellarg($width . 'x' . $height . '^') . ' ' . escapeshellarg($destinationFileName);
			exec($command, $output, $returnStatus);
			if ($returnStatus != 0) {
				return false;
			}
			return true;
		}
	}
}