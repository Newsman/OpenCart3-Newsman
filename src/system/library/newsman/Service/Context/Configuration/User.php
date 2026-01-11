<?php

namespace Newsman\Service\Context\Configuration;

use Newsman\Service\Context\Store;

/**
 * Class Service Context Configuration User
 *
 * @class \Newsman\Service\Context\Configuration\User
 */
class User extends Store {
	/**
	 * API user ID
	 *
	 * @var string|int
	 */
	protected $user_id;

	/**
	 * API key
	 *
	 * @var string
	 */
	protected $api_key;

	/**
	 * Set API user ID
	 *
	 * @param string|int $user_id API user ID.
	 *
	 * @return $this
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;

		return $this;
	}

	/**
	 * Get API user ID
	 *
	 * @return int|string
	 */
	public function getUserId() {
		return $this->user_id;
	}

	/**
	 * Set API key
	 *
	 * @param string $api_key API key.
	 *
	 * @return $this
	 */
	public function setApiKey(string $api_key) {
		$this->api_key = $api_key;

		return $this;
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function getApiKey() {
		return $this->api_key;
	}
}
