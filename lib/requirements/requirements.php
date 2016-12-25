<?php
/**
 * This is the default requirements for the {@link QsRequirementChecker} instance.
 * These requirements are mandatory for any application.
 *
 * @var $this QsRequirementChecker
 */
return array(
	// Yii core:
	array(
		'name' => 'PHP version',
		'mandatory' => true,
		'condition' => version_compare(PHP_VERSION, '5.1.0', '>='),
		'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
		'memo' => 'PHP 5.1.0 or higher is required.',
	),
	array(
		'name' => 'Reflection extension',
		'mandatory' => true,
		'condition' => class_exists('Reflection', false),
		'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
	),
	array(
		'name' => 'PCRE extension',
		'mandatory' => true,
		'condition' => extension_loaded('pcre'),
		'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
	),
	array(
		'name' => 'SPL extension',
		'mandatory' => true,
		'condition' => extension_loaded('SPL'),
		'by' => '<a href="http://www.yiiframework.com">Yii Framework</a>',
	),
	// PHP ini
	'phpSafeMode' => array(
		'name' => 'PHP safe mode',
		'mandatory' => true,
		'condition' => $this->checkPhpIniOff('safe_mode'),
		'by' => 'Application core features',
		'memo' => '"safe_mode" should be disabled at php.ini',
	),
	'phpExposePhp' => array(
		'name' => 'Expose PHP',
		'mandatory' => true,
		'condition' => $this->checkPhpIniOff('expose_php'),
		'by' => 'Security reasons',
		'memo' => '"expose_php" should be disabled at php.ini',
	),
	'phpAllowUrlInclude' => array(
		'name' => 'PHP allow url include',
		'mandatory' => true,
		'condition' => $this->checkPhpIniOff('allow_url_include'),
		'by' => 'Security reasons',
		'memo' => '"allow_url_include" should be disabled at php.ini',
	),
);