<?php

namespace Newsman\Api;

/**
 * Class API Context
 *
 * @class \Newsman\Api\Context
 */
class Context extends \Newsman\Nzmbase implements ContextInterface {
	/**
	 * Store ID
	 *
	 * @var int|string
	 */
	protected $store_id;

	/**
	 * API user ID
	 *
	 * @var string
	 */
	protected $user_id;

	/**
	 * API segment ID
	 *
	 * @var string
	 */
	protected $segment_id;

	/**
	 * API key
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * API REST endpoint
	 *
	 * @var string
	 */
	protected $endpoint;

	/**
	 * API list ID
	 *
	 * @var int
	 */
	protected $list_id;

	/**
	 * Get API user ID
	 *
	 * @return string
	 */
	public function getUserId() {
		if (null !== $this->user_id) {
			return $this->user_id;
		}

		return $this->config->getUserId($this->getStoreId());
	}

	/**
	 * Get API segment ID
	 *
	 * @return string
	 */
	public function getSegmentId() {
		if (null !== $this->segment_id) {
			return $this->segment_id;
		}

		return $this->config->getSegmentId($this->getStoreId());
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function getApiKey() {
		if (null !== $this->api_key) {
			return $this->api_key;
		}

		return $this->config->getApiKey($this->getStoreId());
	}

	/**
	 * Set store ID
	 *
	 * @param int|string $store_id Store ID.
	 *
	 * @return ContextInterface
	 */
	public function setStoreId($store_id) {
		$this->store_id = $store_id;

		return $this;
	}

	/**
	 * Get store ID
	 *
	 * @return int|string
	 */
	public function getStoreId() {
		if (null === $this->store_id) {
			$this->store_id = $this->config->getCurrentStoreId();
		}

		return $this->store_id;
	}

	/**
	 * Set API user ID
	 *
	 * @param int|string $user_id API user ID.
	 *
	 * @return ContextInterface
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;

		return $this;
	}

	/**
	 * Set API segment ID
	 *
	 * @param string $segment_id Segment ID.
	 *
	 * @return ContextInterface
	 */
	public function setSegmentId($segment_id) {
		$this->segment_id = $segment_id;

		return $this;
	}

	/**
	 * Set API key
	 *
	 * @param string $api_key API key.
	 *
	 * @return ContextInterface
	 */
	public function setApiKey($api_key) {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * API REST HTTP endpoint
	 *
	 * @param string $endpoint API REST endpoint.
	 *
	 * @return ContextInterface
	 */
	public function setEndpoint($endpoint) {
		$this->endpoint = $endpoint;

		return $this;
	}

	/**
	 * Get API REST endpoint
	 *
	 * @return string
	 */
	public function getEndpoint() {
		return $this->endpoint;
	}

	/**
	 * Set API list ID
	 *
	 * @param int $list_id API list ID.
	 *
	 * @return ContextInterface
	 */
	public function setListId($list_id) {
		$this->list_id = $list_id;

		return $this;
	}

	/**
	 * Get API list ID
	 *
	 * @return int
	 */
	public function getListId() {
		if (null !== $this->list_id) {
			return $this->list_id;
		}

		return $this->config->getListId($this->getStoreId());
	}
}
