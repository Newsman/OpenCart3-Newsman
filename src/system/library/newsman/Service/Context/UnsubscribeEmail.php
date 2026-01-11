<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Unsubscribe Email
 *
 * @class \Newsman\Service\Context\UnsubscribeEmail
 */
class UnsubscribeEmail extends Store {
	/**
	 * Subscriber E-mail
	 *
	 * @var string
	 */
	protected $email;

	/**
	 * Subscriber IP address
	 *
	 * @var string
	 */
	protected $ip;

	/**
	 * Set subscriber E-mail
	 *
	 * @param string $email Subscriber E-mail.
	 *
	 * @return $this
	 */
	public function setEmail($email) {
		$this->email = $email;

		return $this;
	}

	/**
	 * Get subscriber E-mail
	 *
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
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
