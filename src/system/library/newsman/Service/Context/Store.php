<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Store
 *
 * @class \Newsman\Service\Context\Store
 */
class Store extends AbstractContext {
	/**
	 * Store ID
	 *
	 * @var null|int
	 */
	protected $store_id;

	/**
	 * API list ID
	 *
	 * @var null|int
	 */
	protected $list_id;

	/**
	 * API segment ID
	 *
	 * @var null|int
	 */
	protected $segment_id;

	/**
	 * Set Store ID
	 *
	 * @param int $store_id Store ID.
	 *
	 * @return $this
	 */
	public function setStoreId($store_id) {
		$this->store_id = $store_id;

		return $this;
	}

	/**
	 * Get Store ID
	 *
	 * @return null|int
	 */
	public function getStoreId() {
		return $this->store_id;
	}


	/**
	 * Set API list ID
	 *
	 * @param int $list_id API list ID.
	 *
	 * @return $this
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
		return $this->list_id;
	}

	/**
	 * Set API segment ID
	 *
	 * @param string $segment_id Segment ID.
	 *
	 * @return $this
	 */
	public function setSegmentId($segment_id) {
		$this->segment_id = $segment_id;

		return $this;
	}

	/**
	 * Get API segment ID
	 *
	 * @return string
	 */
	public function getSegmentId() {
		return $this->segment_id;
	}
}
