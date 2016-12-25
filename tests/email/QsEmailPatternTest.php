<?php

/**
 * Test case for the extension "qs.email.includes.QsEmailPattern".
 * @see QsEmailPattern
 */
class QsEmailPatternTest extends CTestCase {
	public static function setUpBeforeClass() {
		Yii::import('qs.email.includes.*');
	}

	public function testCreate() {
		$emailPattern = new QsEmailPattern();
		$this->assertTrue(is_object($emailPattern));
	}

	/**
	 * @depends testCreate
	 */
	public function testSetGet() {
		$emailPattern = new QsEmailPattern();

		$testId = 'testId';
		$this->assertTrue($emailPattern->setId($testId), 'Unable to set id!');
		$this->assertEquals($emailPattern->getId(), $testId, 'Unable to set id correctly!');

		$testTimestamp = time();
		$this->assertTrue($emailPattern->setTimestamp($testTimestamp), 'Unable to set timestamp!');
		$this->assertEquals($emailPattern->getTimestamp(), $testTimestamp, 'Unable to set timestamp correctly!');

		$testSubject = 'test_subject';
		$this->assertTrue($emailPattern->setSubject($testSubject), 'Unable to set subject!');
		$this->assertEquals($emailPattern->getSubject(), $testSubject, 'Unable to set subject correctly!');

		$testFromEmail = 'test_from_email';
		$this->assertTrue($emailPattern->setFromEmail($testFromEmail), 'Unable to set fromEmail!');
		$this->assertEquals($emailPattern->getFromEmail(), $testFromEmail, 'Unable to set fromEmail correctly!');

		$testFromName = 'test_from_email';
		$this->assertTrue($emailPattern->setFromName($testFromName), 'Unable to set fromName!');
		$this->assertEquals($emailPattern->getFromName(), $testFromName, 'Unable to set fromName correctly!');

		$testBodyHtml = 'test body html';
		$this->assertTrue($emailPattern->setBodyHtml($testBodyHtml), 'Unable to set bodyHtml!');
		$this->assertEquals($emailPattern->getBodyHtml(), $testBodyHtml, 'Unable to set bodyHtml correctly!');

		$testBodyText = 'test body text';
		$this->assertTrue($emailPattern->setBodyText($testBodyText), 'Unable to set bodyText!');
		$this->assertEquals($emailPattern->getBodyText(), $testBodyText, 'Unable to set bodyText correctly!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSetUpFrom() {
		$emailPattern = new QsEmailPattern();

		$testFrom = 'test_string_from';
		$this->assertTrue($emailPattern->setFrom($testFrom), 'Unable to set from with string!');
		$this->assertEquals($emailPattern->getFrom(), $testFrom, 'Unable to set from with string correctly!');
		$this->assertEquals($emailPattern->getFromEmail(), $testFrom, 'Unable to set fromEmail, while set from with string!');

		$testFromEmail = 'test_from_email_native';
		$testFromName = 'test_from_name_native';
		$testFrom = array(
			$testFromEmail,
			$testFromName
		);
		$this->assertTrue($emailPattern->setFrom($testFrom), 'Unable to set from with native array!');
		$this->assertEquals($emailPattern->getFromEmail(), $testFromEmail, 'Unable to set fromEmail, while set from with native array!');
		$this->assertEquals($emailPattern->getFromName(), $testFromName, 'Unable to set fromName, while set from with native array!');

		$testFromEmail = 'test_from_email_key';
		$testFromName = 'test_from_name_key';
		$testFrom = array(
			$testFromEmail => $testFromName
		);
		$this->assertTrue($emailPattern->setFrom($testFrom), 'Unable to set from with named key array!');
		$this->assertEquals($emailPattern->getFromEmail(), $testFromEmail, 'Unable to set fromEmail, while set from with named key array!');
		$this->assertEquals($emailPattern->getFromName(), $testFromName, 'Unable to set fromName, while set from with named key array!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testCreateWithParams() {
		$testId = 'testId';
		$testSubject = 'Test Subject';
		$testFrom = 'Test From';
		$testBodyHtml = 'Test body html';
		$testBodyText = 'Test body text';
		$fields = array(
			'id' => $testId,
			'subject' => $testSubject,
			'from' => $testFrom,
			'bodyHtml' => $testBodyHtml,
			'bodyText' => $testBodyText,
		);

		$emailPattern = new QsEmailPattern($fields);
		$this->assertTrue(is_object($emailPattern));

		$this->assertEquals($emailPattern->id, $testId, 'Unable to set id with constructor!');
		$this->assertEquals($emailPattern->subject, $testSubject, 'Unable to set subject with constructor!');
		$this->assertEquals($emailPattern->from, $testFrom, 'Unable to set from with constructor!');
		$this->assertEquals($emailPattern->bodyHtml, $testBodyHtml, 'Unable to set bodyHtml with constructor!');
		$this->assertEquals($emailPattern->bodyText, $testBodyText, 'Unable to set bodyText with constructor!');
	}
}
