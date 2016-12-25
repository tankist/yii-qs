<?php

/**
 * Test case for the extension "qs.email.QsEmailMessage".
 * @see QsEmailMessage
 */
class QsEmailMessageTest extends CTestCase {
	/**
	 * @var CApplicationComponent email application component backup.
	 */
	protected static $_emailManagerBackup = null;

	public static function setUpBeforeClass() {
		Yii::import('qs.email.*');
		//Yii::import('qs.email.includes.*');
	}

	public static function tearDownAfterClass() {
		if (is_object(self::$_emailManagerBackup)) {
			Yii::app()->setComponent('email', self::$_emailManagerBackup);
		}
	}

	public function setUp() {
		if (!is_object(self::$_emailManagerBackup)) {
			// Swift autoload may brake on setUpBeforeClass:
			if (Yii::app()->hasComponent('email')) {
				self::$_emailManagerBackup = Yii::app()->getComponent('email');
			}
			$emailComponentConfig = array(
				'class' => 'QsEmailManager'
			);
			$emailComponent = Yii::createComponent($emailComponentConfig);
			$emailComponent->init();
			Yii::app()->setComponent('email', $emailComponent);
		}
	}

	// Tests:

	public function testCreate() {
		$emailMessage = new QsEmailMessage();
		$this->assertTrue(is_object($emailMessage));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetFrom() {
		$emailMessage = new QsEmailMessage();

		$testFrom = 'dymmy@somedomain.com';
		$emailMessage->setFrom($testFrom);

		$returnedFrom = $emailMessage->getFrom();
		$this->assertTrue(array_key_exists($testFrom, $returnedFrom), 'Unable to set "from"!');

		$returnedReplyTo = $emailMessage->getReplyTo();
		$this->assertTrue(array_key_exists($testFrom, $returnedReplyTo), 'Unable to set "reply-to"!');
	}

	/**
	 * @depends testCreate
	 */
	public function testSimpleSend() {
		$emailMessage = new QsEmailMessage();

		$emailMessage->addTo('pklimov1@quartsoft.com');
		$emailMessage->setFrom('develqs@quartsoft.com');
		$emailMessage->setSubject('Test Yii Email Subject');
		$emailMessage->setBody('Test Yii Email Body.');

		$this->assertGreaterThan(0, $emailMessage->send(), 'Unable to send email message!' );
	}

	/**
	 * @depends testSimpleSend
	 */
	public function testSendAlternativeBody() {
		$emailMessage = new QsEmailMessage();

		$testTo = 'pklimov1@quartsoft.com';
		$emailMessage->addTo($testTo);
		$emailMessage->setFrom('develqs@quartsoft.com');
		$emailMessage->setSubject('Test Yii Email Subject');

		$testBodyHtml = '<html>Test HTML content.</html>';
		$emailMessage->addBodyHtml($testBodyHtml);

		$testBodyText = 'Test plain text content.';
		$emailMessage->addBodyText($testBodyText);

		$this->assertGreaterThan(0, $emailMessage->send(), 'Unable to send email message with alternative body!');
	}

	/**
	 * @depends testSimpleSend
	 */
	public function testSendAttachments() {
		$emailMessage = new QsEmailMessage();
		$testTo = 'pklimov1@quart-soft.com';
		$emailMessage->addTo($testTo);
		$emailMessage->setFrom('develqs@quartsoft.com');
		$emailMessage->setSubject('Test Yii Email Attachment Subject');
		$emailMessage->setBodyHtml('Test Yii Email Attachment body');

		$testFileName = __FILE__;
		$this->assertTrue($emailMessage->attachFile($testFileName), 'Unable to attach file to the email message!');

		$testAttachmentContent = 'Test Attachment Content';
		$testAttachmentFileName = 'test_attachment_file_name.txt';
		$this->assertTrue($emailMessage->createAttachment($testAttachmentContent, $testAttachmentFileName), 'Unable to create attachment to the email message!');

		$this->assertGreaterThan(0, $emailMessage->send(), 'Unable to send email message with attachment!');
	}

	/**
	 * @depends testSimpleSend
	 */
	public function testApplyPattern() {
		$emailMessage = new QsEmailMessage();

		$testEmailPattern = new QsEmailPattern();
		$testSubject = 'Test Subject';
		$testEmailPattern->setSubject($testSubject);
		$testFrom = 'test_from@somedomain.com';
		$testEmailPattern->setFrom($testFrom);
		$testBodyHtml = '<html>Test body html</html>';
		$testEmailPattern->setBodyHtml($testBodyHtml);
		$testBodyText = 'Test body text';
		$testEmailPattern->setBodyText($testBodyText);

		$this->assertTrue($emailMessage->applyPattern($testEmailPattern), 'Unable to apply pattern!');
		$this->assertSame($emailMessage->getSubject(), $testEmailPattern->getSubject(), 'Unable to apply subject!');
		$this->assertTrue(in_array($testEmailPattern->getFrom(), $emailMessage->getFrom()) || array_key_exists($testEmailPattern->getFrom(), $emailMessage->getFrom()), 'Unable to apply from!');
	}
}
