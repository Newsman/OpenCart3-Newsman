<?php

namespace Newsman;

/**
 * Newsman Library base class
 */
class Library {
	/**
	 * @var \Registry
	 */
	protected $registry;

	/**
	 * Constructor
	 *
	 * @param \Registry $registry
	 */
	public function __construct($registry) {
		$this->registry = $registry;
	}

	/**
	 * Magic method to retrieve a value from the registry.
	 *
	 * @param string $key The key to retrieve from the registry.
	 * @return mixed The value associated with the provided key.
	 */
	public function __get($key) {
		return $this->registry->get($key);
	}

	/**
	 * Magic method to set a value in the registry.
	 *
	 * @param string $key   The key to identify the value.
	 * @param mixed  $value The value to be set in the registry.
	 * @return void
	 */
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
}
