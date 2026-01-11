<?php

namespace Newsman\Api;

/**
 * Class API Context interface
 *
 * @class \Newsman\Api\ContextInterface
 */
interface ContextInterface {
	/**
	 * Get API user ID
	 *
	 * @return string
	 */
	public function getUserId();

	/**
	 * Get API segment ID
	 *
	 * @return string
	 */
	public function getSegmentId();

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function getApiKey();

	/**
	 * Set store ID
	 *
	 * @param int|string $store_id Store ID.
	 *
	 * @return ContextInterface
	 */
	public function setStoreId($store_id);

	/**
	 * Get Store ID
	 *
	 * @return int|string
	 */
	public function getStoreId();

	/**
	 * Set API user ID
	 *
	 * @param int|string $user_id API user ID.
	 *
	 * @return ContextInterface
	 */
	public function setUserId($user_id);

	/**
	 * Set API segment ID
	 *
	 * @param string $segment_id Segment ID.
	 *
	 * @return ContextInterface
	 */
	public function setSegmentId($segment_id);

	/**
	 * Set API key
	 *
	 * @param string $api_key API key.
	 *
	 * @return ContextInterface
	 */
	public function setApiKey($api_key);

	/**
	 * API REST HTTP endpoint
	 *
	 * @param string $endpoint API REST endpoint.
	 *
	 * @return ContextInterface
	 */
	public function setEndpoint($endpoint);

	/**
	 * Get API REST endpoint
	 *
	 * @return string
	 */
	public function getEndpoint();

	/**
	 * Set API list ID
	 *
	 * @param int $list_id API list ID.
	 *
	 * @return ContextInterface
	 */
	public function setListId($list_id);

	/**
	 * Get API list ID
	 *
	 * @return int
	 */
	public function getListId();
}
