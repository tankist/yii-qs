<?php /* @var $this PhpUnitLogViewWidget */ ?>
<?php /* @var $testCase SimpleXMLElement */ ?>
<?php /* @var $testCaseAttributes array */ ?>
<b>
	Test case: &quot;<?php echo $testCaseAttributes['class']; ?>::<?php echo $testCaseAttributes['name']; ?>&quot; <?php if ($testCaseAttributes['file']) { ?>(file: &quot;<?php echo $testCaseAttributes['file']; ?>&quot; at line <?php echo $testCaseAttributes['line']; ?>)<?php } ?>:
</b><br />
Assertions: <?php echo $testCaseAttributes['assertions']; ?><br />
Time: <?php echo $testCaseAttributes['time']; ?><br />
<?php if ($testCase->failure) { ?>
	<b>Failure:</b><br /> <?php echo nl2br( htmlspecialchars($testCase->failure) ); ?><br />
<?php } ?>
<?php if ($testCase->error) { ?>
	<b>Error:</b><br /> <?php echo nl2br( htmlspecialchars($testCase->error) ); ?><br />
<?php } ?>