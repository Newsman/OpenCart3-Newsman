<?php

namespace Newsman\Service\Context\Configuration;

/**
 * Class Service Context Configuration SaveListIntegrationSetup
 *
 * @class \Newsman\Service\Context\Configuration\SaveListIntegrationSetup
 */
class SaveListIntegrationSetup extends EmailList {
	/**
	 * Integration platform identifier
	 *
	 * @var string
	 */
	protected $integration = 'opencart';

	/**
	 * Payload data for the integration setup
	 *
	 * @var array
	 */
	protected $payload = array();

	/**
	 * Set the integration platform identifier
	 *
	 * @param string $integration Integration platform name.
	 *
	 * @return $this
	 */
	public function setIntegration($integration) {
		$this->integration = $integration;

		return $this;
	}

	/**
	 * Get the integration platform identifier
	 *
	 * @return string
	 */
	public function getIntegration() {
		return $this->integration;
	}

	/**
	 * Set the payload data
	 *
	 * @param array $payload Payload data.
	 *
	 * @return $this
	 */
	public function setPayload(array $payload) {
		$this->payload = $payload;

		return $this;
	}

	/**
	 * Get the payload data
	 *
	 * @return array
	 */
	public function getPayload() {
		return $this->payload;
	}
}
