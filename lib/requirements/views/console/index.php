<?php
/* @var $this QsRequirementChecker */
/* @var $summary array */
/* @var $requirements array[] */

echo "\nApplication Requirement Checker\n\n";

echo "This script checks if your server configuration meets the requirements\n";
echo "for running Web application.\n";
echo "It checks if the server is running the right version of PHP,\n";
echo "if appropriate PHP extensions have been loaded, and if php.ini file settings are correct.\n";

$header = 'Check conclusion:';
echo "\n{$header}\n";
echo str_pad('', strlen($header), '-')."\n\n";

foreach ($requirements as $key => $requirement) {
	if ($requirement['condition']) {
		echo $requirement['name'].": OK\n";
		echo "\n";
	} else {
		echo $requirement['name'].': '.($requirement['mandatory'] ? 'FAILED!!!' : 'WARNING!!!')."\n";
		echo 'Required by: '.strip_tags($requirement['by'])."\n";
		$memo = strip_tags($requirement['memo']);
		if (!empty($memo)) {
			echo 'Memo: '.strip_tags($requirement['memo'])."\n";
		}
		echo "\n";
	}
}

$summaryString = 'Errors: '.$summary['errors'].'   Warnings: '.$summary['warnings'].'   Total checks: '.$summary['total'];
echo str_pad('', strlen($summaryString), '-')."\n";
if ($summary['errors']>0) {
	echo "\033[0;30m\033[41m".$summaryString."\033[0m";
} elseif ($summary['warnings']>0) {
	echo "\033[0;30m\033[43m".$summaryString."\033[0m";
} else {
	echo "\033[0;30m\033[42m".$summaryString."\033[0m";
}

echo "\n\n";