<?php

/**
 * Test case for the extension "qs.email.QsEmailManager".
 * This is final level test for the "email" extension package.
 * @see QsEmailManager
 */
class QsEmailManagerTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.email.*');
	}

	/**
	 * Creates test email manager instance.
	 * @return QsEmailManager email manager instance.
	 */
	protected function createTestEmailManager() {
		$config = array(
			'class' => 'QsEmailManager'
		);
		$emailManager = Yii::createComponent($config);
		$emailManager->init();
		return $emailManager;
	}

	// Tests:

	public function testCreate() {
		$emailManager = new QsEmailManager();
		$emailManager->init();
		$this->assertTrue(is_object($emailManager));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$emailManager = $this->createTestEmailManager();

		$testLogging = 'testLoggin';
		$this->assertTrue($emailManager->setLogging($testLogging), 'Unable to set logging!');
		$this->assertEquals($emailManager->getLogging(), $testLogging, 'Unable to set logging correctly!');

		$testTestMode = 'TestTestMode';
		$this->assertTrue($emailManager->setTestMode($testTestMode), 'Unable to set testMode!');
		$this->assertEquals($emailManager->getTestMode(), $testTestMode, 'Unable to set testMode correctly!');

		$testTestEmail = 'testTestEmail@testdomain';
		$this->assertTrue($emailManager->setTestEmail($testTestEmail), 'Unable to set testEmail!');
		$this->assertEquals($emailManager->getTestEmail(), $testTestEmail, 'Unable to set testEmail correctly!');
	}

	/**
	 * @depends testCreate
	 */
	public function testGetDefaultComponents() {
		$emailManager = $this->createTestEmailManager();

		$returnedTransport = $emailManager->getTransport();
		$this->assertTrue(is_object($returnedTransport), 'Unable to get default transport object!');

		$returnedMailer = $emailManager->getMailer();
		$this->assertTrue(is_object($returnedMailer), 'Unable to get default mailer object!');

		$returnedPatternStorage = $emailManager->getPatternStorage();
		$this->assertTrue(is_object($returnedPatternStorage), 'Unable to get default pattern storage object!');

		$returnedPatternComposer = $emailManager->getPatternComposer();
		$this->assertTrue(is_object($returnedPatternComposer), 'Unable to get default pattern composer object!');
	}

	/**
	 * @depends testGetDefaultComponents
	 */
	public function testTestModeBcc() {
		$emailManager = $this->createTestEmailManager();

		$testTestEmail = 'pklimov1@quartsoft.com';
		$emailManager->setTestEmail($testTestEmail);
		$emailManager->setTestMode(QsEmailManager::TestModeBcc);

		$testEmailMessage = new QsEmailMessage();
		$testTo = 'test_to_email@domain.com';
		$testEmailMessage->setTo($testTo);
		$testEmailMessage->setFrom('develqs@quartsoft.com');
		$testEmailMessage->setSubject('Yii email in test mode');
		$testEmailMessage->setBodyText('Test email in test mode.');

		$this->assertGreaterThan(0, $emailManager->send($testEmailMessage), 'Unable to send email message in the bcc test mode!' );

		$returnedBcc = $testEmailMessage->getBcc();
		$this->assertTrue(in_array($testTestEmail, $returnedBcc) || array_key_exists($testTestEmail, $returnedBcc), 'Unable to apply the bcc test mode!');
	}

	/**
	 * @depends testGetDefaultComponents
	 */
	public function testTestModeRedirect() {
		$emailManager = $this->createTestEmailManager();

		$testTestEmail = 'pklimov1@quartsoft.com';
		$emailManager->setTestEmail($testTestEmail);
		$emailManager->setTestMode(QsEmailManager::TestModeRedirect);

		$testEmailMessage = new QsEmailMessage();
		$testTo = 'test_to_email@domain.com';
		$testEmailMessage->setTo($testTo);
		$testEmailMessage->setFrom('develqs@quartsoft.com');
		$testEmailMessage->setSubject('Yii email in test mode');
		$testEmailMessage->setBodyText('Test email in test mode.');

		$this->assertGreaterThan(0, $emailManager->send($testEmailMessage), 'Unable to send email message in the redirect test mode!' );

		$returnedTo = $testEmailMessage->getTo();
		$this->assertTrue(!in_array($testTo, $returnedTo) && !array_key_exists($testTo, $returnedTo), 'Unable to apply the redirect test mode!');
	}

	/**
	 * @depends testGetDefaultComponents
	 */
	public function testTestModeSilence() {
		$emailManager = $this->createTestEmailManager();

		$testTestEmail = 'pklimov@quartsoft.com';
		$emailManager->setTestEmail($testTestEmail);
		$emailManager->setTestMode(QsEmailManager::TestModeSilence);

		$testEmailMessage = new QsEmailMessage();
		$testTo = 'test_to_email@domain.com';
		$testEmailMessage->setTo($testTo);
		$testEmailMessage->setFrom('develqs@quartsoft.com');
		$testEmailMessage->setSubject('Yii email in test mode');
		$testEmailMessage->setBodyText('Test email in test mode.');

		$this->assertGreaterThan(0, $emailManager->send($testEmailMessage), 'Unable to send email message in the silence test mode!');

		/*$returnedTo = $testEmailMessage->getTo();
		$assertExpression = (!in_array($testTo, $returnedTo) && !array_key_exists($testTo, $returnedTo) && !in_array($testTestEmail, $returnedTo) && !array_key_exists($testTestEmail, $returnedTo));
		$this->assertTrue($assertExpression, 'Unable to apply the silence test mode!');*/
	}

	/**
	 * @depends testCreate
	 */
	public function testSetUpTransport() {
		$emailManager = $this->createTestEmailManager();

		$testTransportType = 'smtp';
		$testTransportConfig = array(
			'type' => $testTransportType,
		);
		$this->assertTrue($emailManager->setTransport($testTransportConfig), 'Unable to set transport!');

		$returnedTransport = $emailManager->getTransport();
		$this->assertTrue(is_object($returnedTransport), 'Returned transport is not an object!');

		$this->assertContains($testTransportType, get_class($returnedTransport), 'Transport type has been ignored!', true);
	}
}
