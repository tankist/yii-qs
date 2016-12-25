<?php

Yii::import('qs.config.QsConfigStorageFile', true);

/**
 * Test case for the extension "qs.config.QsConfigStorageFile".
 * @see QsConfigStorageFile
 */
class QsConfigStorageFileTest extends CTestCase {
	public function tearDown() {
		$fileName = $this->getTestFileName();
		if (file_exists($fileName)) {
			unlink($fileName);
		}
	}

	/**
	 * @return string test file name
	 */
	protected function getTestFileName() {
		return Yii::getPathOfAlias('application.runtime') . DIRECTORY_SEPARATOR . __CLASS__ . getmypid() . '.php';
	}

	/**
	 * @return QsConfigStorageFile test storage instance.
	 */
	protected function createTestStorage() {
		$config = array(
			'class' => 'QsConfigStorageFile',
			'fileName' => $this->getTestFileName(),
		);
		$component = Yii::createComponent($config);
		return $component;
	}

	// Tests :

	public function testSetGet() {
		$storage = new QsConfigStorageFile();

		$fileName = '/test/file/name.php';
		$storage->setFileName($fileName);
		$this->assertEquals($fileName, $storage->getFileName(), 'Unable to setup file name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testGetDefaultFileName() {
		$storage = new QsConfigStorageFile();
		$fileName = $storage->getFileName();
		$this->assertNotEmpty($fileName, 'Unable to get default file name!');
	}

	/**
	 * @depends testSetGet
	 */
	public function testSave() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$this->assertTrue($storage->save($values), 'Unable to save values!');
		$this->assertFileExists($storage->getFileName(), 'Unable to create file!');
	}

	/**
	 * @depends testSave
	 */
	public function testGet() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$storage->save($values);
		$this->assertEquals($values, $storage->get(), 'Unable to get values!');
	}

	/**
	 * @depends testGet
	 */
	public function testClear() {
		$storage = $this->createTestStorage();
		$values = array(
			'name1' => 'value1',
			'name2' => 'value2',
		);
		$storage->save($values);

		$this->assertTrue($storage->clear(), 'Unable to clear values!');
		$this->assertEquals(array(), $storage->get(), 'Values are not cleared!');
	}
}