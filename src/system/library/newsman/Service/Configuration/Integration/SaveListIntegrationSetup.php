<?php

namespace Newsman\Service\Configuration\Integration;

use Newsman\Service\AbstractService;

/**
 * API Class Service Configuration Integration saveListIntegrationSetup
 *
 * @class \Newsman\Service\Configuration\Integration\SaveListIntegrationSetup
 */
class SaveListIntegrationSetup extends AbstractService {
	/**
	 * Save list integration setup in Newsman
	 *
	 * @see https://kb.newsman.ro/api/1.2/integration.saveListIntegrationSetup
	 */
	public const ENDPOINT = 'integration.saveListIntegrationSetup';

	/**
	 * Save integration setup for a list in Newsman
	 *
	 * @param \Newsman\Service\Context\Configuration\SaveListIntegrationSetup $context Save list integration setup context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		if (empty($context->getListId())) {
			$e = new \Exception($this->escapeHtml('List ID is required.'));
			$this->logger->error($e);
			throw $e;
		}

		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$client = $this->createApiClient();
		$result = $client->post(
			$api_context,
			array(),
			array(
				'list_id'     => $api_context->getListId(),
				'integration' => $context->getIntegration(),
				'payload'     => json_encode($context->getPayload()),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		return $result;
	}
}
