<?php /* @var $this PhpUnitLogViewWidget */ ?>
<?php /* @var $testSuite SimpleXMLElement */ ?>
<?php /* @var $testSuiteAttributes array */ ?>
<b>
Test suite: &quot;<?php echo $testSuiteAttributes['name']; ?>&quot;<?php if ($testSuiteAttributes['file']) { ?>(file: "<?php echo $testSuiteAttributes['file']; ?>"):<?php } ?>
</b><br />
Tests: <?php echo $testSuiteAttributes['tests']; ?><br /> 
Assertions: <?php echo $testSuiteAttributes['assertions']; ?><br /> 
Failures: <?php echo $testSuiteAttributes['failures']; ?><br />
Errors: <?php echo $testSuiteAttributes['errors']; ?><br />
Time: <?php echo $testSuiteAttributes['time']; ?><br />