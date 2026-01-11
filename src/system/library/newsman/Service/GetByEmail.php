<?php

namespace Newsman\Service;

/**
 * API Class Service Get by Email from list
 *
 * @class Newsman\Service\GetByEmail
 */
class GetByEmail extends AbstractService {
	/**
	 * Get by email from email list Newsman API endpoint
	 *
	 * @see https://kb.newsman.com/ap/1.2/subscriber.getByEmail
	 */
	public const ENDPOINT = 'subscriber.getByEmail';

	/**
	 * Get subscriber by email
	 *
	 * @param Context\GetByEmail $context Get by email context.
	 *
	 * @return array|string
	 * @throws \Exception Throw exception on errors.
	 */
	public function execute($context) {
		$this->validateEmail($context->getEmail());

		$api_context = $this->createApiContext()
			->setListId($context->getListId())
			->setStoreId($context->getStoreId())
			->setEndpoint(self::ENDPOINT);

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Try to get by email %s'),
				$context->getEmail()
			)
		);

		$client = $this->createApiClient();
		$this->event->trigger('newsman/service_get_by_email/before', array($context));
		$result = $client->get(
			$api_context,
			array(
				'list_id' => $api_context->getListId(),
				'email'   => $context->getEmail(),
			)
		);

		if ($client->hasError()) {
			throw new \Exception($this->escapeHtml($client->getErrorMessage()), $client->getErrorCode());
		}

		$this->logger->info(
			sprintf(
				$this->escapeHtml('Done get by email %s'),
				$context->getEmail()
			)
		);

		return $result;
	}
}
