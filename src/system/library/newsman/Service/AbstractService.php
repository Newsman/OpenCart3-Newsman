<?php

namespace Newsman\Service;

use Newsman\Api\ClientInterface;
use Newsman\Api\ContextInterface;
use Newsman\Service\Context\AbstractContext;

/**
 * Class Service Abstract Service
 *
 * @class \Newsman\Service\AbstractService
 */
class AbstractService extends \Newsman\Nzmbase {
	/**
	 * API context
	 *
	 * @var ContextInterface
	 */
	protected $api_context;

	/**
	 * API client
	 *
	 * @var ClientInterface
	 */
	protected $api_client;

	/**
	 * Email validator
	 *
	 * @var \Newsman\Validator\Email
	 */
	protected $validator_email;

	/**
	 * Store ID
	 *
	 * @var null|int
	 */
	protected $store_id;

	/**
	 * Class construct
	 */
	public function __construct($registry) {
		parent::__construct($registry);

		$this->api_context = new \Newsman\Api\Context($this->registry);
		$this->api_client = new \Newsman\Api\Client($this->registry);
		$this->validator_email = new \Newsman\Validator\Email();
	}

	/**
	 * Create API context
	 *
	 * @return \Newsman\Api\Context|ContextInterface
	 */
	public function createApiContext() {
		$this->api_context = new \Newsman\Api\Context($this->registry);
		$this->api_context->setStoreId($this->getStoreId());

		return $this->api_context;
	}

	/**
	 * Create API client
	 *
	 * @return \Newsman\Api\Client|ClientInterface
	 */
	public function createApiClient() {
		$this->api_client = new \Newsman\Api\Client($this->registry);

		return $this->api_client;
	}

	/**
	 * Execute API service
	 *
	 * @param AbstractContext $context APi service context.
	 *
	 * @return array|string
	 */
	public function execute($context) {
		return array();
	}

	/**
	 * Set Store ID
	 *
	 * @param null|int $store_id Store ID.
	 *
	 * @return $this
	 */
	public function setStoreId($store_id) {
		$this->store_id = $store_id;

		return $this;
	}

	/**
	 * Get store ID
	 *
	 * @return int|null Store ID.
	 */
	public function getStoreId() {
		return $this->store_id;
	}

	/**
	 * Validate email address
	 *
	 * @param string $email Email address to validate.
	 *
	 * @return void
	 * @throws \Exception Throws error on an invalid email address.
	 */
	public function validateEmail($email) {
		$validator = new \Newsman\Validator\Email();
		if (!$validator->isValid($email)) {
			$e = new \Exception(
				sprintf(
					$this->escapeHtml('Invalid email address %1'),
					$email
				)
			);
			$this->logger->logException($e);
			throw $e;
		}
	}
}
