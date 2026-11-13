<?php

namespace Newsman\Service\Context;

/**
 * Class Service Context Anonymise Email
 *
 * @class \Newsman\Service\Context\AnonymiseEmail
 */
class AnonymiseEmail extends Store {
	/**
	 * Subscriber E-mail
	 *
	 * @var string
	 */
	protected $email;

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
}
