<?php
/**
 * IQsFileStorage interface file.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @link http://www.quartsoft.com/
 * @copyright Copyright &copy; 2010-2013 QuartSoft ltd.
 * @license http://www.quartsoft.com/license/
 */

/** 
 * IQsFileStorage is an interface for the all file storages.
 * File storage should be a hub for the {@link IQsFileStorageBucket} instances.
 *
 * @author Paul Klimov <pklimov@quartsoft.com>
 * @package qs.files.storages
 */
interface IQsFileStorage {
	/**
	 * Sets the list of available buckets.
	 * @param array $buckets - set of bucket instances or bucket configurations.
	 * @return boolean success.
	 */
	public function setBuckets(array $buckets);

	/**
	 * Gets the list of available bucket instances.
	 * @return array set of bucket instances.
	 */
	public function getBuckets();

	/**
	 * Gets the bucket intance by name.
	 * @param string $bucketName - name of the bucket.
	 * @return array set of bucket instances.
	 */
	public function getBucket($bucketName);

	/**
	 * Adds the bucket to the buckets list.
	 * @param string $bucketName - name of the bucket.
	 * @param mixed $bucketData - bucket instance or configuration array.
	 * @return boolean success.
	 */
	public function addBucket($bucketName, $bucketData = array());

	/**
	 * Indicates if the bucket has been set up in the storage.
	 * @param string $bucketName - name of the bucket.
	 * @return boolean success.
	 */
	public function hasBucket($bucketName);
}