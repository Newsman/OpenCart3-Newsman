<?php

namespace Newsman\Service\Context\Sms;

use Newsman\Service\Context\Store;

/**
 * Class Service Context SMS Unsubscribe from a list
 *
 * @class \Newsman\Service\Context\Sms\Unsubscribe
 */
class Unsubscribe extends Store {
	/**
	 * Telephone number
	 *
	 * @var string
	 */
	protected $telephone;

	/**
	 * Subscriber IP address
	 *
	 * @var string
	 */
	protected $ip;

	/**
	 * Set a subscriber telephone number
	 *
	 * @param string $telephone Subscriber telephone number.
	 *
	 * @return $this
	 */
	public function setTelephone($telephone) {
		$this->telephone = $telephone;

		return $this;
	}

	/**
	 * Get a subscriber telephone number
	 *
	 * @return string
	 */
	public function getTelephone() {
		return $this->telephone;
	}

	/**
	 * Set a subscriber IP address
	 *
	 * @param string $ip Subscriber IP address.
	 *
	 * @return $this
	 */
	public function setIp($ip) {
		$this->ip = $ip;

		return $this;
	}

	/**
	 * Get a subscriber IP address
	 *
	 * @return string
	 */
	public function getIp() {
		return $this->ip;
	}
}
