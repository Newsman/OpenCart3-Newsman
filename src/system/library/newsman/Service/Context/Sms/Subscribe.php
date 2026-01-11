<?php

namespace Newsman\Service\Context\Sms;

/**
 * Class Service Context Subscribe Telephone Number to list
 *
 * @class \Newsman\Service\Context\Sms\Subscribe
 */
class Subscribe extends Unsubscribe {
	/**
	 * Subscriber firstname
	 *
	 * @var string
	 */
	protected $firstname;

	/**
	 * Subscriber lastname
	 *
	 * @var string
	 */
	protected $lastname;

	/**
	 * Properties
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Set subscriber firstname
	 *
	 * @param string $firstname Subscriber firstname.
	 *
	 * @return $this
	 */
	public function setFirstname($firstname) {
		$this->firstname = $firstname;

		return $this;
	}

	/**
	 * Get subscriber firstname
	 *
	 * @return string
	 */
	public function getFirstname() {
		if (empty($this->firstname)) {
			return self::NULL_VALUE;
		}

		return $this->firstname;
	}

	/**
	 * Set subscriber lastname
	 *
	 * @param string $lastname Subscriber lastname.
	 *
	 * @return $this
	 */
	public function setLastname($lastname) {
		$this->lastname = $lastname;

		return $this;
	}

	/**
	 * Get subscriber lastname
	 *
	 * @return string
	 */
	public function getLastname() {
		if (empty($this->lastname)) {
			return self::NULL_VALUE;
		}

		return $this->lastname;
	}

	/**
	 * Set properties
	 *
	 * @param array $properties Properties.
	 *
	 * @return $this
	 */
	public function setProperties($properties) {
		$this->properties = $properties;

		return $this;
	}

	/**
	 * Get properties
	 *
	 * @return array
	 */
	public function getProperties() {
		return $this->properties;
	}
}
